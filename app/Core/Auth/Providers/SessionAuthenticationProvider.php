<?php

namespace App\Core\Auth\Providers;

use App\Core\Auth\Contracts\AuthenticationContract;
use App\Core\Models\User;
use Illuminate\Support\Facades\Auth;

class SessionAuthenticationProvider implements AuthenticationContract
{
    /**
     * @param array<string, mixed> $credentials
     */
    public function authenticate(array $credentials): bool
    {
        return Auth::attempt($credentials);
    }

    public function user(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();
        return $user;
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
