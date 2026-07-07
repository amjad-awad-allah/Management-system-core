<?php

namespace App\Modules\Nachhilfe\Domain\Repositories;

use App\Modules\Nachhilfe\Domain\Entities\Lesson;

interface LessonRepositoryInterface
{
    public function save(Lesson $lesson): void;
}
