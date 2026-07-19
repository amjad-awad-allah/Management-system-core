<?php

namespace App\Modules\Nachhilfe\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Nachhilfe\Infrastructure\Models\Teacher
 */
class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'status' => $this->resource->status,
            'qualification' => $this->resource->qualification,
            'hourly_rate' => (float) $this->resource->hourly_rate,
            'subjects' => $this->whenLoaded('subjects'),
            'students' => $this->whenLoaded('students', function() {
                return $this->resource->students->map(fn($s) => [
                    'id' => $s->id,
                    'first_name' => $s->first_name,
                    'last_name' => $s->last_name,
                ]);
            }),
        ];
    }
}
