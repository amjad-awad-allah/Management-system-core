<?php

namespace App\Modules\Nachhilfe\Infrastructure\Repositories;

use App\Modules\Nachhilfe\Domain\Repositories\StudentRepositoryInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;

class EloquentStudentRepository implements StudentRepositoryInterface
{
    public function save(Student $student): void
    {
        $student->save();
    }

    public function findById(string $id): ?Student
    {
        return Student::find($id);
    }
}
