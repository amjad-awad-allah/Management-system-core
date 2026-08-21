<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Services\CenterSettingsService;
use App\Modules\Nachhilfe\Application\Services\ConflictDetectionService;
use App\Modules\Nachhilfe\Application\Services\HolidayService;
use App\Modules\Nachhilfe\Infrastructure\Models\Holiday;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    private string $timezone = 'Europe/Berlin';

    public function __construct(
        private readonly HolidayService $holidayService,
        private readonly ConflictDetectionService $conflictDetectionService,
        private readonly CenterSettingsService $centerSettings
    ) {}

    /**
     * Unified Operational Calendar Feed Endpoint.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'teacher_id' => 'nullable|string',
            'room_id' => 'nullable|string',
            'subject_id' => 'nullable|string',
            'status' => 'nullable|string|in:scheduled,completed,cancelled',
            'state' => 'nullable|string|max:5',
        ]);

        $now = Carbon::now($this->timezone);
        $startDate = $validated['start_date'] ?? $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endDate = $validated['end_date'] ?? $now->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
        $state = !empty($validated['state']) ? strtoupper($validated['state']) : $this->centerSettings->getCenterBundesland();

        // 1. Query lessons within date range
        $query = Lesson::with(['room', 'subject', 'teacher', 'students', 'lessonStudents.attendance'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc');

        // Role-Based Isolation Boundary
        $user = $request->user();
        if ($user && $user->hasRole('Teacher')) {
            $teacher = \App\Modules\Nachhilfe\Infrastructure\Models\Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $query->where('teacher_id', $teacher->id);
            }
        } elseif ($user && $user->hasRole('Student')) {
            $student = \App\Modules\Nachhilfe\Infrastructure\Models\Student::where('user_id', $user->id)->first();
            if ($student) {
                $query->whereHas('students', function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                });
            }
        }

        if (!empty($validated['teacher_id'])) {
            $query->where('teacher_id', $validated['teacher_id']);
        }
        if (!empty($validated['room_id'])) {
            $query->where('room_id', $validated['room_id']);
        }
        if (!empty($validated['subject_id'])) {
            $query->where('subject_id', $validated['subject_id']);
        }
        if (!empty($validated['status'])) {
            $statusVal = strtolower($validated['status']);
            $query->where(function($q) use ($statusVal) {
                $q->where('status', $statusVal)
                  ->orWhere('status', ucfirst($statusVal));
            });
        }

        $lessons = $query->get();

        // 2. Detect schedule conflicts
        $conflictsMap = $this->conflictDetectionService->detectConflicts($lessons);

        // 3. Transform lessons with is_in_progress and conflict info
        $processedLessons = [];
        $scheduledCount = 0;
        $inProgressCount = 0;
        $completedCount = 0;
        $cancelledCount = 0;
        $totalConflictsCount = 0;

        foreach ($lessons as $lesson) {
            $startDateTime = Carbon::parse("{$lesson->date} {$lesson->start_time}", $this->timezone);
            $endDateTime = Carbon::parse("{$lesson->date} {$lesson->end_time}", $this->timezone);
            $normalizedStatus = strtolower($lesson->status);

            $isInProgress = false;
            if ($normalizedStatus === 'scheduled' && $startDateTime->lte($now) && $endDateTime->gt($now)) {
                $isInProgress = true;
                $inProgressCount++;
            } elseif ($normalizedStatus === 'scheduled') {
                $scheduledCount++;
            } elseif ($normalizedStatus === 'completed') {
                $completedCount++;
            } elseif ($normalizedStatus === 'cancelled') {
                $cancelledCount++;
            }

            $conflictInfo = $conflictsMap[$lesson->id] ?? [
                'has_conflict' => false,
                'conflict_types' => [],
                'conflict_details' => [],
            ];

            if ($conflictInfo['has_conflict']) {
                $totalConflictsCount++;
            }

            $lessonData = $lesson->toArray();
            $lessonData['status'] = $normalizedStatus;
            $lessonData['is_in_progress'] = $isInProgress;
            $lessonData['has_conflict'] = $conflictInfo['has_conflict'];
            $lessonData['conflict_types'] = $conflictInfo['conflict_types'];
            $lessonData['conflict_details'] = $conflictInfo['conflict_details'];

            $processedLessons[] = $lessonData;
        }

        // 4. Fetch Holidays via HolidayService
        $holidays = $this->holidayService->getHolidays($startDate, $endDate, $state);

        return response()->json([
            'range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'lessons' => $processedLessons,
            'holidays' => $holidays,
            'meta' => [
                'timezone' => $this->timezone,
                'week_starts_on' => 'monday',
                'state' => $state,
            ],
            'summary' => [
                'total' => count($lessons),
                'scheduled' => $scheduledCount,
                'in_progress' => $inProgressCount,
                'completed' => $completedCount,
                'cancelled' => $cancelledCount,
                'conflicts' => $totalConflictsCount,
            ],
        ]);
    }

    /**
     * Create custom center holiday or admin override.
     */
    public function storeHoliday(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:public,school,center',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'state' => 'nullable|string|max:5',
            'source' => 'nullable|in:custom,override',
            'external_id' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $holiday = Holiday::create([
            'source' => $validated['source'] ?? 'custom',
            'external_id' => $validated['external_id'] ?? null,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'state' => $validated['state'] ?? null,
            'is_active' => true,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json([
            'message' => 'Holiday saved successfully',
            'holiday' => $holiday,
        ], 201);
    }
}
