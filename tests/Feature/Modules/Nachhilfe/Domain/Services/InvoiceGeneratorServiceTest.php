<?php

namespace Tests\Feature\Modules\Nachhilfe\Domain\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Nachhilfe\Domain\Services\InvoiceGeneratorService;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentContract;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Core\Models\User;
use Illuminate\Support\Str;

class InvoiceGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_invoice_for_present_and_absent_but_not_excused()
    {
        $user = User::factory()->create();
        $student = Student::create([
            'id' => (string) Str::ulid(),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => '2010-01-01',
            'level' => 'G10'
        ]);

        $subject = SubjectModel::create(['id' => (string) Str::ulid(), 'name' => 'Math']);
        $teacher = Teacher::create(['id' => (string) Str::ulid(), 'user_id' => $user->id, 'name' => 'Mr. Smith']);
        $room = \App\Modules\Nachhilfe\Infrastructure\Models\Room::create(['id' => (string) Str::ulid(), 'name' => 'A1', 'capacity' => 10]);

        // Create Contract
        StudentContract::create([
            'id' => (string) Str::ulid(),
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'hours_per_week' => 2,
            'hourly_rate' => 25.00,
            'start_date' => '2026-07-01'
        ]);

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
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed'
        ]);

        $lesson3 = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'type' => 'individual',
            'date' => '2026-07-20',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'completed'
        ]);

        // Attendances
        $lesson1->students()->attach($student->id, ['id' => (string) Str::ulid()]);
        $ls1 = $lesson1->students()->first()->pivot;
        $ls1->attendance()->create(['id' => (string) Str::ulid(), 'status' => 'present']);

        $lesson2->students()->attach($student->id, ['id' => (string) Str::ulid()]);
        $ls2 = $lesson2->students()->first()->pivot;
        $ls2->attendance()->create(['id' => (string) Str::ulid(), 'status' => 'absent_unexcused']);

        $lesson3->students()->attach($student->id, ['id' => (string) Str::ulid()]);
        $ls3 = $lesson3->students()->first()->pivot;
        $ls3->attendance()->create(['id' => (string) Str::ulid(), 'status' => 'absent_excused']);

        $service = new InvoiceGeneratorService();
        $invoice = $service->generateForStudent($student, '2026-07');

        $this->assertNotNull($invoice);
        $this->assertEquals(50.00, $invoice->total_amount); // 2 hours (present + absent) * 25.00
        $this->assertCount(2, $invoice->items);
    }
}
