<?php

namespace App\Core\Authorization\Contracts;

use App\Core\Models\User;

interface AuthorizationContract
{
    /**
     * Determine if the given user has the given permission.
     */
    public function hasPermission(User $user, string $permission): bool;

    /**
     * Determine if the given user has the given role.
     */
    public function hasRole(User $user, string $role): bool;

    /**
     * Assign a role to the given user.
     */
    public function assignRole(User $user, string $role): void;

    /**
     * Revoke a role from the given user.
     */
    public function revokeRole(User $user, string $role): void;
}
