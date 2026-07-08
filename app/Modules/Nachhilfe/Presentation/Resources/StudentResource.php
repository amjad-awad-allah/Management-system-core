<?php

namespace App\Modules\Nachhilfe\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Nachhilfe\Infrastructure\Models\Student
 */
class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            // Exclude birth_date for privacy unless explicitly needed
            'school' => $this->resource->school,
            'grade' => $this->resource->grade,
            'parent_phone_1' => $this->resource->parent_phone_1,
            'parent_phone_2' => $this->resource->parent_phone_2,
        ];
    }
}
