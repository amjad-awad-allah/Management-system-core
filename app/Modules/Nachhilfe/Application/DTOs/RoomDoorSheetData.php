<?php

namespace App\Modules\Nachhilfe\Application\DTOs;

final class RoomDoorSheetData
{
    /**
     * @param array<int, array{
     *     start_time: string,
     *     end_time: string,
     *     subject: string,
     *     teacher_name: string,
     *     student_count: int,
     *     status: string
     * }> $lessons
     */
    public function __construct(
        public readonly string $roomId,
        public readonly string $roomName,
        public readonly string $date,
        public readonly array $lessons,
        public readonly int $totalLessons,
        public readonly string $generatedAt,
        public readonly ?int $roomCapacity = null,
    ) {}

    public function toArray(): array
    {
        return [
            'room_id' => $this->roomId,
            'room_name' => $this->roomName,
            'date' => $this->date,
            'lessons' => $this->lessons,
            'total_lessons' => $this->totalLessons,
            'generated_at' => $this->generatedAt,
            'room_capacity' => $this->roomCapacity,
        ];
    }
}
