<?php

namespace Tests\Feature\Modules\Nachhilfe\Presentation\Controllers;

use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LessonCancellationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Student $student;
    private Teacher $teacher;
    private string $subjectId;
    private string $roomId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::forceCreate([
            'id' => (string) Str::ulid(),
            'name' => 'Admin User',
            'email' => 'admin_cancel_' . Str::random(5) . '@test.com',
            'password' => 'password',
        ]);

        $this->student = Student::create([
            'id' => (string) Str::ulid(),
            'first_name' => 'Cancel',
            'last_name' => 'Student',
            'birth_date' => '2012-05-15',
            'school' => 'School',
            'grade' => '8',
            'parent_phone_1' => '+49 151 98765432'
        ]);

        $this->teacher = Teacher::create([
            'id' => (string) Str::ulid(),
            'name' => 'Teacher',
            'email' => 'teacher_cancel_' . Str::random(5) . '@test.com',
        ]);

        $this->subjectId = (string) Str::ulid();
        \Illuminate\Support\Facades\DB::table('subjects')->insert([
            'id' => $this->subjectId,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'is_active' => true,
        ]);

        $this->roomId = (string) Str::ulid();
        \Illuminate\Support\Facades\DB::table('rooms')->insert([
            'id' => $this->roomId,
            'name' => 'Room A',
            'is_active' => true,
        ]);

        // Enable Nachhilfe module for the test request
        \Illuminate\Support\Facades\DB::table('module_settings')->insert([
            'id' => (string) Str::ulid(),
            'module' => 'Nachhilfe',
            'enabled' => true
        ]);
        \Illuminate\Support\Facades\Cache::forget('module_enabled_Nachhilfe');

        // Insert active CancellationPolicy (Standard: 24h, 100% deduction)
        \Illuminate\Support\Facades\DB::table('cancellation_policies')->insert([
            'id' => (string) Str::ulid(),
            'name' => 'Standard Policy',
            'hours_before' => 24,
            'deduct_percentage' => 100.00,
            'is_active' => true,
        ]);
    }

    public function test_late_cancellation_automatically_deducts_hours_under_policy(): void
    {
        // 1. Create a student package with 10 remaining hours
        $package = StudentPackage::create([
            'id' => (string) Str::ulid(),
            'student_id' => $this->student->id,
            'subject_id' => $this->subjectId,
            'funding_source' => 'private',
            'total_hours' => 10,
            'remaining_hours' => 10,
            'status' => 'active',
        ]);

        // 2. Book a lesson starting in 5 hours (inside the 24-hour cancellation window)
        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->roomId,
            'subject_id' => $this->subjectId,
            'type' => 'individual',
            'date' => now()->addHours(5)->toDateString(),
            'start_time' => now()->addHours(5)->toTimeString(),
            'end_time' => now()->addHours(6)->toTimeString(),
            'duration_minutes' => 60, // 1 hour
            'status' => 'scheduled'
        ]);

        $lessonStudent = LessonStudent::create([
            'id' => (string) Str::ulid(),
            'lesson_id' => $lesson->id,
            'student_id' => $this->student->id,
            'package_id' => $package->id,
        ]);

        // 3. Cancel the lesson (triggers policy)
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/lessons/{$lesson->id}/status", [
                'status' => 'cancelled'
            ]);

        $response->assertStatus(200);

        // 4. Verify package remaining hours are deducted by 1 hour (from 10 to 9)
        $package->refresh();
        $this->assertEquals(9.00, (float) $package->remaining_hours);

        // 5. Verify a ledger entry was created in subscription_usages
        $this->assertDatabaseHas('subscription_usages', [
            'subscription_id' => $package->id,
            'lesson_id' => $lesson->id,
            'type' => 'Deduction',
            'hours' => 1.00,
            'notes' => 'Late cancellation (< 24h before start)'
        ]);
    }

    public function test_early_cancellation_does_not_deduct_hours(): void
    {
        $package = StudentPackage::create([
            'id' => (string) Str::ulid(),
            'student_id' => $this->student->id,
            'subject_id' => $this->subjectId,
            'funding_source' => 'private',
            'total_hours' => 10,
            'remaining_hours' => 10,
            'status' => 'active',
        ]);

        // Book a lesson starting in 30 hours (outside the 24-hour cancellation window)
        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->roomId,
            'subject_id' => $this->subjectId,
            'type' => 'individual',
            'date' => now()->addHours(30)->toDateString(),
            'start_time' => now()->addHours(30)->toTimeString(),
            'end_time' => now()->addHours(31)->toTimeString(),
            'duration_minutes' => 60,
            'status' => 'scheduled'
        ]);

        $lessonStudent = LessonStudent::create([
            'id' => (string) Str::ulid(),
            'lesson_id' => $lesson->id,
            'student_id' => $this->student->id,
            'package_id' => $package->id,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/lessons/{$lesson->id}/status", [
                'status' => 'cancelled'
            ]);

        $response->assertStatus(200);

        // Verify hours are NOT deducted
        $package->refresh();
        $this->assertEquals(10.00, (float) $package->remaining_hours);
    }

    public function test_late_cancellation_with_explicit_charge_false_override(): void
    {
        $package = StudentPackage::create([
            'id' => (string) Str::ulid(),
            'student_id' => $this->student->id,
            'subject_id' => $this->subjectId,
            'funding_source' => 'private',
            'total_hours' => 10,
            'remaining_hours' => 10,
            'status' => 'active',
        ]);

        // Book lesson in 5 hours (inside late cancel window)
        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->roomId,
            'subject_id' => $this->subjectId,
            'type' => 'individual',
            'date' => now()->addHours(5)->toDateString(),
            'start_time' => now()->addHours(5)->toTimeString(),
            'end_time' => now()->addHours(6)->toTimeString(),
            'duration_minutes' => 60,
            'status' => 'scheduled'
        ]);

        $lessonStudent = LessonStudent::create([
            'id' => (string) Str::ulid(),
            'lesson_id' => $lesson->id,
            'student_id' => $this->student->id,
            'package_id' => $package->id,
        ]);

        // Cancel with explicit charge_student => false
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/v1/nachhilfe/lessons/{$lesson->id}/status", [
                'status' => 'cancelled',
                'charge_student' => false
            ]);

        $response->assertStatus(200);

        // Verify hours are NOT deducted
        $package->refresh();
        $this->assertEquals(10.00, (float) $package->remaining_hours);
    }
}
