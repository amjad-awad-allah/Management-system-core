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
            'user_id' => 'required|string|ulid',
            'name' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
            'hourly_rate' => 'required|numeric|min:0',
        ];
    }
}
