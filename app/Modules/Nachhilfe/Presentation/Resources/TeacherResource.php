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
        $user = $request->user();
        $isSelf = $user && $this->resource->user_id && (string) $user->id === (string) $this->resource->user_id;
        $canManageFinancials = $user && (
            $user->hasRole(['Super Admin', 'Center Manager']) ||
            $user->can('manage nachhilfe') ||
            $user->can('manage billing')
        );
        $canManageStaff = $user && (
            $user->hasRole(['Super Admin', 'Center Manager', 'Receptionist']) ||
            $user->can('manage nachhilfe') ||
            $user->can('manage lessons') ||
            $user->can('manage students')
        );

        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'phone' => ($isSelf || $canManageStaff) ? $this->resource->phone : null,
            'status' => $this->resource->status,
            'qualification' => $this->resource->qualification,
            'hourly_rate' => ($isSelf || $canManageFinancials) ? ($this->resource->hourly_rate !== null ? (float) $this->resource->hourly_rate : null) : null,
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
