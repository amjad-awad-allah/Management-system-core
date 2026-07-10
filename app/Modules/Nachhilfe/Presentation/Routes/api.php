<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Nachhilfe\Presentation\Controllers\SubjectController;
use App\Modules\Nachhilfe\Presentation\Controllers\LessonController;

Route::prefix('api/v1/nachhilfe')
    ->middleware(['api', 'module.active:Nachhilfe', 'auth:sanctum'])
    ->group(function () {
        
        Route::get('/dashboard/stats', [\App\Modules\Nachhilfe\Presentation\Controllers\DashboardController::class, 'stats']);
        
        Route::get('/subjects', [SubjectController::class, 'index']);
        Route::post('/subjects', [SubjectController::class, 'store']);
        Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);

        Route::get('/rooms', [\App\Modules\Nachhilfe\Presentation\Controllers\RoomController::class, 'index']);
        Route::post('/rooms', [\App\Modules\Nachhilfe\Presentation\Controllers\RoomController::class, 'store']);
        Route::put('/rooms/{room}', [\App\Modules\Nachhilfe\Presentation\Controllers\RoomController::class, 'update']);
        Route::delete('/rooms/{room}', [\App\Modules\Nachhilfe\Presentation\Controllers\RoomController::class, 'destroy']);

        Route::get('/lessons', [LessonController::class, 'index']);
        Route::post('/lessons', [LessonController::class, 'store']);
        
        Route::post('/lesson-students/{lessonStudentId}/attendance', [\App\Modules\Nachhilfe\Presentation\Controllers\AttendanceController::class, 'store']);

        Route::get('/students', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentController::class, 'index']);
        Route::post('/students', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentController::class, 'store']);
        Route::get('/students/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentController::class, 'show']);
        Route::put('/students/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentController::class, 'update']);
        Route::delete('/students/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentController::class, 'destroy']);

        Route::get('/teachers', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherController::class, 'index']);
        Route::post('/teachers', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherController::class, 'store']);
        Route::get('/teachers/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherController::class, 'show']);
        Route::put('/teachers/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherController::class, 'update']);
        Route::delete('/teachers/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherController::class, 'destroy']);

        Route::get('/packages', [\App\Modules\Nachhilfe\Presentation\Controllers\PackageController::class, 'index']);
        Route::post('/packages', [\App\Modules\Nachhilfe\Presentation\Controllers\PackageController::class, 'store']);
        Route::get('/packages/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\PackageController::class, 'show']);
        Route::put('/packages/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\PackageController::class, 'update']);
        Route::delete('/packages/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\PackageController::class, 'destroy']);

        Route::get('/students/{id}/packages', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentPackageController::class, 'index']);
        Route::get('/students/{id}/statement', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentController::class, 'statement']);
        Route::post('/student-packages', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentPackageController::class, 'store']);
    });
