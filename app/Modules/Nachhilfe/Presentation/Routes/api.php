<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Nachhilfe\Presentation\Controllers\SubjectController;
use App\Modules\Nachhilfe\Presentation\Controllers\LessonController;

Route::middleware(['api', 'auth:sanctum', 'module.active:Nachhilfe'])
    ->prefix('api/v1/nachhilfe')
    ->group(function () {
        
        Route::get('/subjects', [SubjectController::class, 'index']);
        Route::post('/subjects', [SubjectController::class, 'store']);

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

        Route::get('/students/{id}/packages', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentPackageController::class, 'index']);
        Route::post('/student-packages', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentPackageController::class, 'store']);
    });
