<?php

use App\Core\Models\User;
use App\Core\Models\Role;
use App\Core\Models\Notification;
use App\Core\Models\NotificationPreference;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use App\Modules\Nachhilfe\Application\Services\PackageAlertService;
use App\Modules\Nachhilfe\Application\Services\SubscriptionUsageService;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    cache()->flush();
    DB::table('module_settings')->insert([
        'id' => Str::ulid()->toString(),
        'module' => 'Nachhilfe',
        'enabled' => true,
    ]);

    // Create global parent user
    $this->parentUser = User::forceCreate([
        'id' => (string) Str::ulid(),
        'name' => 'Erika Mustermann',
        'email' => 'parent_' . Str::random(5) . '@parent.com',
        'password' => 'password',
        'locale' => 'de'
    ]);
    $studentRole = Role::firstOrCreate(['name' => 'Student']);
    $this->parentUser->assignRole($studentRole);

    $this->student = new Student();
    $this->student->id = (string) Str::ulid();
    $this->student->user_id = $this->parentUser->id;
    $this->student->first_name = 'Max';
    $this->student->last_name = 'Mustermann';
    $this->student->parent_name = 'Erika Mustermann';
    $this->student->parent_phone_1 = '17612345678';
    $this->student->save();

    // Create a subject
    $this->subjectId = (string) Str::ulid();
    DB::table('subjects')->insert([
        'id' => $this->subjectId,
        'name' => 'Mathematics',
        'code' => 'MATH',
        'is_active' => true,
    ]);
});

test('expiring voucher generates core notifications', function () {
    // 1. Create a package expiring in 5 days
    $package = new StudentPackage();
    $package->id = (string) Str::ulid();
    $package->student_id = $this->student->id;
    $package->subject_id = $this->subjectId;
    $package->voucher_reference = 'JC-MATH-2026';
    $package->total_hours = 30;
    $package->remaining_hours = 25;
    $package->expires_at = now()->addDays(5)->toDateString();
    $package->status = 'active';
    $package->save();

    // 2. Run alert service check
    app(PackageAlertService::class)->checkPackageAlerts();

    // 3. Verify notifications created in db
    $this->assertDatabaseHas('notifications', [
        'user_id' => $this->parentUser->id,
        'source_type' => 'student_package',
        'source_id' => $package->id,
        'type' => 'voucher_expiring_soon',
        'delivery_channel' => 'in_app',
        'delivery_status' => 'sent',
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $this->parentUser->id,
        'source_type' => 'student_package',
        'source_id' => $package->id,
        'type' => 'voucher_expiring_soon',
        'delivery_channel' => 'chat',
        'delivery_status' => 'sent',
    ]);
});

test('low hours voucher generates core notifications', function () {
    // 1. Create a package with remaining hours <= 3
    $package = new StudentPackage();
    $package->id = (string) Str::ulid();
    $package->student_id = $this->student->id;
    $package->subject_id = $this->subjectId;
    $package->voucher_reference = 'JC-MATH-LOW';
    $package->total_hours = 10;
    $package->remaining_hours = 2;
    $package->expires_at = now()->addDays(60)->toDateString();
    $package->status = 'active';
    $package->save();

    // 2. Run alert service check
    app(PackageAlertService::class)->checkPackageAlerts();

    // 3. Verify notifications created in db
    $this->assertDatabaseHas('notifications', [
        'user_id' => $this->parentUser->id,
        'source_id' => $package->id,
        'type' => 'voucher_low_hours',
        'delivery_channel' => 'in_app',
        'delivery_status' => 'sent',
    ]);

    $notification = Notification::where('source_id', $package->id)->where('delivery_channel', 'in_app')->first();
    expect($notification->metadata['hours_remaining'])->toBe(2);
    expect($notification->metadata['student_name'])->toBe('Max Mustermann');
});

test('unique index prevents duplicate notifications', function () {
    // 1. Create a package expiring in 5 days
    $package = new StudentPackage();
    $package->id = (string) Str::ulid();
    $package->student_id = $this->student->id;
    $package->subject_id = $this->subjectId;
    $package->voucher_reference = 'JC-MATH-DUP';
    $package->total_hours = 30;
    $package->remaining_hours = 25;
    $package->expires_at = now()->addDays(5)->toDateString();
    $package->status = 'active';
    $package->save();

    // 2. Run check twice
    app(PackageAlertService::class)->checkPackageAlerts();
    app(PackageAlertService::class)->checkPackageAlerts();

    // 3. Verify exactly one set of notifications exists
    $count = Notification::where('source_id', $package->id)
        ->where('type', 'voucher_expiring_soon')
        ->where('delivery_channel', 'in_app')
        ->count();

    expect($count)->toBe(1);
});

