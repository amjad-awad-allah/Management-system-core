<?php

namespace App\Modules\Nachhilfe\Presentation\Policies;

use Illuminate\Contracts\Auth\Authenticatable as User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        // Require specific permission or just true for now
        // return $user->hasPermissionTo('create subjects');
        return true;
    }
}
