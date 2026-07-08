<?php

namespace App\Modules\Nachhilfe\Infrastructure\Repositories;

use App\Modules\Nachhilfe\Domain\Repositories\TeacherRepositoryInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;

class EloquentTeacherRepository implements TeacherRepositoryInterface
{
    public function save(Teacher $teacher): void
    {
        $teacher->save();
    }

    public function findById(string $id): ?Teacher
    {
        return Teacher::find($id);
    }
}
