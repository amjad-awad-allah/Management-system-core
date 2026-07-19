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

        // ── Admin Chat & Messaging ─────────────────────────────────────────────
        Route::prefix('nachhilfe/chat')->group(function () {
            // Channels
            Route::get('/channels', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'indexChannels']);
            Route::post('/channels', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'storeChannel']);
            Route::get('/channels/archived', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'archivedChannels']);
            Route::get('/channels/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'showChannel']);
            Route::delete('/channels/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'destroyChannel']);
            Route::post('/channels/{id}/read', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'markRead']);
            // Messages
            Route::get('/channels/{id}/messages', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'indexMessages']);
            Route::post('/channels/{id}/messages', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'storeMessage']);
            Route::delete('/messages/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'destroyMessage']);
            Route::get('/messages/{id}/attachment', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'downloadAttachment']);
            // Surveys
            Route::get('/surveys', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'indexSurveys']);
            Route::post('/surveys', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'storeSurvey']);
            Route::get('/surveys/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'showSurvey']);
            Route::get('/surveys/{id}/responses', [\App\Modules\Nachhilfe\Presentation\Controllers\ChatController::class, 'surveyResponses']);
        });
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

                // Teacher Chat
                Route::prefix('chat')->group(function () {
                    Route::get('/channels', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherChatController::class, 'indexChannels']);
                    Route::post('/channels', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherChatController::class, 'storeChannel']);
                    Route::get('/channels/{id}/messages', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherChatController::class, 'indexMessages']);
                    Route::post('/channels/{id}/messages', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherChatController::class, 'storeMessage']);
                    Route::post('/channels/{id}/read', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherChatController::class, 'markRead']);
                    Route::get('/messages/{id}/attachment', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherChatController::class, 'downloadAttachment']);
                    // Surveys
                    Route::get('/surveys', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherChatController::class, 'indexSurveys']);
                    Route::post('/surveys', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherChatController::class, 'storeSurvey']);
                    Route::get('/surveys/{id}/responses', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherChatController::class, 'surveyResponses']);
                });
            });

            // Student Mobile API
            Route::prefix('student')->group(function () {
                Route::get('/lessons', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentMobileController::class, 'lessons']);
                Route::get('/subscriptions', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentMobileController::class, 'subscriptions']);
                Route::get('/packages', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentMobileController::class, 'packages']);
                Route::get('/invoices', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentMobileController::class, 'invoices']);
                Route::get('/timeline', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentMobileController::class, 'timeline']);

                // Student Chat
                Route::prefix('chat')->group(function () {
                    Route::get('/channels', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentChatController::class, 'indexChannels']);
                    Route::get('/channels/{id}/messages', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentChatController::class, 'indexMessages']);
                    Route::post('/channels/{id}/messages', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentChatController::class, 'storeMessage']);
                    Route::post('/channels/{id}/read', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentChatController::class, 'markRead']);
                    Route::get('/messages/{id}/attachment', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentChatController::class, 'downloadAttachment']);
                    // Surveys
                    Route::get('/surveys', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentChatController::class, 'indexSurveys']);
                    Route::post('/surveys/{id}/respond', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentChatController::class, 'submitSurveyResponse']);
                    Route::get('/surveys/{id}/responses', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentChatController::class, 'surveyResponses']);
                });
            });
        });
    });
});

