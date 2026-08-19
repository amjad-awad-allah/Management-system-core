<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherAvailability;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayrollItem;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\SubscriptionUsage;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use Illuminate\Support\Str;

class Phase1SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_teacher_availability()
    {
        $teacher = Teacher::factory()->create();
        $availability = TeacherAvailability::create([
            'teacher_id' => $teacher->id,
            'day_of_week' => 1,
            'start_time' => '14:00:00',
            'end_time' => '18:00:00',
        ]);

        $this->assertDatabaseHas('teacher_availabilities', [
            'id' => $availability->id,
            'teacher_id' => $teacher->id,
        ]);
        $this->assertTrue($teacher->availabilities()->where('id', $availability->id)->exists());
    }

    public function test_can_create_teacher_payroll_and_items()
    {
        $teacher = Teacher::factory()->create();
        $payroll = TeacherPayroll::create([
            'teacher_id' => $teacher->id,
            'month' => '2026-07',
            'total_completed_lessons' => 5,
            'total_hours' => 7.5,
            'total_amount' => 150.00,
            'status' => 'Draft',
        ]);

        $room = Room::create(['id' => (string) Str::ulid(), 'name' => 'Room A', 'capacity' => 10]);
        $subject = SubjectModel::create(['id' => (string) Str::ulid(), 'name' => 'Math', 'color' => '#fff']);

        $lesson = Lesson::create([
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'date' => '2026-07-15',
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'duration_minutes' => 90,
            'status' => 'completed',
        ]);

        $item = TeacherPayrollItem::create([
            'payroll_id' => $payroll->id,
            'lesson_id' => $lesson->id,
            'hours' => 1.5,
            'hourly_rate' => 20.00,
            'amount' => 30.00,
        ]);

        $this->assertDatabaseHas('teacher_payroll_items', [
            'id' => $item->id,
            'payroll_id' => $payroll->id,
            'amount' => 30.00,
        ]);
        $this->assertTrue($payroll->items()->where('id', $item->id)->exists());
    }

    public function test_can_create_subscription_usages()
    {
        $student = Student::factory()->create();
        
        $package = StudentPackage::create([
            'student_id' => $student->id,
            'total_hours' => 10.5, // Verify decimal works
            'remaining_hours' => 9.0, // Verify decimal works
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2026-08-01',
        ]);

        $usage = SubscriptionUsage::create([
            'subscription_id' => $package->id,
            'lesson_id' => null,
            'type' => 'Deduction',
            'hours' => 1.5,
        ]);

        $this->assertDatabaseHas('subscription_usages', [
            'id' => $usage->id,
            'type' => 'Deduction',
            'hours' => 1.5,
        ]);

        $this->assertDatabaseHas('student_packages', [
            'id' => $package->id,
            'total_hours' => 10.50,
            'remaining_hours' => 9.00,
        ]);

        $this->assertTrue($package->usages()->where('id', $usage->id)->exists());
    }
}
