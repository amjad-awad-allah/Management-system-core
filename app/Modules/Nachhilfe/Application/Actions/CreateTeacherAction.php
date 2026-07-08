<?php

namespace App\Modules\Nachhilfe\Application\Actions;

use App\Modules\Nachhilfe\Domain\Repositories\TeacherRepositoryInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use Illuminate\Support\Str;

class CreateTeacherAction
{
    public function __construct(
        private readonly TeacherRepositoryInterface $repository
    ) {}

    public function execute(string $userId, string $name, string $qualification, float $hourlyRate): Teacher
    {
        $teacher = new Teacher();
        $teacher->id = (string) Str::ulid();
        $teacher->user_id = $userId;
        $teacher->name = $name;
        $teacher->qualification = $qualification;
        $teacher->hourly_rate = $hourlyRate;

        $this->repository->save($teacher);

        return $teacher;
    }
}
