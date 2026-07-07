<?php

namespace App\Core\Auth\Contracts;

use App\Core\Models\User;

interface AuthenticationContract
{
    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array<string, mixed> $credentials
     */
    public function authenticate(array $credentials): bool;

    /**
     * Get the currently authenticated user.
     */
    public function user(): ?User;

    /**
     * Log the user out of the application.
     */
    public function logout(): void;
}
