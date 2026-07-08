<?php

namespace App\Modules\Nachhilfe\Domain\Repositories;

use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;

interface TeacherRepositoryInterface
{
    public function save(Teacher $teacher): void;
    public function findById(string $id): ?Teacher;
}
