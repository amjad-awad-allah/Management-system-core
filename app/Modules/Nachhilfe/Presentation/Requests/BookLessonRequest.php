<?php

namespace App\Modules\Nachhilfe\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['Super Admin', 'Center Manager', 'Admin', 'Receptionist']) || $user->can('manage lessons')) {
            return true;
        }

        $teacher = \App\Modules\Nachhilfe\Infrastructure\Models\Teacher::where('user_id', $user->id)->first();
        if ($teacher && $teacher->id === $this->input('teacher_id')) {
            $lesson = $this->route('lesson');
            if ($lesson instanceof \App\Modules\Nachhilfe\Infrastructure\Models\Lesson) {
                return $lesson->teacher_id === $teacher->id;
            }
            return true;
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'string', 'ulid', 'exists:teachers,id'],
            'room_id' => ['required', 'string', 'ulid', 'exists:rooms,id'],
            'subject_id' => ['required', 'string', 'ulid', 'exists:subjects,id'],
            'type' => ['required', 'string', 'in:individual,group'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'notes' => ['nullable', 'string'],
            'students' => ['required', 'array', 'min:1'],
            'students.*.student_id' => ['required', 'string', 'ulid', 'exists:students,id'],
            'students.*.package_id' => ['nullable', 'string', 'ulid', 'exists:packages,id'],
            'recurrence_pattern' => ['nullable', 'string', 'in:none,daily,weekly,monthly'],
            'recurrence_end_date' => ['required_if:recurrence_pattern,daily,weekly,monthly', 'nullable', 'date_format:Y-m-d', 'after:date'],
        ];
    }
}
