<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controller;

class MobileAuthController extends Controller
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
                'email' => ['البريد الإلكتروني أو كلمة المرور غير صحيحة.'],
            ]);
        }

        // Only allow Students and Teachers to login via Mobile API
        if (!$user->hasRole('Student') && !$user->hasRole('Teacher')) {
            throw ValidationException::withMessages([
                'email' => ['عذراً، هذا الحساب غير مصرح له بتسجيل الدخول للتطبيق.'],
            ]);
        }

        $token = $user->createToken('mobile_auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            \App\Core\Models\UserMobileDevice::where('token_id', $token->id)->delete();
            $token->delete();
        }

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح.'
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()
        ]);
    }
}
