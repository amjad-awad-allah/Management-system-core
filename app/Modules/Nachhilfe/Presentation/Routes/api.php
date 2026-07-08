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

        Route::get('/students', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentController::class, 'index']);
        Route::post('/students', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentController::class, 'store']);
        Route::get('/students/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentController::class, 'show']);

        Route::get('/teachers', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherController::class, 'index']);
        Route::post('/teachers', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherController::class, 'store']);

    });
