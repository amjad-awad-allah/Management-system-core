<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Nachhilfe\Application\Actions\BookLessonAction;
use App\Modules\Nachhilfe\Application\DTOs\LessonBookingData;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonModel;
use App\Modules\Nachhilfe\Presentation\Requests\BookLessonRequest;
use App\Modules\Nachhilfe\Presentation\Resources\LessonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use DateTimeImmutable;

class LessonController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', LessonModel::class);

        $lessons = LessonModel::query()->with(['student', 'teacher', 'subject'])->latest('start_time')->paginate();

        return LessonResource::collection($lessons);
    }

    public function store(BookLessonRequest $request, BookLessonAction $action): \Illuminate\Http\JsonResponse
    {
        $dto = new LessonBookingData(
            studentId: (string) $request->validated('student_id'),
            teacherId: (string) $request->validated('teacher_id'),
            subjectId: (string) $request->validated('subject_id'),
            scheduledAt: new DateTimeImmutable((string) $request->validated('scheduled_at')),
            durationMinutes: (int) $request->validated('duration_minutes')
        );

        $lesson = $action->execute($dto);

        // Action returns Domain Entity Lesson.
        // We should convert this or reload from DB for the resource if needed,
        // or map the domain entity directly to an array.
        // Let's reload from eloquent to match the resource structure perfectly.
        $model = LessonModel::findOrFail($lesson->id);

        return (new LessonResource($model))->response()->setStatusCode(201);
    }
}
