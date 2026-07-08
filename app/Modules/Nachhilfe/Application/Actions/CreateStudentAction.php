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

    public function execute(string $firstName, string $lastName, string $birthDate, string $school, int $grade, string $parentPhone1, ?string $parentPhone2 = null): Student
    {
        $student = new Student();
        $student->id = (string) Str::ulid();
        $student->first_name = $firstName;
        $student->last_name = $lastName;
        $student->birth_date = $birthDate;
        $student->school = $school;
        $student->grade = $grade;
        $student->parent_phone_1 = $parentPhone1;
        $student->parent_phone_2 = $parentPhone2;

        $this->repository->save($student);

        return $student;
    }
}
