<?php

use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Invoice;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    cache()->flush();
    DB::table('module_settings')->updateOrInsert(
        ['module' => 'Nachhilfe'],
        ['id' => Str::ulid()->toString(), 'enabled' => true]
    );
});

test('can update invoice details and toggle paid status', function () {
    $user = User::forceCreate([
        'id' => (string) Str::ulid(),
        'name' => 'Admin User',
        'email' => 'admin@test.com',
        'password' => 'secret'
    ]);

    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Jane';
    $student->last_name = 'Doe';
    $student->birth_date = '2012-01-01';
    $student->school = 'Middle School';
    $student->grade = 8;
    $student->save();

    $invoice = new Invoice();
    $invoice->id = (string) Str::ulid();
    $invoice->invoice_number = 'INV-2026-07-001';
    $invoice->student_id = $student->id;
    $invoice->month = '2026-07';
    $invoice->total_amount = 150.00;
    $invoice->status = 'unpaid';
    $invoice->due_date = '2026-07-15';
    $invoice->save();

    // Verify PUT /api/v1/nachhilfe/invoices/{invoice} to change status to paid
    $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/nachhilfe/invoices/{$invoice->id}", [
        'status' => 'paid',
        'total_amount' => 175.50,
        'due_date' => '2026-07-20'
    ]);

    $response->assertStatus(200);
    $data = $response->json('invoice');
    
    expect($data['status'])->toBe('paid');
    expect((float)$data['total_amount'])->toBe(175.50);
    expect(substr($data['due_date'], 0, 10))->toBe('2026-07-20');
    expect($data['paid_at'])->not->toBeNull();

    // Revert status to unpaid
    $response2 = $this->actingAs($user, 'sanctum')->putJson("/api/v1/nachhilfe/invoices/{$invoice->id}", [
        'status' => 'unpaid',
        'total_amount' => 175.50,
        'due_date' => '2026-07-20'
    ]);

    $response2->assertStatus(200);
    $data2 = $response2->json('invoice');
    
    expect($data2['status'])->toBe('unpaid');
    expect($data2['paid_at'])->toBeNull();
});

test('can update teacher payroll details', function () {
    $user = User::forceCreate([
        'id' => (string) Str::ulid(),
        'name' => 'Admin User',
        'email' => 'admin2@test.com',
        'password' => 'secret'
    ]);

    $teacher = new Teacher();
    $teacher->id = (string) Str::ulid();
    $teacher->user_id = (string) Str::ulid();
    $teacher->name = 'Mr. Smith';
    $teacher->qualification = 'Math teacher';
    $teacher->hourly_rate = 25.00;
    $teacher->save();

    $payroll = new TeacherPayroll();
    $payroll->id = (string) Str::ulid();
    $payroll->teacher_id = $teacher->id;
    $payroll->month = '2026-07';
    $payroll->total_completed_lessons = 4;
    $payroll->total_hours = 6.00;
    $payroll->total_amount = 150.00;
    $payroll->status = 'Draft';
    $payroll->save();

    $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/nachhilfe/payrolls/{$payroll->id}", [
        'status' => 'Paid',
        'total_amount' => 180.00
    ]);

    $response->assertStatus(200);
    $data = $response->json('payroll');

    expect($data['status'])->toBe('Paid');
    expect((float)$data['total_amount'])->toBe(180.00);
});
