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
            'student_id' => $this->student_id,
            'teacher_id' => $this->teacher_id,
            'subject_id' => $this->subject_id,
            'scheduled_at' => $this->scheduled_at,
            'duration_minutes' => (int) $this->duration_minutes,
            'status' => $this->status,
            'price' => (float) $this->price,
            'created_at' => $this->created_at,
        ];
    }
}
