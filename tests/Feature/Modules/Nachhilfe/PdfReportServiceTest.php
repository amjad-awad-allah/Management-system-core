<?php

use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use App\Modules\Nachhilfe\Infrastructure\Models\PdfExportLog;
use App\Modules\Nachhilfe\Application\Services\PayrollCalculationService;
use App\Modules\Nachhilfe\Application\Services\PdfFilenameHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
    \Illuminate\Support\Facades\DB::table('module_settings')->insert([
        'id' => (string) Str::ulid(),
        'module' => 'Nachhilfe',
        'enabled' => true,
    ]);
    foreach (['Super Admin', 'Center Manager', 'Teacher', 'Student'] as $roleName) {
        \App\Core\Models\Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['id' => (string) Str::ulid()]
        );
    }

    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole('Super Admin');

    $this->teacherUser = User::factory()->create();
    $this->teacherUser->assignRole('Teacher');

    $this->teacher = Teacher::create([
        'id' => (string) Str::ulid(),
        'user_id' => $this->teacherUser->id,
        'name' => 'Jürgen Müller',
        'email' => 'juergen@example.com',
        'phone' => '01511234567',
        'hourly_rate' => 35.00,
        'subjects' => ['Mathematik'],
        'color' => '#3b82f6',
    ]);

    $this->otherTeacherUser = User::factory()->create();
    $this->otherTeacherUser->assignRole('Teacher');

    $this->otherTeacher = Teacher::create([
        'id' => (string) Str::ulid(),
        'user_id' => $this->otherTeacherUser->id,
        'name' => 'Anna Schmidt',
        'email' => 'anna@example.com',
        'hourly_rate' => 40.00,
    ]);

    $this->studentUser = User::factory()->create();
    $this->studentUser->assignRole('Student');

    $this->room = Room::create([
        'id' => (string) Str::ulid(),
        'name' => 'Raum A101',
        'capacity' => 10,
    ]);

    $this->subject = SubjectModel::create([
        'id' => (string) Str::ulid(),
        'name' => 'Mathematik',
    ]);

    $this->student = Student::create([
        'id' => (string) Str::ulid(),
        'first_name' => 'Maximilian',
        'last_name' => 'Schneider',
        'status' => 'active',
    ]);
});

test('PdfFilenameHelper generates clean ASCII slug filenames', function () {
    $slug = PdfFilenameHelper::slugify('Jürgen Müller');
    expect($slug)->toBe('Jurgen_Muller');

    $filename = PdfFilenameHelper::teacherTimetable('Jürgen Müller', '2026-08-20');
    expect($filename)->toBe('teacher_timetable_Jurgen_Muller_2026-08-20.pdf');
});

test('Admin can export teacher timetable PDF and creates audit log', function () {
    Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-20',
        'start_time' => '10:00:00',
        'end_time' => '11:30:00',
        'duration_minutes' => 90,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($this->adminUser)
        ->getJson("/api/v1/nachhilfe/reports/teacher-timetable?teacher_id={$this->teacher->id}&start_date=2026-08-01&end_date=2026-08-31");

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');

    $auditLog = PdfExportLog::where('report_type', 'teacher_timetable')->first();
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->user_id)->toBe($this->adminUser->id)
        ->and($auditLog->success)->toBeTrue();
});

test('Room door sheet PDF enforces GDPR privacy and excludes student names from binary text', function () {
    $lesson = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-20',
        'start_time' => '10:00:00',
        'end_time' => '11:30:00',
        'duration_minutes' => 90,
        'status' => 'completed',
    ]);

    $lesson->students()->attach($this->student->id);

    $response = $this->actingAs($this->adminUser)
        ->getJson("/api/v1/nachhilfe/reports/room-door-sheet?room_id={$this->room->id}&date=2026-08-20");

    $response->assertStatus(200);
    $pdfContent = $response->getContent();
    expect($pdfContent)->toStartWith('%PDF')
        ->and($pdfContent)->not->toContain('Maximilian')
        ->and($pdfContent)->not->toContain('Schneider');

    $auditLog = PdfExportLog::where('report_type', 'room_door-sheet')->orWhere('report_type', 'room_door_sheet')->first();
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->success)->toBeTrue();
});

test('Admin can export approved payroll PDF with verified Merkle-style hash', function () {
    $lesson = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-15',
        'start_time' => '10:00:00',
        'end_time' => '11:30:00',
        'duration_minutes' => 90,
        'status' => 'completed',
    ]);

    $service = app(PayrollCalculationService::class);
    $service->approvePayroll('2026-08', $this->adminUser->id);

    $payroll = TeacherPayroll::where('month', '2026-08')->first();
    expect($payroll)->not->toBeNull();

    $response = $this->actingAs($this->adminUser)
        ->getJson("/api/v1/nachhilfe/reports/payroll/{$payroll->id}");

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

test('Tampered payroll triggers Hash Mismatch gate and blocks PDF export', function () {
    $lesson = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-15',
        'start_time' => '10:00:00',
        'end_time' => '11:30:00',
        'duration_minutes' => 90,
        'status' => 'completed',
    ]);

    $service = app(PayrollCalculationService::class);
    $service->approvePayroll('2026-08', $this->adminUser->id);

    $payroll = TeacherPayroll::where('month', '2026-08')->first();
    // Tamper with snapshot hash
    $payroll->update(['snapshot_hash' => 'invalid_tampered_hash_string']);

    $response = $this->actingAs($this->adminUser)
        ->getJson("/api/v1/nachhilfe/reports/payroll/{$payroll->id}");

    $response->assertStatus(422);

    $auditLog = PdfExportLog::where('report_type', 'payroll')->where('entity_id', $payroll->id)->first();
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->success)->toBeFalse()
        ->and($auditLog->failure_reason)->toContain('Integrity Violation');
});

test('Authorization matrix denies teacher from exporting payroll or other teacher timetable and logs failure', function () {
    Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-15',
        'start_time' => '10:00:00',
        'end_time' => '11:30:00',
        'duration_minutes' => 90,
        'status' => 'completed',
    ]);

    $service = app(PayrollCalculationService::class);
    $service->approvePayroll('2026-08', $this->adminUser->id);

    $payroll = TeacherPayroll::where('month', '2026-08')->first();

    // Teacher tries to export payroll PDF -> 403 Forbidden
    $response1 = $this->actingAs($this->teacherUser)
        ->getJson("/api/v1/nachhilfe/reports/payroll/{$payroll->id}");
    $response1->assertStatus(403);

    $log1 = PdfExportLog::where('user_id', $this->teacherUser->id)
        ->where('report_type', 'payroll')
        ->first();
    expect($log1)->not->toBeNull()
        ->and($log1->success)->toBeFalse()
        ->and($log1->failure_reason)->toBe('Unauthorized export attempt');

    // Teacher tries to export other teacher's timetable -> 403 Forbidden
    $response2 = $this->actingAs($this->teacherUser)
        ->getJson("/api/v1/nachhilfe/reports/teacher-timetable?teacher_id={$this->otherTeacher->id}&start_date=2026-08-01&end_date=2026-08-31");
    $response2->assertStatus(403);

    $log2 = PdfExportLog::where('user_id', $this->teacherUser->id)
        ->where('report_type', 'teacher_timetable')
        ->first();
    expect($log2)->not->toBeNull()
        ->and($log2->success)->toBeFalse();
});
