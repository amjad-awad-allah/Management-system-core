<?php

namespace App\Modules\Nachhilfe\Application\Actions;

use App\Modules\Nachhilfe\Domain\Repositories\TeacherRepositoryInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Core\Models\User;
use App\Core\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class CreateTeacherAction
{
    public function __construct(
        private readonly TeacherRepositoryInterface $repository
    ) {}

    public function execute(string $name, string $qualification, float $hourlyRate, ?string $email = null, ?string $phone = null, array $subjectIds = []): Teacher
    {
        $userId = null;

        if ($email) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password123') // Default password
                ]
            );
            
            if (!$user->hasRole('Teacher')) {
                $role = Role::firstOrCreate(['name' => 'Teacher']);
                $user->assignRole($role);
            }

            $userId = $user->id;
        }

        $teacher = new Teacher();
        $teacher->id = (string) Str::ulid();
        $teacher->user_id = $userId;
        $teacher->name = $name;
        $teacher->qualification = $qualification;
        $teacher->hourly_rate = $hourlyRate;
        $teacher->email = $email;
        $teacher->phone = $phone;

        $this->repository->save($teacher);

        if (!empty($subjectIds)) {
            $teacher->subjects()->sync($subjectIds);
        }

        return $teacher;
    }
}
