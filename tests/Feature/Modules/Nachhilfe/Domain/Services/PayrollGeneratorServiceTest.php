<?php

namespace Tests\Feature\Modules\Nachhilfe\Domain\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Nachhilfe\Domain\Services\PayrollGeneratorService;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Core\Models\User;
use Illuminate\Support\Str;

class PayrollGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_payroll_for_teacher()
    {
        $user = User::factory()->create();
        $teacher = Teacher::create([
            'id' => (string) Str::ulid(), 
            'user_id' => $user->id, 
            'name' => 'Mr. Smith',
            'hourly_rate' => 30.00
        ]);

        $subject = SubjectModel::create(['id' => (string) Str::ulid(), 'name' => 'Math']);
        $room = \App\Modules\Nachhilfe\Infrastructure\Models\Room::create(['id' => (string) Str::ulid(), 'name' => 'A1', 'capacity' => 10]);

        // Create Lessons in July 2026
        $lesson1 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'type' => 'individual',
            'date' => '2026-07-10',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed'
        ]);

        $lesson2 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'type' => 'individual',
            'date' => '2026-07-15',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'duration_minutes' => 90,
            'status' => 'completed'
        ]);

        // Ignored lesson (cancelled)
        Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'type' => 'individual',
            'date' => '2026-07-20',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'cancelled'
        ]);

        $service = new PayrollGeneratorService();
        $payroll = $service->generateForTeacher($teacher, '2026-07');

        $this->assertNotNull($payroll);
        // 60 + 90 = 150 mins = 2.5 hours. 2.5 * 30.00 = 75.00
        $this->assertEquals(75.00, $payroll->total_amount);
        $this->assertCount(2, $payroll->items);
    }
}
