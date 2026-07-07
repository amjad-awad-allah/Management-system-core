<?php

namespace App\Modules\Nachhilfe\Application\Actions;

use App\Modules\Nachhilfe\Domain\Entities\Lesson;
use App\Modules\Nachhilfe\Domain\Events\LessonBooked;
use App\Modules\Nachhilfe\Domain\Repositories\LessonRepositoryInterface;
use App\Modules\Nachhilfe\Domain\ValueObjects\Money;
use Illuminate\Support\Str;

class BookLessonAction
{
    public function __construct(
        private readonly LessonRepositoryInterface $lessonRepository
    ) {}

    public function execute(
        string $studentId,
        string $teacherId,
        string $subjectId,
        \DateTimeImmutable $scheduledAt,
        int $durationMinutes,
        float $priceAmount
    ): Lesson {
        $lessonId = (string) Str::ulid();
        $money = Money::of($priceAmount);

        $lesson = new Lesson(
            id: $lessonId,
            studentId: $studentId,
            teacherId: $teacherId,
            subjectId: $subjectId,
            scheduledAt: $scheduledAt,
            durationMinutes: $durationMinutes,
            status: 'scheduled',
            price: $money
        );

        $lesson->recordDomainEvent(new LessonBooked(
            lessonId: $lessonId,
            studentId: $studentId,
            teacherId: $teacherId,
            priceAmount: $priceAmount
        ));

        $this->lessonRepository->save($lesson);

        return $lesson;
    }
}
