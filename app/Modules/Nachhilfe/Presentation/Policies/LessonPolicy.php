<?php

namespace App\Modules\Nachhilfe\Presentation\Policies;

use Illuminate\Contracts\Auth\Authenticatable as User;

class LessonPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
