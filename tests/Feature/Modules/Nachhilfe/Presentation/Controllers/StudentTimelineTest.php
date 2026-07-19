<?php

use App\Core\Models\User;
use App\Core\Models\Role;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use App\Modules\Nachhilfe\Infrastructure\Models\Attendance;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    cache()->flush();
    DB::table('module_settings')->insert([
        'id' => Str::ulid()->toString(),
        'module' => 'Nachhilfe',
        'enabled' => true,
    ]);

    // Create a global teacher to satisfy foreign key constraints in tests
    $teacherUser = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'T', 'email' => 't_test_' . Str::random(5) . '@t.com', 'password' => 'p']);
    $teacher = new Teacher();
    $teacher->id = (string) Str::ulid();
    $teacher->user_id = $teacherUser->id;
    $teacher->name = 'T';
    $teacher->save();

    $this->teacherId = $teacher->id;
});

test('admin can fetch student timeline with all events', function () {
    // 1. Setup Student
    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Timeline';
    $student->last_name = 'Student';
    $student->birth_date = '2010-05-15';
    $student->school = 'High School';
    $student->grade = 10;
    $student->save();

    // 2. Setup Teacher
    $teacherUser = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'Teacher User', 'email' => 'teacher@t.com', 'password' => 'p']);
    $teacher = new Teacher();
    $teacher->id = (string) Str::ulid();
    $teacher->user_id = $teacherUser->id;
    $teacher->name = 'Teacher User';
    $teacher->save();

    // 3. Setup Lesson and Pivot
    $subjectId = (string) Str::ulid();
    DB::table('subjects')->insert([
        'id' => $subjectId,
        'name' => 'Mathematics',
        'code' => 'MATH',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $roomId = (string) Str::ulid();
    DB::table('rooms')->insert([
        'id' => $roomId,
        'name' => 'Room A',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $lesson = new Lesson();
    $lesson->id = (string) Str::ulid();
    $lesson->teacher_id = $teacher->id;
    $lesson->room_id = $roomId;
    $lesson->subject_id = $subjectId;
    $lesson->date = '2026-07-20';
    $lesson->start_time = '14:00:00';
    $lesson->end_time = '15:00:00';
    $lesson->status = 'scheduled';
    $lesson->save();

    $lessonStudent = new LessonStudent();
    $lessonStudent->id = (string) Str::ulid();
    $lessonStudent->lesson_id = $lesson->id;
    $lessonStudent->student_id = $student->id;
    $lessonStudent->notes = 'Initial study notes';
    $lessonStudent->save();

    // 4. Setup Admin User
    $adminUser = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'Admin User', 'email' => 'admin@t.com', 'password' => 'p']);
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);
    $adminUser->assignRole($adminRole);

    // 5. Query Admin Timeline
    $response = $this->actingAs($adminUser, 'sanctum')
        ->getJson("/api/v1/nachhilfe/students/{$student->id}/timeline");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'per_page', 'total', 'last_page']
        ]);
});

