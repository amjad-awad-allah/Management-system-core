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
        LessonModel::updateOrCreate(
            ['id' => $lesson->id],
            [
                'student_id' => $lesson->studentId,
                'teacher_id' => $lesson->teacherId,
                'subject_id' => $lesson->subjectId,
                'scheduled_at' => $lesson->scheduledAt,
                'duration_minutes' => $lesson->durationMinutes,
                'status' => $lesson->status,
                'price' => $lesson->price->amount(),
            ]
        );

        $events = $lesson->releaseDomainEvents();
        foreach ($events as $event) {
            $this->eventBus->publish($event);
        }
    }
}
