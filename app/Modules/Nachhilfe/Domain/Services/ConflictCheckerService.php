<?php

namespace App\Modules\Nachhilfe\Domain\Services;

use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use Carbon\Carbon;
use Exception;

class ConflictCheckerService
{
    /**
     * Check if a lesson can be scheduled without conflicts.
     * Throws an exception if a conflict is detected.
     *
     * @param string $teacherId
     * @param string $roomId
     * @param string $date (Y-m-d)
     * @param string $startTime (H:i)
     * @param string $endTime (H:i)
     * @param string|null $excludeLessonId (for updates)
     * @return void
     * @throws Exception
     */
    public function checkConflicts(string $teacherId, string $roomId, string $date, string $startTime, string $endTime, ?string $excludeLessonId = null): void
    {
        // Format to H:i:s for consistent SQL string comparison
        $startTime = Carbon::parse($startTime)->format('H:i:s');
        $endTime = Carbon::parse($endTime)->format('H:i:s');

        $this->checkTeacherConflict($teacherId, $date, $startTime, $endTime, $excludeLessonId);
        $this->checkRoomConflict($roomId, $date, $startTime, $endTime, $excludeLessonId);
    }

    private function checkTeacherConflict(string $teacherId, string $date, string $startTime, string $endTime, ?string $excludeLessonId): void
    {
        $query = Lesson::where('teacher_id', $teacherId)
            ->where('date', $date)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($subQ) use ($startTime, $endTime) {
                    // Overlaps logic: (start < new_end) AND (end > new_start)
                    $subQ->where('start_time', '<', $endTime)
                         ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeLessonId) {
            $query->where('id', '!=', $excludeLessonId);
        }

        if ($query->exists()) {
            throw new Exception("Teacher is already booked for this time slot.");
        }
    }

    private function checkRoomConflict(string $roomId, string $date, string $startTime, string $endTime, ?string $excludeLessonId): void
    {
        $query = Lesson::where('room_id', $roomId)
            ->where('date', $date)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($subQ) use ($startTime, $endTime) {
                    $subQ->where('start_time', '<', $endTime)
                         ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeLessonId) {
            $query->where('id', '!=', $excludeLessonId);
        }

        // Wait, what if the room is Online? Online rooms might have huge capacity or not block. 
        // But the requirement says checking room capacity / conflict.
        // For now, if a room is assigned, we consider it a conflict if it overlaps.
        // If it's a group lesson, multiple students can be in the same lesson (handled by lesson_students).
        // If we want two different lessons in the same room at the same time, we would check Room capacity.
        // But usually, one room = one lesson. I will stick to one lesson per room.

        if ($query->exists()) {
            throw new Exception("Room is already booked for this time slot.");
        }
    }
}
