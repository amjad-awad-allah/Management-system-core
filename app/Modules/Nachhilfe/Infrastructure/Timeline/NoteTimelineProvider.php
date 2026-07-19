<?php

namespace App\Modules\Nachhilfe\Infrastructure\Timeline;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Application\Contracts\TimelineProviderInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use Carbon\Carbon;

class NoteTimelineProvider implements TimelineProviderInterface
{
    public function getEvents(Student $student): array
    {
        $lessonStudents = LessonStudent::where('student_id', $student->id)
            ->whereNotNull('notes')
            ->where('notes', '!=', '')
            ->with(['lesson.subject', 'lesson.teacher'])
            ->get();

        $events = [];
        foreach ($lessonStudents as $ls) {
            $lesson = $ls->lesson;
            if (!$lesson) continue;

            $updatedAt = Carbon::parse($ls->updated_at);
            $events[] = [
                'id' => 'note-' . $ls->id,
                'type' => 'note',
                'title' => 'Study note added',
                'description' => "Note on " . ($lesson->subject?->name ?? '') . ": \"{$ls->notes}\"",
                'date' => $updatedAt->format('Y-m-d'),
                'time' => $updatedAt->format('H:i'),
                'icon' => 'chat-bubble',
                'color' => 'orange',
                'metadata' => [
                    'lesson_student_id' => $ls->id,
                    'notes' => $ls->notes,
                    'subject' => $lesson->subject?->name,
                    'teacher' => $lesson->teacher?->name,
                ]
            ];
        }

        return $events;
    }
}
