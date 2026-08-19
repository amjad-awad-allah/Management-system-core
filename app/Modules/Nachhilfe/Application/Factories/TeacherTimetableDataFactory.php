<?php

namespace App\Modules\Nachhilfe\Application\Factories;

use App\Modules\Nachhilfe\Application\DTOs\TeacherTimetableData;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use Carbon\Carbon;

class TeacherTimetableDataFactory
{
    private string $timezone = 'Europe/Berlin';

    public function create(string $teacherId, string $startDate, string $endDate): TeacherTimetableData
    {
        $teacher = Teacher::findOrFail($teacherId);

        $lessons = Lesson::with(['room', 'subject', 'students'])
            ->where('teacher_id', $teacherId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $lessonData = [];
        foreach ($lessons as $lesson) {
            $lessonData[] = [
                'date' => Carbon::parse($lesson->date)->format('d.m.Y'),
                'start_time' => substr($lesson->start_time, 0, 5),
                'end_time' => substr($lesson->end_time, 0, 5),
                'subject' => $lesson->subject?->name ?? 'N/A',
                'room' => $lesson->room?->name ?? 'N/A',
                'student_count' => $lesson->students->count(),
                'status' => ucfirst($lesson->status),
            ];
        }

        return new TeacherTimetableData(
            teacherId: $teacher->id,
            teacherName: $teacher->name,
            startDate: Carbon::parse($startDate)->format('d.m.Y'),
            endDate: Carbon::parse($endDate)->format('d.m.Y'),
            lessons: $lessonData,
            totalLessons: count($lessonData),
            generatedAt: Carbon::now($this->timezone)->format('d.m.Y H:i'),
        );
    }
}