test('real-time deduction triggers immediate low hours alert', function () {
    // 1. Create a teacher
    $teacherUser = User::forceCreate([
        'id' => (string) Str::ulid(),
        'name' => 'Teacher U',
        'email' => 'teacher_' . Str::random(5) . '@t.com',
        'password' => 'password'
    ]);
    $teacher = new Teacher();
    $teacher->id = (string) Str::ulid();
    $teacher->user_id = $teacherUser->id;
    $teacher->name = 'Teacher U';
    $teacher->save();

    // 2. Create room
    $roomId = (string) Str::ulid();
    DB::table('rooms')->insert([
        'id' => $roomId,
        'name' => 'Room X',
        'is_active' => true,
    ]);

    // 3. Create a package with remaining hours = 4
    $package = new StudentPackage();
    $package->id = (string) Str::ulid();
    $package->student_id = $this->student->id;
    $package->subject_id = $this->subjectId;
    $package->voucher_reference = 'JC-MATH-REAL';
    $package->total_hours = 10;
    $package->remaining_hours = 4;
    $package->status = 'active';
    $package->save();

    // 4. Create lesson and pivot
    $lesson = new Lesson();
    $lesson->id = (string) Str::ulid();
    $lesson->teacher_id = $teacher->id;
    $lesson->room_id = $roomId;
    $lesson->subject_id = $this->subjectId;
    $lesson->date = '2026-07-20';
    $lesson->start_time = '14:00:00';
    $lesson->end_time = '15:30:00'; // 1.5 hours
    $lesson->duration_minutes = 90;
    $lesson->status = 'completed';
    $lesson->save();

    $lessonStudent = new LessonStudent();
    $lessonStudent->id = (string) Str::ulid();
    $lessonStudent->lesson_id = $lesson->id;
    $lessonStudent->student_id = $this->student->id;
    $lessonStudent->save();

    // 5. Complete lesson to trigger deduction (which drops hours to 2.5)
    app(SubscriptionUsageService::class)->deductForLesson($lesson);

    // 6. Assert low hours alert triggered immediately
    $this->assertDatabaseHas('notifications', [
        'user_id' => $this->parentUser->id,
        'source_id' => $package->id,
        'type' => 'voucher_low_hours',
        'delivery_status' => 'sent',
    ]);
});

test('timeline security boundaries block teachers and allow parents', function () {
    // 1. Setup roles and users first to avoid guard pollution
    $teacherUser = User::forceCreate([
        'id' => (string) Str::ulid(),
        'name' => 'Teacher Account',
        'email' => 'teacher_' . Str::random(5) . '@t.com',
        'password' => 'p'
    ]);
    $teacherRole = Role::firstOrCreate(['name' => 'Teacher']);
    $teacherUser->assignRole($teacherRole);

    // 2. Setup notification in db
    $groupId = (string) Str::ulid();
    $package = new StudentPackage();
    $package->id = (string) Str::ulid();
    $package->student_id = $this->student->id;
    $package->subject_id = $this->subjectId;
    $package->total_hours = 10;
    $package->remaining_hours = 10;
    $package->status = 'active';
    $package->save();

    Notification::create([
        'id' => (string) Str::ulid(),
        'notification_group_id' => $groupId,
        'user_id' => $this->parentUser->id,
        'delivery_channel' => 'in_app',
        'type' => 'voucher_low_hours',
        'source_type' => 'student_package',
        'source_id' => $package->id,
        'title' => 'Test',
        'message' => 'Test MSG',
        'delivery_status' => 'sent',
    ]);

    // 3. Query timeline as parent (role Student)
    $responseParent = $this->actingAs($this->parentUser, 'sanctum')
        ->getJson("/api/v1/nachhilfe/students/{$this->student->id}/timeline");

    $responseParent->assertStatus(200);
    $parentEventTypes = collect($responseParent->json()['data'])->pluck('type');
    expect($parentEventTypes->contains('notification'))->toBeTrue();

    // 4. Query timeline as teacher (role Teacher)
    $responseTeacher = $this->actingAs($teacherUser, 'sanctum')
        ->getJson("/api/v1/nachhilfe/students/{$this->student->id}/timeline");

    $responseTeacher->assertStatus(200);
    $teacherEventTypes = collect($responseTeacher->json())->pluck('type');
    expect($teacherEventTypes->contains('notification'))->toBeFalse();
});

test('preference based routing skips disabled channel', function () {
    // Disable chat channel for parent user for low hours notifications
    NotificationPreference::create([
        'id' => (string) Str::ulid(),
        'user_id' => $this->parentUser->id,
        'notification_type' => 'voucher_low_hours',
        'channel' => 'chat',
        'enabled' => false,
    ]);

    $package = new StudentPackage();
    $package->id = (string) Str::ulid();
    $package->student_id = $this->student->id;
    $package->subject_id = $this->subjectId;
    $package->voucher_reference = 'JC-MATH-PREF';
    $package->total_hours = 10;
    $package->remaining_hours = 1.5;
    $package->status = 'active';
    $package->save();

    app(PackageAlertService::class)->checkPackageAlerts();

    // Verify in_app is created
    $this->assertDatabaseHas('notifications', [
        'user_id' => $this->parentUser->id,
        'source_id' => $package->id,
        'delivery_channel' => 'in_app',
    ]);

    // Verify chat is NOT created
    $this->assertDatabaseMissing('notifications', [
        'user_id' => $this->parentUser->id,
        'source_id' => $package->id,
        'delivery_channel' => 'chat',
    ]);
});

test('parent user language triggers correct localization templates', function () {
    // 1. Set parent language to english
    $this->parentUser->locale = 'en';
    $this->parentUser->save();

    $package = new StudentPackage();
    $package->id = (string) Str::ulid();
    $package->student_id = $this->student->id;
    $package->subject_id = $this->subjectId;
    $package->voucher_reference = 'JC-MATH-LANG';
    $package->total_hours = 10;
    $package->remaining_hours = 2;
    $package->status = 'active';
    $package->save();

    app(PackageAlertService::class)->checkPackageAlerts();

    $notification = Notification::where('source_id', $package->id)->where('delivery_channel', 'in_app')->first();
    expect($notification->title)->toBe('Low Voucher Balance Warning');
    expect($notification->message)->toContain('is only 2 hours');
});
