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

    public function execute(\App\Modules\Nachhilfe\Application\DTOs\LessonBookingData $dto): Lesson {
        $lessonId = (string) Str::ulid();
        
        // Pricing calculation belongs to the application layer.
        // For demonstration, 20.00 base rate per hour.
        $hours = $dto->durationMinutes / 60;
        $priceAmount = round(20.00 * $hours, 2);
        
        $money = Money::of($priceAmount);

        $lesson = new Lesson(
            id: $lessonId,
            studentId: $dto->studentId,
            teacherId: $dto->teacherId,
            subjectId: $dto->subjectId,
            scheduledAt: $dto->scheduledAt,
            durationMinutes: $dto->durationMinutes,
            status: 'scheduled',
            price: $money
        );

        $lesson->recordDomainEvent(new LessonBooked(
            lessonId: $lessonId,
            studentId: $dto->studentId,
            teacherId: $dto->teacherId,
            priceAmount: $priceAmount
        ));

        $this->lessonRepository->save($lesson);

        return $lesson;
    }
}
