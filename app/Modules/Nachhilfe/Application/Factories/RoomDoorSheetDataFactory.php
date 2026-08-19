<?php

namespace App\Modules\Nachhilfe\Application\Factories;

use App\Modules\Nachhilfe\Application\DTOs\RoomDoorSheetData;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use Carbon\Carbon;

class RoomDoorSheetDataFactory
{
    private string $timezone = 'Europe/Berlin';

    public function create(string $roomId, string $date): RoomDoorSheetData
    {
        $room = Room::findOrFail($roomId);

        $lessons = Lesson::with(['teacher', 'subject', 'students'])
            ->where('room_id', $roomId)
            ->where('date', $date)
            ->orderBy('start_time', 'asc')
            ->get();

        $lessonData = [];
        foreach ($lessons as $lesson) {
            $lessonData[] = [
                'start_time' => substr($lesson->start_time, 0, 5),
                'end_time' => substr($lesson->end_time, 0, 5),
                'subject' => $lesson->subject?->name ?? 'N/A',
                'teacher_name' => $lesson->teacher?->name ?? 'N/A',
                'student_count' => $lesson->students->count(),
                'status' => ucfirst($lesson->status),
            ];
        }

        return new RoomDoorSheetData(
            roomId: $room->id,
            roomName: $room->name,
            date: Carbon::parse($date)->format('d.m.Y'),
            lessons: $lessonData,
            totalLessons: count($lessonData),
            generatedAt: Carbon::now($this->timezone)->format('d.m.Y H:i'),
        );
    }
}
