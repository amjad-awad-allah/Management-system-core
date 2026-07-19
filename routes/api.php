<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Core\Http\Controllers\AuthController;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        
        // System Management (Protected by Super Admin Gate implicitly or explicit permission)
        Route::apiResource('/users', \App\Core\Presentation\Controllers\UserController::class);
        Route::apiResource('/roles', \App\Core\Presentation\Controllers\RoleController::class);
        Route::get('/permissions', [\App\Core\Presentation\Controllers\RoleController::class, 'permissions']);
        Route::get('/audit-logs', [\App\Core\Presentation\Controllers\AuditLogController::class, 'index']);
        Route::get('/settings', [\App\Core\Presentation\Controllers\SettingController::class, 'index']);
        Route::post('/settings', [\App\Core\Presentation\Controllers\SettingController::class, 'update']);

        // Core Notification Center
        Route::get('/notifications', [\App\Core\Presentation\Controllers\NotificationCenterController::class, 'index']);
        Route::post('/notifications/{id}/read', [\App\Core\Presentation\Controllers\NotificationCenterController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [\App\Core\Presentation\Controllers\NotificationCenterController::class, 'markAllAsRead']);
        Route::get('/notifications/preferences', [\App\Core\Presentation\Controllers\NotificationCenterController::class, 'getPreferences']);
        Route::post('/notifications/preferences', [\App\Core\Presentation\Controllers\NotificationCenterController::class, 'updatePreferences']);

        // Mobile Login Codes & Session Management
        Route::get('/users/{id}/login-code', [\App\Core\Presentation\Controllers\LoginCodeController::class, 'show']);
        Route::get('/nachhilfe/students/{id}/login-code', [\App\Core\Presentation\Controllers\LoginCodeController::class, 'showStudentCode']);
        Route::get('/nachhilfe/teachers/{id}/login-code', [\App\Core\Presentation\Controllers\LoginCodeController::class, 'showTeacherCode']);
        Route::post('/users/{id}/login-code/regenerate', [\App\Core\Presentation\Controllers\LoginCodeController::class, 'regenerate']);
        Route::delete('/mobile/devices/{id}', [\App\Core\Presentation\Controllers\LoginCodeController::class, 'revokeDevice']);
    });

    // Mobile API endpoints
    Route::prefix('mobile')->group(function () {
        // Mobile Auth (public)
        Route::post('/login', [\App\Core\Http\Controllers\MobileAuthController::class, 'login']);
        Route::post('/auth/code', [\App\Core\Presentation\Controllers\LoginCodeController::class, 'loginByCode']);

        Route::middleware('auth:sanctum')->group(function () {
            // Mobile Auth (protected)
            Route::post('/logout', [\App\Core\Http\Controllers\MobileAuthController::class, 'logout']);
            Route::get('/user', [\App\Core\Http\Controllers\MobileAuthController::class, 'user']);

            // Teacher Mobile API
            Route::prefix('teacher')->group(function () {
                Route::get('/lessons', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherMobileController::class, 'lessons']);
                Route::post('/lessons/{id}/attendance', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherMobileController::class, 'markAttendance']);
                Route::get('/payrolls', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherMobileController::class, 'payrolls']);
                Route::get('/students/{id}/timeline', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherMobileController::class, 'studentTimeline']);
            });

            // Student Mobile API
            Route::prefix('student')->group(function () {
                Route::get('/lessons', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentMobileController::class, 'lessons']);
                Route::get('/subscriptions', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentMobileController::class, 'subscriptions']);
                Route::get('/packages', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentMobileController::class, 'packages']);
                Route::get('/invoices', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentMobileController::class, 'invoices']);
                Route::get('/timeline', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentMobileController::class, 'timeline']);
            });
        });
    });
});
