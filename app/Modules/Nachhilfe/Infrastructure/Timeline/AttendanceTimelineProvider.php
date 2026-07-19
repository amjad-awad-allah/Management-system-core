<?php

namespace App\Modules\Nachhilfe\Infrastructure\Timeline;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Application\Contracts\TimelineProviderInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\Attendance;

class AttendanceTimelineProvider implements TimelineProviderInterface
{
    public function getEvents(Student $student): array
    {
        $attendances = Attendance::whereHas('lessonStudent', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })
        ->with(['lessonStudent.lesson.subject', 'markedBy'])
        ->get();

        $events = [];
        foreach ($attendances as $attendance) {
            $lessonStudent = $attendance->lessonStudent;
            $lesson = $lessonStudent?->lesson;
            if (!$lesson) continue;

            $statusText = [
                'present' => 'Present',
                'absent_excused' => 'Absent (Excused)',
                'absent_unexcused' => 'Absent (Unexcused)',
                'late' => 'Late',
            ][$attendance->status] ?? $attendance->status;

            $color = [
                'present' => 'green',
                'absent_excused' => 'yellow',
                'absent_unexcused' => 'red',
                'late' => 'orange',
            ][$attendance->status] ?? 'gray';

            $events[] = [
                'id' => 'attendance-' . $attendance->id,
                'type' => 'attendance',
                'title' => 'Attendance marked: ' . $statusText,
                'description' => 'For ' . ($lesson->subject?->name ?? '') . ' lesson on ' . $lesson->date . ($attendance->markedBy ? ' by ' . $attendance->markedBy->name : ''),
                'date' => $attendance->marked_at ? $attendance->marked_at->format('Y-m-d') : $lesson->date,
                'time' => $attendance->marked_at ? $attendance->marked_at->format('H:i') : substr($lesson->start_time, 0, 5),
                'icon' => 'check-circle',
                'color' => $color,
                'metadata' => [
                    'attendance_id' => $attendance->id,
                    'status' => $attendance->status,
                    'note' => $attendance->note,
                    'subject' => $lesson->subject?->name,
                    'marked_by' => $attendance->markedBy?->name,
                ]
            ];
        }

        return $events;
    }
}
