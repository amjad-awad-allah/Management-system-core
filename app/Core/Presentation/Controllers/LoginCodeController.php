<?php

namespace App\Core\Presentation\Controllers;

use App\Core\Models\User;
use App\Core\Models\UserLoginCode;
use App\Core\Models\UserMobileDevice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
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
        $currentUser = auth()->user();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isPrivileged = $currentUser->hasRole(['Super Admin', 'Admin', 'Center Manager'])
            || $currentUser->can('manage users');

        if (!$isPrivileged && $currentUser->id !== $userId) {
            return response()->json(['message' => 'Unauthorized access to user login code.'], 403);
        }

        $user = User::findOrFail($userId);

        if ($user->hasRole('Super Admin') && !$currentUser->hasRole('Super Admin')) {
            return response()->json(['message' => 'Unauthorized access to Super Admin login code.'], 403);
        }

        if ($user->hasRole('Admin') && !$currentUser->hasAnyRole(['Super Admin', 'Admin'])) {
            return response()->json(['message' => 'Unauthorized access to Admin login code.'], 403);
        }

        $loginCode = UserLoginCode::where('user_id', $userId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if (!$loginCode) {
            $code = $this->generateSecureCode();
            $loginCode = UserLoginCode::create([
                'id' => (string) Str::ulid(),
                'user_id' => $userId,
                'code' => $code,
                'status' => 'active',
                'expires_at' => now()->addHours(24),
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
        $currentUser = auth()->user();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $student = \App\Modules\Nachhilfe\Infrastructure\Models\Student::findOrFail($studentId);

        $isPrivileged = $currentUser->hasAnyRole(['Super Admin', 'Admin', 'Center Manager'])
            || $currentUser->can('manage students')
            || $currentUser->can('manage nachhilfe');

        $isSelf = $student->user_id && (string) $currentUser->id === (string) $student->user_id;

        if (!$isPrivileged && !$isSelf) {
            return response()->json(['message' => 'Unauthorized access to student login code.'], 403);
        }

        if (!$student->user_id) {
            $email = $student->parent_email ?: ('student_' . $student->id . '@nachhilfe.local');
            $existingUser = User::where('email', $email)->first();

            // Prevent administrative account linking and privilege escalation
            if ($existingUser && $existingUser->hasAnyRole(['Super Admin', 'Admin', 'Center Manager', 'Receptionist'])) {
                $email = 'student_' . $student->id . '@nachhilfe.local';
            }

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
        $currentUser = auth()->user();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $teacher = \App\Modules\Nachhilfe\Infrastructure\Models\Teacher::findOrFail($teacherId);

        $isPrivileged = $currentUser->hasAnyRole(['Super Admin', 'Admin', 'Center Manager'])
            || $currentUser->can('manage nachhilfe')
            || $currentUser->can('manage users');

        $isSelf = $teacher->user_id && (string) $currentUser->id === (string) $teacher->user_id;

        if (!$isPrivileged && !$isSelf) {
            return response()->json(['message' => 'Unauthorized access to teacher login code.'], 403);
        }

        if (!$teacher->user_id) {
            $email = $teacher->email ?: ('teacher_' . $teacher->id . '@nachhilfe.local');
            $existingUser = User::where('email', $email)->first();

            // Prevent administrative account linking and privilege escalation
            if ($existingUser && $existingUser->hasAnyRole(['Super Admin', 'Admin', 'Center Manager', 'Receptionist'])) {
                $email = 'teacher_' . $teacher->id . '@nachhilfe.local';
            }

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
        $admin = auth()->user();
        if (!$admin) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isPrivileged = $admin->hasRole(['Super Admin', 'Admin', 'Center Manager'])
            || $admin->can('manage users');

        if (!$isPrivileged && $admin->id !== $userId) {
            return response()->json(['message' => 'Unauthorized to regenerate login code.'], 403);
        }

        $request->validate([
            'admin_password' => 'required|string'
        ]);

        $admin = auth()->user();
        if (!Hash::check($request->admin_password, $admin->password)) {
            throw ValidationException::withMessages([
                'admin_password' => ['Admin password is incorrect.']
            ]);
        }

        $user = User::findOrFail($userId);

        if ($user->hasRole('Super Admin') && !$admin->hasRole('Super Admin')) {
            return response()->json(['message' => 'Unauthorized to regenerate Super Admin login code.'], 403);
        }

        if ($user->hasRole('Admin') && !$admin->hasAnyRole(['Super Admin', 'Admin'])) {
            return response()->json(['message' => 'Unauthorized to regenerate Admin login code.'], 403);
        }

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
            'expires_at' => now()->addHours(24),
            'created_by' => $admin->id
        ]);

        return response()->json([
            'message' => 'Login code and legacy sessions revoked, new active code generated successfully.',
            'login_code' => $loginCode
        ]);
    }

    /**
     * Revoke single device session.
     */
    public function revokeDevice(Request $request, string $deviceId): JsonResponse
    {
        $currentUser = auth()->user();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $device = UserMobileDevice::with('user')->findOrFail($deviceId);
        
        $isPrivileged = $currentUser->hasRole(['Super Admin', 'Admin', 'Center Manager'])
            || $currentUser->can('manage users');

        if ($device->user && $device->user->hasRole('Super Admin') && !$currentUser->hasRole('Super Admin')) {
            return response()->json(['message' => 'Unauthorized to revoke Super Admin device.'], 403);
        }

        if (!$isPrivileged && $device->user_id !== $currentUser->id) {
            return response()->json(['message' => 'Unauthorized to revoke this device.'], 403);
        }

        // Revoke the specific token
        if ($device->token_id) {
            $device->user->tokens()->where('id', $device->token_id)->delete();
        }

        $device->delete();

        return response()->json(['message' => 'Device logged out successfully.']);
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

        $authResult = DB::transaction(function () use ($request) {
            $loginCode = UserLoginCode::where('code', $request->code)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (!$loginCode) {
                return ['status' => 'invalid'];
            }

            if (!$loginCode->expires_at || $loginCode->expires_at->isPast()) {
                $loginCode->update(['status' => 'expired']);
                return ['status' => 'expired'];
            }

            $user = $loginCode->user;
            if (!$user) {
                return ['status' => 'user_not_found'];
            }

            // Deny mobile short code authentication for any user with administrative roles (prevent privilege escalation)
            if ($user->hasAnyRole(['Super Admin', 'Admin', 'Center Manager', 'Receptionist'])) {
                return ['status' => 'unauthorized_role'];
            }

            // Allowlist mobile authentication: only Student and Teacher permitted
            if (!$user->hasAnyRole(['Student', 'Teacher'])) {
                return ['status' => 'unauthorized_role'];
            }

            // Atomic single-use consumption: mark code used within the same transaction
            $loginCode->update([
                'status' => 'used',
                'last_used_at' => now(),
            ]);

            $tokenResult = $user->createToken('mobile_auth_token');

            UserMobileDevice::create([
                'id' => (string) Str::ulid(),
                'user_id' => $user->id,
                'device_name' => $request->device_name,
                'device_id' => $request->device_id,
                'token_id' => $tokenResult->accessToken->id
            ]);

            return [
                'status' => 'success',
                'access_token' => $tokenResult->plainTextToken,
                'user' => $user,
            ];
        });

        if ($authResult['status'] === 'expired') {
            throw ValidationException::withMessages([
                'code' => ['Scanned login code has expired. Please request a new code.']
            ]);
        }

        if ($authResult['status'] === 'unauthorized_role') {
            throw ValidationException::withMessages([
                'code' => ['This account is not authorized for mobile application login.']
            ]);
        }

        if ($authResult['status'] !== 'success') {
            throw ValidationException::withMessages([
                'code' => ['Scanned login code is invalid or has already been used.']
            ]);
        }

        $user = $authResult['user'];
        return response()->json([
            'access_token' => $authResult['access_token'],
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
