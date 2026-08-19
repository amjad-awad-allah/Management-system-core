<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Nachhilfe\Domain\Services\ConflictCheckerService;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Presentation\Requests\BookLessonRequest;
use App\Modules\Nachhilfe\Presentation\Resources\LessonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class LessonController extends Controller
{
    public function __construct(
        private readonly ConflictCheckerService $conflictChecker
    ) {}

    public function index(\Illuminate\Http\Request $request)
    {
        $query = Lesson::with([
            'teacher' => fn($q) => $q->withTrashed(),
            'room', 
            'subject', 
            'students' => fn($q) => $q->withTrashed(), 
            'lessonStudents.attendance'
        ])->latest('date');

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->query('teacher_id'));
        }

        if ($request->has('student_id')) {
            $studentId = $request->query('student_id');
            $query->whereHas('students', function($q) use ($studentId) {
                $q->where('student_id', $studentId);
            });
        }

        $lessons = $query->paginate();
        return LessonResource::collection($lessons);
    }

    public function store(BookLessonRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $pattern = $data['recurrence_pattern'] ?? 'none';
        
        $dates = [];
        $startDate = Carbon::parse($data['date']);
        
        if ($pattern === 'none') {
            $dates[] = $data['date'];
        } else {
            $endDate = Carbon::parse($data['recurrence_end_date']);
            $currentDate = $startDate->copy();
            
            // Limit occurrences to 100 to prevent infinite loop or memory exhaustion
            $limit = 100;
            while ($currentDate->lessThanOrEqualTo($endDate) && $limit > 0) {
                $dates[] = $currentDate->toDateString();
                
                if ($pattern === 'daily') {
                    $currentDate->addDay();
                } elseif ($pattern === 'weekly') {
                    $currentDate->addWeek();
                } elseif ($pattern === 'monthly') {
                    $currentDate->addMonth();
                }
                $limit--;
            }
        }

        try {
            // 1. Triple-lock Conflict Prevention for each date
            foreach ($dates as $date) {
                $this->conflictChecker->checkConflicts(
                    $data['teacher_id'],
                    $data['room_id'],
                    $date,
                    $data['start_time'],
                    $data['end_time']
                );
            }

            // 2. Transaction for atomic save
            $lesson = DB::transaction(function () use ($data, $dates, $pattern) {
                // Calculate duration
                $start = Carbon::parse($data['date'] . ' ' . $data['start_time']);
                $end = Carbon::parse($data['date'] . ' ' . $data['end_time']);
                $durationMinutes = $start->diffInMinutes($end);

                $scheduleTemplateId = null;
                if ($pattern !== 'none') {
                    // Create a schedule template
                    $startDayOfWeekName = strtolower(Carbon::parse($data['date'])->englishDayOfWeek);
                    $template = \App\Modules\Nachhilfe\Infrastructure\Models\ScheduleTemplate::create([
                        'teacher_id' => $data['teacher_id'],
                        'room_id' => $data['room_id'],
                        'subject_id' => $data['subject_id'],
                        'frequency' => $pattern,
                        'interval' => 1,
                        'start_date' => $data['date'],
                        'end_date' => $data['recurrence_end_date'] ?? null,
                        'days_of_week' => [$startDayOfWeekName],
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'is_active' => true,
                    ]);
                    $scheduleTemplateId = $template->id;
                }

                $firstLesson = null;
                foreach ($dates as $date) {
                    $lesson = Lesson::create([
                        'teacher_id' => $data['teacher_id'],
                        'room_id' => $data['room_id'],
                        'subject_id' => $data['subject_id'],
                        'type' => $data['type'],
                        'date' => $date,
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'duration_minutes' => $durationMinutes,
                        'status' => 'scheduled',
                        'notes' => $data['notes'] ?? null,
                        'schedule_template_id' => $scheduleTemplateId,
                    ]);

                    // Attach students
                    $studentsToAttach = [];
                    foreach ($data['students'] as $student) {
                        $studentsToAttach[$student['student_id']] = [
                            'id' => (string) \Illuminate\Support\Str::ulid(),
                            'package_id' => $student['package_id'] ?? null,
                            'hours_consumed' => 0,
                        ];
                    }
                    $lesson->students()->attach($studentsToAttach);

                    if (!$firstLesson) {
                        $firstLesson = $lesson;
                    }
                }

                return $firstLesson;
            });

            $lesson->load(['teacher', 'room', 'subject', 'students', 'lessonStudents.attendance']);

            return (new LessonResource($lesson))->response()->setStatusCode(201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Schedule conflict detected: ' . $e->getMessage(),
                'conflicts' => [
                    ['message' => $e->getMessage()]
                ]
            ], 422);
        }
    }

    public function update(BookLessonRequest $request, Lesson $lesson): \Illuminate\Http\JsonResponse
    {
        app(\App\Modules\Nachhilfe\Domain\Services\LessonModificationGuard::class)->checkCanModify($lesson);

        $data = $request->validated();
        $updateSeries = filter_var($request->input('update_series'), FILTER_VALIDATE_BOOLEAN);

        try {
            if ($updateSeries && $lesson->schedule_template_id) {
                // Fetch all future lessons in the series
                $futureLessons = Lesson::where('schedule_template_id', $lesson->schedule_template_id)
                    ->where('date', '>=', $lesson->date)
                    ->get();

                // 1. Triple-lock Conflict Prevention for each future date (excluding the lessons themselves)
                foreach ($futureLessons as $fl) {
                    $this->conflictChecker->checkConflicts(
                        $data['teacher_id'],
                        $data['room_id'],
                        $fl->date,
                        $data['start_time'],
                        $data['end_time'],
                        $fl->id
                    );
                }

                // 2. Transaction for atomic save
                DB::transaction(function () use ($data, $futureLessons, $lesson) {
                    $start = Carbon::parse($lesson->date . ' ' . $data['start_time']);
                    $end = Carbon::parse($lesson->date . ' ' . $data['end_time']);
                    $durationMinutes = $start->diffInMinutes($end);

                    // Update schedule template
                    $template = \App\Modules\Nachhilfe\Infrastructure\Models\ScheduleTemplate::find($lesson->schedule_template_id);
                    if ($template) {
                        $template->update([
                            'teacher_id' => $data['teacher_id'],
                            'room_id' => $data['room_id'],
                            'subject_id' => $data['subject_id'],
                            'start_time' => $data['start_time'],
                            'end_time' => $data['end_time'],
                        ]);
                    }

                    foreach ($futureLessons as $fl) {
                        $fl->update([
                            'teacher_id' => $data['teacher_id'],
                            'room_id' => $data['room_id'],
                            'subject_id' => $data['subject_id'],
                            'type' => $data['type'],
                            'start_time' => $data['start_time'],
                            'end_time' => $data['end_time'],
                            'duration_minutes' => $durationMinutes,
                            'notes' => $data['notes'] ?? null,
                        ]);

                        // Sync students
                        $studentsToSync = [];
                        foreach ($data['students'] as $student) {
                            $existingPivot = $fl->students()->where('student_id', $student['student_id'])->first();
                            $pivotId = $existingPivot ? $existingPivot->pivot->id : (string) \Illuminate\Support\Str::ulid();
                            
                            $studentsToSync[$student['student_id']] = [
                                'id' => $pivotId,
                                'package_id' => $student['package_id'] ?? null,
                                'hours_consumed' => $existingPivot ? $existingPivot->pivot->hours_consumed : 0,
                            ];
                        }
                        $fl->students()->sync($studentsToSync);
                    }
                });
            } else {
                // Update this single instance only
                $this->conflictChecker->checkConflicts(
                    $data['teacher_id'],
                    $data['room_id'],
                    $data['date'],
                    $data['start_time'],
                    $data['end_time'],
                    $lesson->id
                );

                DB::transaction(function () use ($data, $lesson) {
                    $isRescheduled = ($lesson->date !== $data['date'] || $lesson->start_time !== $data['start_time']);

                    if ($isRescheduled) {
                        $eventIds = \App\Core\Notification\Models\NotificationEvent::where('event_type', 'lesson')
                            ->where('event_id', $lesson->id)
                            ->whereIn('notification_type', ['lesson_reminder_24h', 'lesson_reminder_2h'])
                            ->pluck('id');

                        if ($eventIds->isNotEmpty()) {
                            \App\Core\Notification\Models\NotificationOutbox::whereIn('notification_event_id', $eventIds)
                                ->whereIn('status', ['pending', 'failed'])
                                ->update(['status' => 'cancelled']);
                        }
                    }

                    $start = Carbon::parse($data['date'] . ' ' . $data['start_time']);
                    $end = Carbon::parse($data['date'] . ' ' . $data['end_time']);
                    $durationMinutes = $start->diffInMinutes($end);

                    $lesson->update([
                        'teacher_id' => $data['teacher_id'],
                        'room_id' => $data['room_id'],
                        'subject_id' => $data['subject_id'],
                        'type' => $data['type'],
                        'date' => $data['date'],
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'duration_minutes' => $durationMinutes,
                        'notes' => $data['notes'] ?? null,
                    ]);

                    // Sync students
                    $studentsToSync = [];
                    foreach ($data['students'] as $student) {
                        $existingPivot = $lesson->students()->where('student_id', $student['student_id'])->first();
                        $pivotId = $existingPivot ? $existingPivot->pivot->id : (string) \Illuminate\Support\Str::ulid();
                        
                        $studentsToSync[$student['student_id']] = [
                            'id' => $pivotId,
                            'package_id' => $student['package_id'] ?? null,
                            'hours_consumed' => $existingPivot ? $existingPivot->pivot->hours_consumed : 0,
                        ];
                    }
                    $lesson->students()->sync($studentsToSync);
                });
            }

            $lesson->load(['teacher', 'room', 'subject', 'students', 'lessonStudents.attendance']);

            return (new LessonResource($lesson))->response()->setStatusCode(200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Schedule conflict detected: ' . $e->getMessage(),
                'conflicts' => [
                    ['message' => $e->getMessage()]
                ]
            ], 422);
        }
    }

    public function updateStatus(Request $request, Lesson $lesson): \Illuminate\Http\JsonResponse
    {
        app(\App\Modules\Nachhilfe\Domain\Services\LessonModificationGuard::class)->checkCanModify($lesson);

        $validated = $request->validate([
            'status' => 'required|string|in:scheduled,confirmed,started,completed,cancelled,no-show,rescheduled,Scheduled,Confirmed,Started,Completed,Cancelled,NoShow,Rescheduled',
            'charge_student' => 'nullable|boolean',
            'cancellation_reason' => 'nullable|string|max:255',
        ]);

        $oldStatus = strtolower($lesson->status);
        $newStatus = strtolower($validated['status']);
        
        $lesson->status = $validated['status'];
        $lesson->save();

        if ($oldStatus !== 'completed' && $newStatus === 'completed') {
            \App\Modules\Nachhilfe\Domain\Events\LessonCompleted::dispatch($lesson);
        }

        if (($newStatus === 'cancelled' || $newStatus === 'no-show') && $oldStatus !== 'cancelled' && $oldStatus !== 'no-show') {
            $chargeStudent = $validated['charge_student'] ?? null;
            $reason = $validated['cancellation_reason'] ?? (($newStatus === 'no-show') ? 'No-show penalty' : 'Late cancellation penalty');

            $shouldDeduct = false;
            $deductPercentage = 100.00;

            if ($chargeStudent === true) {
                $shouldDeduct = true;
            } elseif ($chargeStudent === false) {
                $shouldDeduct = false;
            } else {
                // Determine based on cancellation policy
                $policy = \App\Modules\Nachhilfe\Infrastructure\Models\CancellationPolicy::where('is_active', true)->first();
                $hoursBefore = $policy ? $policy->hours_before : 24;
                $deductPercentage = $policy ? (float) $policy->deduct_percentage : 100.00;

                $lessonStart = Carbon::parse($lesson->date . ' ' . $lesson->start_time);
                // Note: diffInHours with false return parameter returns negative/positive difference relative to start
                $hoursDiff = now()->diffInHours($lessonStart, false);

                if ($hoursDiff < $hoursBefore) {
                    $shouldDeduct = true;
                    if (!isset($validated['cancellation_reason'])) {
                        $reason = "Late cancellation (< {$hoursBefore}h before start)";
                    }
                }
            }

            if ($shouldDeduct) {
                app(\App\Modules\Nachhilfe\Application\Services\SubscriptionUsageService::class)->deductForCancellation($lesson, $deductPercentage, $reason);
            }
        }

        return response()->json(['message' => 'Status updated successfully', 'lesson' => new LessonResource($lesson->fresh(['teacher', 'room', 'subject', 'students']))]);
    }
}
