<?php

namespace App\Modules\Nachhilfe\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\Nachhilfe\Infrastructure\Models\LessonModel::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'string', 'ulid', 'exists:students,id'],
            'teacher_id' => ['required', 'string', 'ulid', 'exists:teachers,id'],
            'subject_id' => ['required', 'string', 'ulid', 'exists:subjects,id'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:240'],
        ];
    }
}
