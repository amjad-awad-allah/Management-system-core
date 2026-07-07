<?php

namespace App\Modules\Nachhilfe\Domain\Events;

use App\Shared\Contracts\Events\DomainEvent;

class LessonBooked implements DomainEvent
{
    private readonly string $eventId;
    private readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $lessonId,
        public readonly string $studentId,
        public readonly string $teacherId,
        public readonly float $priceAmount
    ) {
        $this->eventId = (string) \Illuminate\Support\Str::ulid();
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getEventVersion(): int
    {
        return 1;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getAggregateId(): string
    {
        return $this->lessonId;
    }

    public function getCorrelationId(): ?string
    {
        return null;
    }

    public function toPayload(): array
    {
        return [
            'lesson_id' => $this->lessonId,
            'student_id' => $this->studentId,
            'teacher_id' => $this->teacherId,
            'price_amount' => $this->priceAmount,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        /** @var array{lesson_id: string, student_id: string, teacher_id: string, price_amount: float|int} $payload */
        return new self(
            lessonId: (string) $payload['lesson_id'],
            studentId: (string) $payload['student_id'],
            teacherId: (string) $payload['teacher_id'],
            priceAmount: (float) $payload['price_amount']
        );
    }
}
