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
    });

    // Mobile API endpoints
    Route::prefix('mobile')->group(function () {
        // Mobile Auth (public)
        Route::post('/login', [\App\Core\Http\Controllers\MobileAuthController::class, 'login']);

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
                Route::get('/invoices', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentMobileController::class, 'invoices']);
                Route::get('/timeline', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentMobileController::class, 'timeline']);
            });
        });
    });
});
