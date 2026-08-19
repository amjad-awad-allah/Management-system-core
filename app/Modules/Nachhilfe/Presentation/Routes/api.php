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

        Route::get('/calendar', [\App\Modules\Nachhilfe\Presentation\Controllers\CalendarController::class, 'index']);
        Route::post('/holidays', [\App\Modules\Nachhilfe\Presentation\Controllers\CalendarController::class, 'storeHoliday']);

        Route::get('/lessons', [LessonController::class, 'index']);
        Route::post('/lessons', [LessonController::class, 'store']);
        Route::put('/lessons/{lesson}', [LessonController::class, 'update']);
        Route::patch('/lessons/{lesson}/status', [LessonController::class, 'updateStatus']);
        
        Route::get('/payrolls/summary', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherPayrollController::class, 'summary']);
        Route::post('/payrolls/approve', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherPayrollController::class, 'approve']);
        Route::get('/payrolls', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherPayrollController::class, 'index']);
        Route::post('/payrolls', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherPayrollController::class, 'generate']);
        Route::get('/payrolls/{payroll}', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherPayrollController::class, 'show']);
        Route::put('/payrolls/{payroll}', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherPayrollController::class, 'update']);
        Route::patch('/payrolls/{payroll}/status', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherPayrollController::class, 'updateStatus']);
        Route::delete('/payrolls/{payroll}', [\App\Modules\Nachhilfe\Presentation\Controllers\TeacherPayrollController::class, 'destroy']);
        
        Route::get('/invoices', [\App\Modules\Nachhilfe\Presentation\Controllers\InvoiceController::class, 'index']);
        Route::post('/invoices', [\App\Modules\Nachhilfe\Presentation\Controllers\InvoiceController::class, 'generate']);
        Route::get('/invoices/{invoice}', [\App\Modules\Nachhilfe\Presentation\Controllers\InvoiceController::class, 'show']);
        Route::put('/invoices/{invoice}', [\App\Modules\Nachhilfe\Presentation\Controllers\InvoiceController::class, 'update']);
        Route::patch('/invoices/{invoice}/status', [\App\Modules\Nachhilfe\Presentation\Controllers\InvoiceController::class, 'updateStatus']);
        Route::delete('/invoices/{invoice}', [\App\Modules\Nachhilfe\Presentation\Controllers\InvoiceController::class, 'destroy']);
        
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
        Route::get('/students/{id}/timeline', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentController::class, 'timeline']);
        Route::post('/student-packages', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentPackageController::class, 'store']);

        // Student Document Management
        Route::get('/students/{studentId}/documents', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentDocumentController::class, 'index']);
        Route::post('/students/{studentId}/documents', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentDocumentController::class, 'store']);
        Route::get('/documents/{id}/download', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentDocumentController::class, 'download']);
        Route::delete('/documents/{id}', [\App\Modules\Nachhilfe\Presentation\Controllers\StudentDocumentController::class, 'destroy']);

        // Stundennachweis PDF Export
        Route::get('/students/{studentId}/stundennachweis', [\App\Modules\Nachhilfe\Presentation\Controllers\StundennachweisController::class, 'generate']);

        // Phase 5B PDF Report Engine
        Route::get('/reports/teacher-timetable', [\App\Modules\Nachhilfe\Presentation\Controllers\PdfReportController::class, 'teacherTimetable']);
        Route::get('/reports/room-door-sheet', [\App\Modules\Nachhilfe\Presentation\Controllers\PdfReportController::class, 'roomDoorSheet']);
        Route::get('/reports/payroll/{payroll_id}', [\App\Modules\Nachhilfe\Presentation\Controllers\PdfReportController::class, 'payrollReport']);
    });
