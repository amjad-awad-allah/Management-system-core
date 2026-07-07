<?php

namespace App\Core\Authorization\Providers;

use App\Core\Authorization\Contracts\AuthorizationContract;
use App\Core\Models\User;

class SpatieAuthorizationProvider implements AuthorizationContract
{
    public function hasPermission(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission);
    }

    public function hasRole(User $user, string $role): bool
    {
        return $user->hasRole($role);
    }

    public function assignRole(User $user, string $role): void
    {
        $user->assignRole($role);
    }

    public function revokeRole(User $user, string $role): void
    {
        $user->removeRole($role);
    }
}
