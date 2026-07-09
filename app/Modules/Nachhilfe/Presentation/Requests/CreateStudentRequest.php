<?php

namespace App\Modules\Nachhilfe\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Use Gates/Policies in the controller if needed
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'required|date_format:Y-m-d',
            'school' => 'required|string|max:255',
            'grade' => 'required|integer|min:1|max:13',
            'parent_phone_1' => 'required|string|max:20',
            'parent_phone_2' => 'nullable|string|max:20',
            'subject_ids' => 'sometimes|array',
            'subject_ids.*' => 'string|exists:subjects,id',
        ];
    }
}
