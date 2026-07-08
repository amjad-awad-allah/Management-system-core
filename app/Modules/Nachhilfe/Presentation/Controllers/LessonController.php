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

    public function index()
    {
        $lessons = Lesson::with(['teacher', 'room', 'subject', 'students'])->latest('date')->paginate();
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

            $lesson->load(['teacher', 'room', 'subject', 'students']);

            return (new LessonResource($lesson))->response()->setStatusCode(201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Conflict detected: ' . $e->getMessage()
            ], 409);
        }
    }
}
