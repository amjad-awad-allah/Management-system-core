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
            'birth_date' => $this->resource->birth_date,
            'school' => $this->resource->school,
            'grade' => $this->resource->grade,
            'parent_name' => $this->resource->parent_name,
            'parent_email' => $this->resource->parent_email,
            'parent_phone_1' => $this->resource->parent_phone_1,
            'parent_phone_2' => $this->resource->parent_phone_2,
            'subjects' => $this->whenLoaded('subjects'),
            'teachers' => $this->whenLoaded('teachers', function() {
                return $this->resource->teachers->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                ]);
            }),
        ];
    }
}
