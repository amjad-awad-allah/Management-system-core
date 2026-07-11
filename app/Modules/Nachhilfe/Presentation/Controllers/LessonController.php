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

        try {
            // 1. Triple-lock Conflict Prevention
            $this->conflictChecker->checkConflicts(
                $data['teacher_id'],
                $data['room_id'],
                $data['date'],
                $data['start_time'],
                $data['end_time']
            );

            // 2. Transaction for atomic save
            $lesson = DB::transaction(function () use ($data) {
                // Calculate duration
                $start = Carbon::parse($data['date'] . ' ' . $data['start_time']);
                $end = Carbon::parse($data['date'] . ' ' . $data['end_time']);
                $durationMinutes = $start->diffInMinutes($end);

                $lesson = Lesson::create([
                    'teacher_id' => $data['teacher_id'],
                    'room_id' => $data['room_id'],
                    'subject_id' => $data['subject_id'],
                    'type' => $data['type'],
                    'date' => $data['date'],
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'duration_minutes' => $durationMinutes,
                    'status' => 'scheduled',
                    'notes' => $data['notes'] ?? null,
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

                return $lesson;
            });

            $lesson->load(['teacher', 'room', 'subject', 'students', 'lessonStudents.attendance']);

            return (new LessonResource($lesson))->response()->setStatusCode(201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Conflict detected: ' . $e->getMessage()
            ], 409);
        }
    }

    public function update(BookLessonRequest $request, Lesson $lesson): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        try {
            // 1. Triple-lock Conflict Prevention, excluding this lesson
            $this->conflictChecker->checkConflicts(
                $data['teacher_id'],
                $data['room_id'],
                $data['date'],
                $data['start_time'],
                $data['end_time'],
                $lesson->id
            );

            // 2. Transaction for atomic save
            $lesson = DB::transaction(function () use ($data, $lesson) {
                // Calculate duration
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
                    // Check if pivot already exists to keep its ULID, otherwise generate new
                    $existingPivot = $lesson->students()->where('student_id', $student['student_id'])->first();
                    $pivotId = $existingPivot ? $existingPivot->pivot->id : (string) \Illuminate\Support\Str::ulid();
                    
                    $studentsToSync[$student['student_id']] = [
                        'id' => $pivotId,
                        'package_id' => $student['package_id'] ?? null,
                        'hours_consumed' => $existingPivot ? $existingPivot->pivot->hours_consumed : 0,
                    ];
                }
                
                $lesson->students()->sync($studentsToSync);

                return $lesson;
            });

            $lesson->load(['teacher', 'room', 'subject', 'students', 'lessonStudents.attendance']);

            return (new LessonResource($lesson))->response()->setStatusCode(200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Conflict detected: ' . $e->getMessage()
            ], 409);
        }
    }

    public function updateStatus(Request $request, Lesson $lesson): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:scheduled,confirmed,started,completed,cancelled,no-show,rescheduled,Scheduled,Confirmed,Started,Completed,Cancelled,NoShow,Rescheduled'
        ]);

        $oldStatus = strtolower($lesson->status);
        $lesson->status = $validated['status'];
        $lesson->save();

        if ($oldStatus !== 'completed' && strtolower($lesson->status) === 'completed') {
            \App\Modules\Nachhilfe\Domain\Events\LessonCompleted::dispatch($lesson);
        }

        return response()->json(['message' => 'Status updated successfully', 'lesson' => new LessonResource($lesson->fresh(['teacher', 'room', 'subject', 'students']))]);
    }
}
