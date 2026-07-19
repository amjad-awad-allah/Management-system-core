<?php

namespace App\Modules\Nachhilfe\Application\Queries;

use App\Modules\Nachhilfe\Infrastructure\Models\Attendance;
use Illuminate\Support\Collection;

class GetStundennachweisDataQuery
{
    /**
     * Get attendance records for Stundennachweis.
     *
     * @param string $studentId
     * @param string $month (format: YYYY-MM)
     * @param string|null $subjectId
     * @return Collection
     */
    public function execute(string $studentId, string $month, ?string $subjectId = null): Collection
    {
        $query = Attendance::whereHas('lessonStudent', function ($q) use ($studentId) {
            $q->where('student_id', $studentId);
        })
        ->with([
            'lessonStudent.lesson.teacher',
            'lessonStudent.lesson.subject',
            'lessonStudent.lesson.room'
        ])
        ->whereIn('status', ['present', 'Present', 'late', 'Late']);

        // Filter by month (lessons.date matches YYYY-MM-DD)
        $query->whereHas('lessonStudent.lesson', function ($q) use ($month) {
            $q->where('date', 'like', "{$month}-%");
        });

        if ($subjectId) {
            $query->whereHas('lessonStudent.lesson', function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            });
        }

        // Sort by date and start_time
        return $query->get()->sortBy(function ($attendance) {
            $lesson = $attendance->lessonStudent?->lesson;
            return $lesson ? ($lesson->date . ' ' . $lesson->start_time) : '';
        })->values();
    }
}
