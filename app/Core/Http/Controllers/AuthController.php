<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
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

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out.'
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'preferred_locale' => $user->preferredLocale(),
            'roles' => $user->getRoleNames()
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $supported = array_keys(config('localization.supported_locales', ['de' => [], 'en' => []]));
        $validated = $request->validate([
            'preferred_locale' => 'required|string|in:' . implode(',', $supported),
        ]);

        $request->user()->update([
            'preferred_locale' => $validated['preferred_locale'],
        ]);

        return response()->json([
            'message' => 'Preferences updated successfully.',
            'preferred_locale' => $validated['preferred_locale'],
        ]);
    }
}
