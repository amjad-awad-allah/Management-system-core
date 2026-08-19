<?php

namespace App\Modules\Nachhilfe\Application\DTOs;

final class TeacherTimetableData
{
    /**
     * @param array<int, array{
     *     date: string,
     *     start_time: string,
     *     end_time: string,
     *     subject: string,
     *     room: string,
     *     student_count: int,
     *     status: string
     * }> $lessons
     */
    public function __construct(
        public readonly string $teacherId,
        public readonly string $teacherName,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly array $lessons,
        public readonly int $totalLessons,
        public readonly string $generatedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'teacher_id' => $this->teacherId,
            'teacher_name' => $this->teacherName,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'lessons' => $this->lessons,
            'total_lessons' => $this->totalLessons,
            'generated_at' => $this->generatedAt,
        ];
    }
}
