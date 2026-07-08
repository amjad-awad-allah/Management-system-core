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
            'name' => $this->resource->name,
            'qualification' => $this->resource->qualification,
            'hourly_rate' => (float) $this->resource->hourly_rate,
        ];
    }
}
