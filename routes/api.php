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
});
