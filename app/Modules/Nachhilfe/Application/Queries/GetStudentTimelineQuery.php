<?php

namespace App\Modules\Nachhilfe\Application\Queries;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Application\Services\StudentTimelineBuilder;
use Illuminate\Support\Facades\Cache;

class GetStudentTimelineQuery
{
    public function __construct(private readonly StudentTimelineBuilder $builder) {}

    public function execute(Student $student, $user, int $page = 1, int $perPage = 15, ?string $filterType = null): array
    {
        $roleSuffix = 'student';
        if ($user->hasRole('Admin') || $user->hasRole('Super Admin')) {
            $roleSuffix = 'admin';
        } elseif ($user->hasRole('Teacher')) {
            $roleSuffix = 'teacher';
        }

        $version = Cache::get("student:{$student->id}:timeline_version", 0);
        $cacheKey = "student:{$student->id}:timeline:v{$version}:role_{$roleSuffix}";
        
        $events = Cache::remember($cacheKey, 60, function () use ($student, $user) {
            return $this->builder->build($student, $user);
        });

        // Apply type filtering if requested
        if ($filterType) {
            $events = array_values(array_filter($events, fn($e) => $e['type'] === $filterType));
        }

        $total = count($events);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($events, $offset, $perPage);

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage)
            ]
        ];
    }
}
