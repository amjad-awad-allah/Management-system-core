<?php

namespace App\Modules\Nachhilfe\Application\Actions;

use App\Modules\Nachhilfe\Domain\Repositories\StudentRepositoryInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use Illuminate\Support\Str;

class CreateStudentAction
{
    public function __construct(
        private readonly StudentRepositoryInterface $repository
    ) {}

    public function execute(string $firstName, string $lastName, string $birthDate, string $school, int $grade): Student
    {
        $student = new Student();
        $student->id = (string) Str::ulid();
        $student->first_name = $firstName;
        $student->last_name = $lastName;
        $student->birth_date = $birthDate;
        $student->school = $school;
        $student->grade = $grade;

        $this->repository->save($student);

        return $student;
    }
}
