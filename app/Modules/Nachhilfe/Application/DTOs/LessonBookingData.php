<?php

namespace App\Modules\Nachhilfe\Application\DTOs;

use DateTimeImmutable;

readonly class LessonBookingData
{
    public function __construct(
        public string $studentId,
        public string $teacherId,
        public string $subjectId,
        public DateTimeImmutable $scheduledAt,
        public int $durationMinutes
    ) {}
}
