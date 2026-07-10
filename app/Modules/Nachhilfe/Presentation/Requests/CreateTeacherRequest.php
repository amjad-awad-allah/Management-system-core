<?php

namespace App\Modules\Nachhilfe\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'phone' => 'nullable|string|max:255',
            'hourly_rate' => 'required|numeric|min:0',
            'subject_ids' => 'sometimes|array',
            'subject_ids.*' => 'string|exists:subjects,id',
        ];
    }
}
