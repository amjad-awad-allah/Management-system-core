<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Core\Http\Controllers\AuthController;

use Illuminate\Support\Facades\Broadcast;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::get('/settings', [\App\Core\Presentation\Controllers\SettingController::class, 'index']);
    Route::get('/settings/logo', [\App\Core\Presentation\Controllers\SettingController::class, 'getLogo']);
    Broadcast::routes(['middleware' => ['auth:sanctum']]);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::patch('/user/preferences', [AuthController::class, 'updatePreferences']);
        Route::patch('/user/profile', [AuthController::class, 'updateProfile']);
        Route::patch('/user/password', [AuthController::class, 'updatePassword']);
        
        // System Management (Protected by Super Admin Gate implicitly or explicit permission)
        Route::apiResource('/users', \App\Core\Presentation\Controllers\UserController::class);
        Route::apiResource('/roles', \App\Core\Presentation\Controllers\RoleController::class);
        Route::get('/permissions', [\App\Core\Presentation\Controllers\RoleController::class, 'permissions']);
        Route::get('/audit-logs', [\App\Core\Presentation\Controllers\AuditLogController::class, 'index']);
        Route::get('/system-logs', [\App\Core\Presentation\Controllers\SystemLogController::class, 'index']);
        Route::get('/system-logs/export', [\App\Core\Presentation\Controllers\SystemLogController::class, 'export']);
        Route::post('/system-logs/clear', [\App\Core\Presentation\Controllers\SystemLogController::class, 'clear']);
        Route::post('/settings', [\App\Core\Presentation\Controllers\SettingController::class, 'update']);
        Route::post('/settings/logo', [\App\Core\Presentation\Controllers\SettingController::class, 'uploadLogo']);
        Route::delete('/settings/logo', [\App\Core\Presentation\Controllers\SettingController::class, 'deleteLogo']);

        // Core Notification Center & Outbox DLQ Admin
        Route::get('/notifications', [\App\Core\Presentation\Controllers\NotificationCenterController::class, 'index']);
        Route::post('/notifications/{id}/read', [\App\Core\Presentation\Controllers\NotificationCenterController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [\App\Core\Presentation\Controllers\NotificationCenterController::class, 'markAllAsRead']);
        Route::get('/notifications/preferences', [\App\Core\Presentation\Controllers\NotificationCenterController::class, 'getPreferences']);
        Route::post('/notifications/preferences', [\App\Core\Presentation\Controllers\NotificationCenterController::class, 'updatePreferences']);
        Route::get('/admin/notifications/outbox', [\App\Core\Presentation\Controllers\AdminNotificationController::class, 'index']);
        Route::post('/admin/notifications/outbox/{id}/retry', [\App\Core\Presentation\Controllers\AdminNotificationController::class, 'retry']);

        // Mobile Login Codes & Session Management
        Route::get('/users/{id}/login-code', [\App\Core\Presentation\Controllers\LoginCodeController::class, 'show']);
        Route::get('/nachhilfe/students/{id}/login-code', [\App\Core\Presentation\Controllers\LoginCodeController::class, 'showStudentCode']);
        Route::get('/nachhilfe/teachers/{id}/login-code', [\App\Core\Presentation\Controllers\LoginCodeController::class, 'showTeacherCode']);
        Route::post('/users/{id}/login-code/regenerate', [\App\Core\Presentation\Controllers\LoginCodeController::class, 'regenerate']);
        Route::delete('/mobile/devices/{id}', [\App\Core\Presentation\Controllers\LoginCodeController::class, 'revokeDevice']);

        // Interactive Onboarding Guided Tours
        Route::prefix('onboarding')->group(function () {
            Route::get('/{tourKey?}', [\App\Core\Presentation\Controllers\OnboardingController::class, 'show']);
            Route::post('/{tourKey}/start', [\App\Core\Presentation\Controllers\OnboardingController::class, 'start']);
            Route::post('/{tourKey}/step', [\App\Core\Presentation\Controllers\OnboardingController::class, 'step']);
            Route::post('/{tourKey}/complete', [\App\Core\Presentation\Controllers\OnboardingController::class, 'complete']);
            Route::post('/{tourKey}/skip', [\App\Core\Presentation\Controllers\OnboardingController::class, 'skip']);
            Route::post('/{tourKey}/reset', [\App\Core\Presentation\Controllers\OnboardingController::class, 'reset']);
        });

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

