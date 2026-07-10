<?php

namespace Tests\Feature\Modules\Nachhilfe\Domain\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Nachhilfe\Domain\Services\PayrollGeneratorService;
use App\Modules\Nachhilfe\Domain\Services\InvoiceGeneratorService;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Core\Models\User;
use Illuminate\Support\Str;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use App\Modules\Nachhilfe\Infrastructure\Models\Invoice;

class GeneratorIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_regenerate_payroll_if_already_finalized()
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
        Lesson::create([
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

        $service = new PayrollGeneratorService();
        $payroll1 = $service->generateForTeacher($teacher, '2026-07');
        
        $this->assertNotNull($payroll1);
        $this->assertEquals('Draft', $payroll1->status);

        // Regenerating should replace the draft
        $payroll2 = $service->generateForTeacher($teacher, '2026-07');
        $this->assertNotNull($payroll2);
        $this->assertEquals(1, TeacherPayroll::count());
        $this->assertNotEquals($payroll1->id, $payroll2->id); // Draft deleted and recreated

        // Now finalize the payroll
        $payroll2->update(['status' => 'Paid']);

        // Try generating again
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('A finalized payroll already exists for this month.');
        
        $service->generateForTeacher($teacher, '2026-07');
    }

    public function test_it_does_not_regenerate_invoice_if_already_finalized()
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

        $lesson = Lesson::create([
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

        $lesson->students()->attach($student->id, ['id' => (string) Str::ulid()]);
        $ls = $lesson->students()->first()->pivot;
        $ls->attendance()->create(['id' => (string) Str::ulid(), 'status' => 'present']);

        $service = new InvoiceGeneratorService();
        $invoice1 = $service->generateForStudent($student, '2026-07');
        
        $this->assertNotNull($invoice1);
        $this->assertEquals('draft', $invoice1->status);

        // Finalize
        $invoice1->update(['status' => 'paid']);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('A finalized invoice already exists for this month.');
        
        $service->generateForStudent($student, '2026-07');
    }
}