test('teacher can only view timeline of students they teach and cannot see changes or payment events', function () {
    // 1. Setup Student
    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Secure';
    $student->last_name = 'Student';
    $student->birth_date = '2010-05-15';
    $student->school = 'High School';
    $student->grade = 10;
    $student->save();

    // 2. Setup Teacher
    $teacherUser = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'Teacher A', 'email' => 'teachera@t.com', 'password' => 'p']);
    $teacherRole = Role::firstOrCreate(['name' => 'Teacher']);
    $teacherUser->assignRole($teacherRole);

    $teacher = new Teacher();
    $teacher->id = (string) Str::ulid();
    $teacher->user_id = $teacherUser->id;
    $teacher->name = 'Teacher A';
    $teacher->save();

    // 3. Query Timeline for unrelated student -> should fail with 404
    $response = $this->actingAs($teacherUser, 'sanctum')
        ->getJson("/api/v1/mobile/teacher/students/{$student->id}/timeline");

    $response->assertStatus(404);

    // 4. Enroll Student in Lesson with this Teacher
    $subjectId = (string) Str::ulid();
    DB::table('subjects')->insert([
        'id' => $subjectId,
        'name' => 'Mathematics',
        'code' => 'MATH',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $roomId = (string) Str::ulid();
    DB::table('rooms')->insert([
        'id' => $roomId,
        'name' => 'Room A',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $lesson = new Lesson();
    $lesson->id = (string) Str::ulid();
    $lesson->teacher_id = $teacher->id;
    $lesson->room_id = $roomId;
    $lesson->subject_id = $subjectId;
    $lesson->date = '2026-07-20';
    $lesson->start_time = '14:00:00';
    $lesson->end_time = '15:00:00';
    $lesson->status = 'scheduled';
    $lesson->save();

    $lessonStudent = new LessonStudent();
    $lessonStudent->id = (string) Str::ulid();
    $lessonStudent->lesson_id = $lesson->id;
    $lessonStudent->student_id = $student->id;
    $lessonStudent->notes = 'Lesson notes';
    $lessonStudent->save();

    // 5. Query Timeline again -> should succeed now
    $response = $this->actingAs($teacherUser, 'sanctum')
        ->getJson("/api/v1/mobile/teacher/students/{$student->id}/timeline");

    $response->assertStatus(200);
});

test('parent can view their own child timeline with payments but not audit logs', function () {
    // 1. Setup parent user and student
    $parentUser = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'Parent A', 'email' => 'parenta@t.com', 'password' => 'p']);
    $studentRole = Role::firstOrCreate(['name' => 'Student']);
    $parentUser->assignRole($studentRole);

    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->user_id = $parentUser->id; // Linked directly as parent account
    $student->first_name = 'Child';
    $student->last_name = 'Student';
    $student->save();

    // Create a local paid invoice (to test payment events)
    $invoice = new \App\Modules\Nachhilfe\Infrastructure\Models\Invoice();
    $invoice->id = (string) Str::ulid();
    $invoice->invoice_number = 'INV-TEST123';
    $invoice->student_id = $student->id;
    $invoice->month = '2026-07';
    $invoice->total_amount = 150.00;
    $invoice->status = 'paid';
    $invoice->due_date = '2026-07-31';
    $invoice->paid_at = now();
    $invoice->save();

    // Create an audit log record for the student
    DB::table('audit_logs')->insert([
        'id' => (string) Str::ulid(),
        'user_id' => $parentUser->id,
        'event' => 'updated',
        'auditable_type' => Student::class,
        'auditable_id' => $student->id,
        'old_values' => json_encode(['first_name' => 'OldName']),
        'new_values' => json_encode(['first_name' => 'Child']),
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // 2. Query timeline as Parent
    $response = $this->actingAs($parentUser, 'sanctum')
        ->getJson("/api/v1/mobile/student/timeline");

    $response->assertStatus(200);

    $events = $response->json('data');

    // 3. Assertions
    $hasPayment = false;
    $hasChange = false;
    foreach ($events as $event) {
        if ($event['type'] === 'payment') {
            $hasPayment = true;
        }
        if ($event['type'] === 'change') {
            $hasChange = true;
        }
    }

    expect($hasPayment)->toBeTrue();
    expect($hasChange)->toBeFalse(); // Parents cannot see audit change logs
});

test('billing provider respects contract boundaries by using BillingContract mock', function () {
    // 1. Setup student
    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Contract';
    $student->last_name = 'Student';
    $student->save();

    $package = new StudentPackage();
    $package->id = (string) Str::ulid();
    $package->student_id = $student->id;
    $package->total_hours = 10;
    $package->remaining_hours = 10;
    $package->funding_source = 'private';
    $package->status = 'active';
    $package->save();

    // 2. Mock BillingContract
    $mockBilling = Mockery::mock(\App\Shared\Contracts\Billing\BillingContract::class);
    $mockBilling->shouldReceive('getTransactionsByReferences')
        ->once()
        ->with('student_package', [$package->id])
        ->andReturn([
            [
                'id' => 'pmt_mocked123',
                'amount' => '150.00',
                'method' => 'cash',
                'status' => 'completed',
                'created_at' => '2026-07-19T04:00:00Z'
            ]
        ]);

    app()->instance(\App\Shared\Contracts\Billing\BillingContract::class, $mockBilling);

    // 3. Query through BillingTimelineProvider
    $provider = new \App\Modules\Nachhilfe\Infrastructure\Timeline\BillingTimelineProvider();
    $events = $provider->getEvents($student);

    // Find the mocked payment event
    $mockedEvent = collect($events)->firstWhere('metadata.payment_id', 'pmt_mocked123');
    
    expect($mockedEvent)->not->toBeNull();
    expect($mockedEvent['title'])->toBe('Package payment received');
    expect($mockedEvent['metadata']['amount'])->toBe('150.00');
});

test('pending_approval package consumption works normally', function () {
    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Pending';
    $student->last_name = 'Consumption';
    $student->save();

    $package = new StudentPackage();
    $package->id = (string) Str::ulid();
    $package->student_id = $student->id;
    $package->total_hours = 50.00;
    $package->remaining_hours = 50.00;
    $package->funding_source = 'jobcenter';
    $package->status = 'pending_approval'; // Antrag gestellt
    $package->save();

    $subjectId = (string) Str::ulid();
    DB::table('subjects')->insert([
        'id' => $subjectId,
        'name' => 'Mathematics',
        'code' => 'MATH',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $roomId = (string) Str::ulid();
    DB::table('rooms')->insert([
        'id' => $roomId,
        'name' => 'Room A',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $lesson = new Lesson();
    $lesson->id = (string) Str::ulid();
    $lesson->teacher_id = $this->teacherId;
    $lesson->room_id = $roomId;
    $lesson->subject_id = $subjectId;
    $lesson->date = '2026-07-20';
    $lesson->start_time = '14:00:00';
    $lesson->end_time = '16:00:00'; // 2 hours
    $lesson->duration_minutes = 120;
    $lesson->status = 'completed';
    $lesson->save();

    $lessonStudent = new LessonStudent();
    $lessonStudent->id = (string) Str::ulid();
    $lessonStudent->lesson_id = $lesson->id;
    $lessonStudent->student_id = $student->id;
    $lessonStudent->save();

    // Trigger service
    $service = app(\App\Modules\Nachhilfe\Application\Services\SubscriptionUsageService::class);
    $service->deductForLesson($lesson);

    $package->refresh();
    expect($package->remaining_hours)->toEqual(48.00);
});

test('rejected package is excluded from consumption', function () {
    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Rejected';
    $student->last_name = 'Student';
    $student->save();

    $package = new StudentPackage();
    $package->id = (string) Str::ulid();
    $package->student_id = $student->id;
    $package->total_hours = 50.00;
    $package->remaining_hours = 50.00;
    $package->funding_source = 'jobcenter';
    $package->status = 'rejected'; // Abgelehnt
    $package->save();

    $subjectId = (string) Str::ulid();
    DB::table('subjects')->insert([
        'id' => $subjectId,
        'name' => 'Mathematics',
        'code' => 'MATH',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $roomId = (string) Str::ulid();
    DB::table('rooms')->insert([
        'id' => $roomId,
        'name' => 'Room A',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $lesson = new Lesson();
    $lesson->id = (string) Str::ulid();
    $lesson->teacher_id = $this->teacherId;
    $lesson->room_id = $roomId;
    $lesson->subject_id = $subjectId;
    $lesson->date = '2026-07-20';
    $lesson->start_time = '14:00:00';
    $lesson->end_time = '16:00:00'; // 2 hours
    $lesson->duration_minutes = 120;
    $lesson->status = 'completed';
    $lesson->save();

    $lessonStudent = new LessonStudent();
    $lessonStudent->id = (string) Str::ulid();
    $lessonStudent->lesson_id = $lesson->id;
    $lessonStudent->student_id = $student->id;
    $lessonStudent->save();

    $service = app(\App\Modules\Nachhilfe\Application\Services\SubscriptionUsageService::class);

    // Expecting exception because no active or pending_approval package with enough hours is found
    expect(fn() => $service->deductForLesson($lesson))->toThrow(Exception::class);
});

test('consumption priority prefers active over pending_approval', function () {
    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Priority';
    $student->last_name = 'Student';
    $student->save();

    // Package A: pending_approval (100 hours)
    $packageA = new StudentPackage();
    $packageA->id = (string) Str::ulid();
    $packageA->student_id = $student->id;
    $packageA->total_hours = 100.00;
    $packageA->remaining_hours = 100.00;
    $packageA->funding_source = 'jobcenter';
    $packageA->status = 'pending_approval';
    $packageA->save();

    // Package B: active (10 hours)
    $packageB = new StudentPackage();
    $packageB->id = (string) Str::ulid();
    $packageB->student_id = $student->id;
    $packageB->total_hours = 10.00;
    $packageB->remaining_hours = 10.00;
    $packageB->funding_source = 'jobcenter';
    $packageB->status = 'active';
    $packageB->save();

    $subjectId = (string) Str::ulid();
    DB::table('subjects')->insert([
        'id' => $subjectId,
        'name' => 'Mathematics',
        'code' => 'MATH',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $roomId = (string) Str::ulid();
    DB::table('rooms')->insert([
        'id' => $roomId,
        'name' => 'Room A',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $lesson = new Lesson();
    $lesson->id = (string) Str::ulid();
    $lesson->teacher_id = $this->teacherId;
    $lesson->room_id = $roomId;
    $lesson->subject_id = $subjectId;
    $lesson->date = '2026-07-20';
    $lesson->start_time = '14:00:00';
    $lesson->end_time = '16:00:00'; // 2 hours
    $lesson->duration_minutes = 120;
    $lesson->status = 'completed';
    $lesson->save();

    $lessonStudent = new LessonStudent();
    $lessonStudent->id = (string) Str::ulid();
    $lessonStudent->lesson_id = $lesson->id;
    $lessonStudent->student_id = $student->id;
    $lessonStudent->save();

    $service = app(\App\Modules\Nachhilfe\Application\Services\SubscriptionUsageService::class);
    $service->deductForLesson($lesson);

    $packageA->refresh();
    $packageB->refresh();

    // Active package B should be consumed, pending package A should remain untouched
    expect($packageB->remaining_hours)->toEqual(8.00);
    expect($packageA->remaining_hours)->toEqual(100.00);
});

test('nachhilfe:expire-packages transitions expired packages to expired status', function () {
    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Expiring';
    $student->last_name = 'Student';
    $student->save();

    // Package 1: active but expired yesterday
    $package1 = new StudentPackage();
    $package1->id = (string) Str::ulid();
    $package1->student_id = $student->id;
    $package1->total_hours = 10.00;
    $package1->remaining_hours = 10.00;
    $package1->funding_source = 'private';
    $package1->status = 'active';
    $package1->expires_at = now()->subDay()->toDateString();
    $package1->save();

    // Package 2: active and expires tomorrow (should stay active)
    $package2 = new StudentPackage();
    $package2->id = (string) Str::ulid();
    $package2->student_id = $student->id;
    $package2->total_hours = 10.00;
    $package2->remaining_hours = 10.00;
    $package2->funding_source = 'private';
    $package2->status = 'active';
    $package2->expires_at = now()->addDay()->toDateString();
    $package2->save();

    // Run artisan command
    $this->artisan('nachhilfe:expire-packages')
         ->expectsOutput('Expired 1 hour approvals.')
         ->assertExitCode(0);

    $package1->refresh();
    $package2->refresh();

    expect($package1->status)->toBe('expired');
    expect($package2->status)->toBe('active');
});
