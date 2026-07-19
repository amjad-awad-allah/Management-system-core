<?php

namespace App\Modules\Nachhilfe\Infrastructure\Timeline;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Application\Contracts\TimelineProviderInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;

class LessonTimelineProvider implements TimelineProviderInterface
{
    public function getEvents(Student $student): array
    {
        $lessonStudents = LessonStudent::with(['lesson.subject', 'lesson.teacher'])
            ->where('student_id', $student->id)
            ->get();

        $events = [];
        foreach ($lessonStudents as $ls) {
            $lesson = $ls->lesson;
            if (!$lesson) continue;

            $statusText = [
                'scheduled' => 'Lesson scheduled',
                'completed' => 'Lesson completed',
                'cancelled' => 'Lesson cancelled',
                'rescheduled' => 'Lesson rescheduled',
            ][$lesson->status] ?? 'Lesson';

            $events[] = [
                'id' => 'lesson-' . $lesson->id,
                'type' => 'lesson',
                'title' => $statusText . ' (' . ($lesson->subject?->name ?? '') . ')',
                'description' => 'With teacher ' . ($lesson->teacher?->name ?? '') . ' - at ' . substr($lesson->start_time, 0, 5),
                'date' => $lesson->date,
                'time' => substr($lesson->start_time, 0, 5),
                'icon' => 'calendar',
                'color' => [
                    'scheduled' => 'purple',
                    'completed' => 'green',
                    'cancelled' => 'red',
                    'rescheduled' => 'blue',
                ][$lesson->status] ?? 'purple',
                'metadata' => [
                    'lesson_id' => $lesson->id,
                    'status' => $lesson->status,
                    'subject' => $lesson->subject?->name,
                    'teacher' => $lesson->teacher?->name,
                    'duration' => $lesson->duration_minutes,
                ]
            ];
        }

        return $events;
    }
}
