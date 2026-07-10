<?php

namespace Tests\Unit;

use App\Modules\Nachhilfe\Application\Services\PayrollService;
use App\Modules\Nachhilfe\Application\Services\SubscriptionUsageService;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayrollItem;
use App\Modules\Nachhilfe\Infrastructure\Models\SubscriptionUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DomainLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_usage_service_deducts_hours_and_is_idempotent()
    {
        $student = Student::create(['id' => Str::ulid()->toString(), 'first_name' => 'John', 'last_name' => 'Doe']);
        $package = StudentPackage::create([
            'id' => Str::ulid()->toString(),
            'student_id' => $student->id,
            'total_hours' => 10,
            'remaining_hours' => 10,
            'status' => 'active'
        ]);

        $teacher = Teacher::create([
            'id' => Str::ulid()->toString(), 
            'user_id' => Str::ulid()->toString(), 
            'name' => 'Test Teacher'
        ]);
        $room = \App\Modules\Nachhilfe\Infrastructure\Models\Room::create(['id' => Str::ulid()->toString(), 'name' => 'Room 1', 'capacity' => 10]);
        $subject = \App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel::create(['id' => Str::ulid()->toString(), 'name' => 'Math']);

        $lesson = Lesson::create([
            'id' => Str::ulid()->toString(),
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'date' => '2026-07-15',
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'duration_minutes' => 90, // 1.5 hours
            'status' => 'scheduled'
        ]);

        $lesson->students()->attach($student->id, [
            'id' => Str::ulid()->toString(),
            'package_id' => $package->id,
            'hours_consumed' => 0
        ]);

        $service = new SubscriptionUsageService();
        $service->deductForLesson($lesson);

        $this->assertDatabaseHas('student_packages', [
            'id' => $package->id,
            'remaining_hours' => 8.5
        ]);

        $this->assertDatabaseHas('subscription_usages', [
            'subscription_id' => $package->id,
            'lesson_id' => $lesson->id,
            'hours' => 1.5
        ]);

        // Test Idempotency
        $service->deductForLesson($lesson);

        // Should still be 8.5
        $this->assertDatabaseHas('student_packages', [
            'id' => $package->id,
            'remaining_hours' => 8.5
        ]);
        
        $this->assertEquals(1, SubscriptionUsage::where('lesson_id', $lesson->id)->count());
    }

    public function test_payroll_service_generates_item_and_is_idempotent()
    {
        $teacher = Teacher::create([
            'id' => Str::ulid()->toString(), 
            'user_id' => Str::ulid()->toString(), 
            'name' => 'Test Teacher',
            'hourly_rate' => 20
        ]);

        $room = \App\Modules\Nachhilfe\Infrastructure\Models\Room::create(['id' => Str::ulid()->toString(), 'name' => 'Room 1', 'capacity' => 10]);
        $subject = \App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel::create(['id' => Str::ulid()->toString(), 'name' => 'Math']);

        $lesson = Lesson::create([
            'id' => Str::ulid()->toString(),
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'date' => '2026-07-15',
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'duration_minutes' => 90, // 1.5 hours
            'status' => 'scheduled'
        ]);

        $service = new PayrollService();
        $service->generateItemForLesson($lesson);

        $this->assertDatabaseHas('teacher_payrolls', [
            'teacher_id' => $teacher->id,
            'month' => '2026-07',
            'total_completed_lessons' => 1,
            'total_hours' => 1.5,
            'total_amount' => 30 // 1.5 * 20
        ]);

        $this->assertDatabaseHas('teacher_payroll_items', [
            'lesson_id' => $lesson->id,
            'hours' => 1.5,
            'hourly_rate' => 20,
            'amount' => 30
        ]);

        // Test Idempotency
        $service->generateItemForLesson($lesson);

        // Should still be 1 lesson
        $this->assertDatabaseHas('teacher_payrolls', [
            'teacher_id' => $teacher->id,
            'total_completed_lessons' => 1
        ]);
        
        $this->assertEquals(1, TeacherPayrollItem::where('lesson_id', $lesson->id)->count());
    }
}
