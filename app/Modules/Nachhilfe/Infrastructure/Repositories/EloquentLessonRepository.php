<?php

namespace App\Modules\Nachhilfe\Infrastructure\Repositories;

use App\Modules\Nachhilfe\Domain\Entities\Lesson;
use App\Modules\Nachhilfe\Domain\Repositories\LessonRepositoryInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonModel;
use App\Shared\Contracts\Events\DomainEventBus;

class EloquentLessonRepository implements LessonRepositoryInterface
{
    public function __construct(
        private readonly DomainEventBus $eventBus
    ) {}

    public function save(Lesson $lesson): void
    {
        $model = LessonModel::find($lesson->id) ?? new LessonModel();
        $model->id = $lesson->id;
        $model->student_id = $lesson->studentId;
        $model->teacher_id = $lesson->teacherId;
        $model->subject_id = $lesson->subjectId;
        $model->scheduled_at = $lesson->scheduledAt;
        $model->duration_minutes = $lesson->durationMinutes;
        $model->status = $lesson->status;
        $model->price = $lesson->price->amount();
        $model->save();

        $events = $lesson->releaseDomainEvents();
        foreach ($events as $event) {
            $this->eventBus->publish($event);
        }
    }
}
