<?php

use App\Core\Models\User;
use App\Modules\Nachhilfe\Application\Services\PayrollCalculationService;
use App\Modules\Nachhilfe\Domain\Services\LessonModificationGuard;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayrollItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    \Illuminate\Support\Facades\Cache::flush();
    \Illuminate\Support\Facades\DB::table('module_settings')->insert([
        'id' => (string) Str::ulid(),
        'module' => 'Nachhilfe',
        'enabled' => true,
    ]);

    \App\Core\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
    $this->user->assignRole('Super Admin');
    $this->actingAs($this->user);

    $this->teacher = Teacher::create([
        'id' => (string) Str::ulid(),
        'name' => 'Prof. Alexander Vance',
        'email' => 'vance@nachhilfe.local',
        'hourly_rate' => 30.00,
        'user_id' => $this->user->id,
    ]);

    $this->room = Room::create([
        'id' => (string) Str::ulid(),
        'name' => 'Room 201',
        'capacity' => 12,
        'type' => 'physical',
    ]);

    $this->subject = SubjectModel::create([
        'id' => (string) Str::ulid(),
        'name' => 'Physics',
        'code' => 'PHYS-01',
    ]);
});

test('draft summary includes completed lessons and excludes scheduled future and cancelled lessons', function () {
    $service = app(PayrollCalculationService::class);
    $month = '2026-08';

    // 1. Completed lesson (90 mins = 1.5 hrs @ €30/hr = €45.00)
    Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'individual',
        'date' => '2026-08-10',
        'start_time' => '10:00',
        'end_time' => '11:30',
        'duration_minutes' => 90,
        'status' => 'completed',
    ]);

    // 2. Cancelled lesson (should be excluded)
    Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'individual',
        'date' => '2026-08-12',
        'start_time' => '14:00',
        'end_time' => '15:00',
        'duration_minutes' => 60,
        'status' => 'cancelled',
    ]);

    // 3. Future scheduled lesson (should be excluded from draft earnings)
    Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'individual',
        'date' => '2026-08-28',
        'start_time' => '16:00',
        'end_time' => '17:00',
        'duration_minutes' => 60,
        'status' => 'scheduled',
    ]);

    $summary = $service->getPayrollSummary($month);

    expect($summary['status'])->toBe('draft')
        ->and($summary['total_completed_lessons'])->toBe(1)
        ->and($summary['total_center_hours'])->toBe(1.5)
        ->and($summary['total_center_payout'])->toBe(45.00)
        ->and($summary['teachers'])->toHaveCount(1)
        ->and($summary['teachers'][0]['total_payout'])->toBe(45.00);
});

test('approval is BLOCKED when past scheduled unverified lessons exist', function () {
    $service = app(PayrollCalculationService::class);
    $month = '2026-08';

    // Completed lesson
    Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'individual',
        'date' => '2026-08-01',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_minutes' => 60,
        'status' => 'completed',
    ]);

    // Past scheduled lesson (unverified!)
    Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'individual',
        'date' => '2026-08-02',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_minutes' => 60,
        'status' => 'scheduled',
    ]);

    expect(fn() => $service->approvePayroll($month, $this->user->id))
        ->toThrow(ValidationException::class);
});

test('approvePayroll creates atomic frozen snapshot with deterministic SHA-256 hash', function () {
    $service = app(PayrollCalculationService::class);
    $month = '2026-08';

    $lesson = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'individual',
        'date' => '2026-08-15',
        'start_time' => '10:00',
        'end_time' => '12:00',
        'duration_minutes' => 120,
        'status' => 'completed',
    ]);

    $summary = $service->approvePayroll($month, $this->user->id);

    expect($summary['status'])->toBe('approved')
        ->and($summary['total_completed_lessons'])->toBe(1)
        ->and($summary['total_center_hours'])->toBe(2.0)
        ->and($summary['total_center_payout'])->toBe(60.00);

    $payrollRecord = TeacherPayroll::where('month', $month)->first();
    expect($payrollRecord)->not->toBeNull()
        ->and($payrollRecord->status)->toBe('approved')
        ->and($payrollRecord->snapshot_hash)->not->toBeNull()
        ->and(strlen($payrollRecord->snapshot_hash))->toBe(64);

    $item = TeacherPayrollItem::where('payroll_id', $payrollRecord->id)->first();
    expect($item)->not->toBeNull()
        ->and($item->lesson_id)->toBe($lesson->id)
        ->and((float) $item->hourly_rate)->toBe(30.00)
        ->and((float) $item->amount)->toBe(60.00);
});

test('approval is IDEMPOTENT and does not create duplicate payroll items on repeated calls', function () {
    $service = app(PayrollCalculationService::class);
    $month = '2026-08';

    Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'individual',
        'date' => '2026-08-15',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_minutes' => 60,
        'status' => 'completed',
    ]);

    $firstRun = $service->approvePayroll($month, $this->user->id);
    $secondRun = $service->approvePayroll($month, $this->user->id);

    expect($firstRun)->toEqual($secondRun)
        ->and(TeacherPayroll::where('month', $month)->count())->toBe(1)
        ->and(TeacherPayrollItem::count())->toBe(1);
});

test('post-approval hourly rate change does NOT alter approved historical payroll snapshot', function () {
    $service = app(PayrollCalculationService::class);
    $month = '2026-08';

    Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'individual',
        'date' => '2026-08-15',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_minutes' => 60,
        'status' => 'completed',
    ]);

    $service->approvePayroll($month, $this->user->id);

    // Update teacher's hourly rate from €30 -> €50
    $this->teacher->update(['hourly_rate' => 50.00]);

    // Query approved payroll summary post-rate-increase
    $summary = $service->getPayrollSummary($month);

    expect($summary['total_center_payout'])->toBe(30.00)
        ->and($summary['teachers'][0]['total_payout'])->toBe(30.00);
});

test('LessonModificationGuard blocks edit, reschedule, or deletion of lessons in approved payroll', function () {
    $service = app(PayrollCalculationService::class);
    $guard = app(LessonModificationGuard::class);
    $month = '2026-08';

    $lesson = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'individual',
        'date' => '2026-08-15',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_minutes' => 60,
        'status' => 'completed',
    ]);

    // Before approval: modification is allowed
    expect(fn() => $guard->checkCanModify($lesson))->not->toThrow(ValidationException::class);

    // Approve payroll
    $service->approvePayroll($month, $this->user->id);

    // After approval: modification throws HTTP 422 ValidationException
    expect(fn() => $guard->checkCanModify($lesson))->toThrow(ValidationException::class);
});

test('updating lesson via Controller throws 422 if lesson is bound to approved payroll', function () {
    $service = app(PayrollCalculationService::class);
    $month = '2026-08';

    $lesson = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'individual',
        'date' => '2026-08-15',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_minutes' => 60,
        'status' => 'completed',
    ]);

    $service->approvePayroll($month, $this->user->id);

    $student = \App\Modules\Nachhilfe\Infrastructure\Models\Student::create([
        'id' => (string) Str::ulid(),
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'email' => 'max@example.com',
    ]);

    $response = $this->putJson("/api/v1/nachhilfe/lessons/{$lesson->id}", [
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'individual',
        'date' => '2026-08-20',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'students' => [
            ['student_id' => $student->id]
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['payroll']);
});
