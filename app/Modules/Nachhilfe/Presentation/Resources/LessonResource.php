<?php

namespace App\Modules\Nachhilfe\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'room_id' => $this->room_id,
            'subject_id' => $this->subject_id,
            'schedule_template_id' => $this->schedule_template_id,
            'teacher' => $this->whenLoaded('teacher', fn() => ['id' => $this->teacher->id, 'name' => $this->teacher->name]),
            'subject' => $this->whenLoaded('subject', fn() => ['id' => $this->subject->id, 'name' => $this->subject->name]),
            'type' => $this->type,
            'date' => $this->date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'notes' => $this->notes,
            'students' => $this->whenLoaded('students', function () {
                return $this->students->map(function ($student) {
                    $attendanceStatus = null;
                    if ($this->relationLoaded('lessonStudents')) {
                        $ls = $this->lessonStudents->firstWhere('student_id', $student->id);
                        $attendanceStatus = $ls?->attendance?->status;
                    }

                    return [
                        'id' => $student->id,
                        'pivot_id' => $student->pivot->id,
                        'name' => $student->first_name . ' ' . $student->last_name,
                        'package_id' => $student->pivot->package_id,
                        'hours_consumed' => $student->pivot->hours_consumed,
                        'attendance' => $attendanceStatus,
                    ];
                });
            }),
            'created_at' => $this->created_at,
        ];
    }
}
