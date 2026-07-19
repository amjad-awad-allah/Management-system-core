<?php

namespace App\Core\Presentation\Controllers;

use App\Core\Models\User;
use App\Core\Models\UserLoginCode;
use App\Core\Models\UserMobileDevice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginCodeController extends Controller
{
    /**
     * Get or initialize login code and active devices for a user.
     */
    public function show(string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        
        $loginCode = UserLoginCode::where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if (!$loginCode) {
            $code = $this->generateSecureCode();
            $loginCode = UserLoginCode::create([
                'id' => (string) Str::ulid(),
                'user_id' => $userId,
                'code' => $code,
                'status' => 'active',
                'created_by' => auth()->id()
            ]);
        }

        $devices = UserMobileDevice::where('user_id', $userId)->get();

        return response()->json([
            'login_code' => $loginCode,
            'devices' => $devices
        ]);
    }

    /**
     * Get or initialize login code for a student (auto-creates linked User if missing).
     */
    public function showStudentCode(string $studentId): JsonResponse
    {
        $student = \App\Modules\Nachhilfe\Infrastructure\Models\Student::findOrFail($studentId);
        
        if (!$student->user_id) {
            $email = $student->parent_email ?: ('student_' . $student->id . '@nachhilfe.local');
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $student->parent_name ?: ($student->first_name . ' ' . $student->last_name),
                    'password' => Hash::make(Str::random(16))
                ]
            );
            
            $role = \App\Core\Models\Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
            }
            
            $student->update(['user_id' => $user->id]);
        }
        
        return $this->show($student->user_id);
    }

    /**
     * Get or initialize login code for a teacher (auto-creates linked User if missing).
     */
    public function showTeacherCode(string $teacherId): JsonResponse
    {
        $teacher = \App\Modules\Nachhilfe\Infrastructure\Models\Teacher::findOrFail($teacherId);
        
        if (!$teacher->user_id) {
            $email = $teacher->email ?: ('teacher_' . $teacher->id . '@nachhilfe.local');
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $teacher->name,
                    'password' => Hash::make(Str::random(16))
                ]
            );
            
            $role = \App\Core\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
            }
            
            $teacher->update(['user_id' => $user->id]);
        }
        
        return $this->show($teacher->user_id);
    }

    /**
     * Regenerate code: revokes old sessions, old codes and registers a new active code.
     */
    public function regenerate(Request $request, string $userId): JsonResponse
    {
        $request->validate([
            'admin_password' => 'required|string'
        ]);

        $admin = auth()->user();
        if (!Hash::check($request->admin_password, $admin->password)) {
            throw ValidationException::withMessages([
                'admin_password' => ['كلمة المرور الخاصة بالإدارة غير صحيحة.']
            ]);
        }

        $user = User::findOrFail($userId);

        // 1. Revoke existing active codes
        UserLoginCode::where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'revoked']);

        // 2. Revoke all active sanctum tokens for this user
        $user->tokens()->delete();

        // 3. Clear all mobile device records
        UserMobileDevice::where('user_id', $userId)->delete();

        // 4. Create new secure active login code
        $code = $this->generateSecureCode();
        $loginCode = UserLoginCode::create([
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'code' => $code,
            'status' => 'active',
            'created_by' => $admin->id
        ]);

        return response()->json([
            'message' => 'تم إلغاء الرمز وجلسات الأجهزة القديمة وتوليد رمز جديد بنجاح.',
            'login_code' => $loginCode
        ]);
    }

    /**
     * Revoke single device session.
     */
    public function revokeDevice(Request $request, string $deviceId): JsonResponse
    {
        $device = UserMobileDevice::findOrFail($deviceId);
        
        // Revoke the specific token
        if ($device->token_id) {
            $device->user->tokens()->where('id', $device->token_id)->delete();
        }

        $device->delete();

        return response()->json(['message' => 'تم تسجيل خروج الجهاز بنجاح.']);
    }

    /**
     * Mobile login endpoint using QR/Short Code.
     */
    public function loginByCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'device_name' => 'required|string',
            'device_id' => 'nullable|string'
        ]);

        $loginCode = UserLoginCode::where('code', $request->code)
            ->where('status', 'active')
            ->first();

        if (!$loginCode) {
            throw ValidationException::withMessages([
                'code' => ['رمز الدخول الممسوح غير صحيح أو تم إلغاؤه.']
            ]);
        }

        // Update last used timestamp
        $loginCode->update(['last_used_at' => now()]);

        $user = $loginCode->user;
        $tokenResult = $user->createToken('mobile_auth_token');

        // Register device session
        UserMobileDevice::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'device_name' => $request->device_name,
            'device_id' => $request->device_id,
            'token_id' => $tokenResult->accessToken->id
        ]);

        return response()->json([
            'access_token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()
            ]
        ]);
    }

    /**
     * Helper to generate a secure random alphanumeric code excluding ambiguous characters.
     */
    private function generateSecureCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
}
