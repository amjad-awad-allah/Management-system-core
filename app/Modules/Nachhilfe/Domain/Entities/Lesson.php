<?php

namespace App\Modules\Nachhilfe\Domain\Entities;

use App\Modules\Nachhilfe\Domain\ValueObjects\Money;
use App\Shared\Contracts\Events\DomainEvent;

class Lesson
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    public function __construct(
        public readonly string $id,
        public readonly string $studentId,
        public readonly string $teacherId,
        public readonly string $subjectId,
        public readonly \DateTimeImmutable $scheduledAt,
        public readonly int $durationMinutes,
        public readonly string $status,
        public readonly Money $price
    ) {}

    public function recordDomainEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return DomainEvent[]
     */
    public function releaseDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
