<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        /** @var string $email */
        $email = $validated['email'];
        /** @var string $password */
        $password = $validated['password'];

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email address or password.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'preferred_locale' => $user->preferredLocale(),
                'roles' => $user->getRoleNames()
            ]
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Successfully logged out.'
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'preferred_locale' => $user->preferredLocale(),
            'roles' => $user->getRoleNames()
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        /** @var array<string, mixed> $supportedConfig */
        $supportedConfig = config('localization.supported_locales', ['de' => [], 'en' => []]);
        $supported = array_keys($supportedConfig);

        $validated = $request->validate([
            'preferred_locale' => 'required|string|in:' . implode(',', $supported),
        ]);

        $user->update([
            'preferred_locale' => $validated['preferred_locale'],
        ]);

        return response()->json([
            'message' => 'Preferences updated successfully.',
            'preferred_locale' => $validated['preferred_locale'],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'preferred_locale' => $user->preferredLocale(),
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        /** @var string $currentPassword */
        $currentPassword = $validated['current_password'];
        /** @var string $newPassword */
        $newPassword = $validated['new_password'];

        if (! Hash::check($currentPassword, (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}
