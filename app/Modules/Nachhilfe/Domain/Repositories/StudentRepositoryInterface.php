<?php

namespace App\Modules\Nachhilfe\Domain\Repositories;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;

interface StudentRepositoryInterface
{
    public function save(Student $student): void;
    public function findById(string $id): ?Student;
}
