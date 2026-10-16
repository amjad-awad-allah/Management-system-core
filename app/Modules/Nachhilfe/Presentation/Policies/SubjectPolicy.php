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
        return $user->hasAnyRole(['Super Admin', 'Center Manager', 'Admin']) || $user->can('manage nachhilfe');
    }

    public function update(User $user, \App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel $subject): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Center Manager', 'Admin']) || $user->can('manage nachhilfe');
    }

    public function delete(User $user, \App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel $subject): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Center Manager', 'Admin']) || $user->can('manage nachhilfe');
    }
}
