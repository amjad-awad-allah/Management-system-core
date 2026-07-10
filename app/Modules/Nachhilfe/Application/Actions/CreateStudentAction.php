<?php

namespace App\Modules\Nachhilfe\Application\Actions;

use App\Modules\Nachhilfe\Domain\Repositories\StudentRepositoryInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Core\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Core\Models\Role;

class CreateStudentAction
{
    public function __construct(
        private readonly StudentRepositoryInterface $repository
    ) {}

    public function execute(string $firstName, string $lastName, string $birthDate, string $school, int $grade, string $parentPhone1, ?string $parentPhone2 = null, ?string $parentName = null, ?string $parentEmail = null, array $subjectIds = []): Student
    {
        $userId = null;

        if ($parentEmail) {
            $user = User::firstOrCreate(
                ['email' => $parentEmail],
                [
                    'name' => $parentName ?? ($firstName . ' Parent'),
                    'password' => Hash::make('password123') // Default password
                ]
            );
            
            if (!$user->hasRole('Student')) {
                // Ensure Student role exists or create it
                $role = Role::firstOrCreate(['name' => 'Student']);
                $user->assignRole($role);
            }

            $userId = $user->id;
        }

        $student = new Student();
        $student->id = (string) Str::ulid();
        $student->user_id = $userId;
        $student->first_name = $firstName;
        $student->last_name = $lastName;
        $student->birth_date = $birthDate;
        $student->school = $school;
        $student->grade = $grade;
        $student->parent_phone_1 = $parentPhone1;
        $student->parent_phone_2 = $parentPhone2;
        $student->parent_name = $parentName;
        $student->parent_email = $parentEmail;

        $this->repository->save($student);

        if (!empty($subjectIds)) {
            $student->subjects()->sync($subjectIds);
        }

        return $student;
    }
}
