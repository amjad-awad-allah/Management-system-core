<?php

namespace Tests\Feature\Mobile;

use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Core\Models\Role;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Student']);
        Role::firstOrCreate(['name' => 'Teacher']);
    }

    public function test_student_can_fetch_their_lessons()
    {
        $user = User::create([
            'name' => 'Student User',
            'email' => 'student@test.com',
            'password' => bcrypt('password123')
        ]);
        $user->assignRole('Student');

        $student = Student::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'birth_date' => '2010-01-01',
            'grade' => 10,
            'school' => 'Test School'
        ]);

        $teacherUser = User::create([
            'name' => 'Teacher User',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password123')
        ]);
        $teacherUser->assignRole('Teacher');

        $teacher = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $teacherUser->id,
            'name' => 'Teacher'
        ]);

        $room = \App\Modules\Nachhilfe\Infrastructure\Models\Room::create([
            'id' => (string) Str::ulid(),
            'name' => 'Test Room',
            'capacity' => 10
        ]);

        $subject = \App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel::create([
            'id' => (string) Str::ulid(),
            'name' => 'Math'
        ]);

        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'type' => 'individual',
            'date' => '2026-08-01',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'duration_minutes' => 60,
            'status' => 'scheduled'
        ]);

        $lesson->students()->attach($student->id, ['id' => (string) Str::ulid()]);
        $ls = $lesson->students()->first()->pivot;
        $ls->attendance()->create(['id' => (string) Str::ulid(), 'status' => 'present']);

        $response = $this->actingAs($user)->getJson('/api/v1/mobile/student/lessons');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $lesson->id);
        $response->assertJsonPath('data.0.lesson_students.0.attendance.status', 'present');
    }

    public function test_teacher_can_fetch_their_lessons()
    {
        $teacherUser = User::create([
            'name' => 'Teacher User',
            'email' => 'teacher2@test.com',
            'password' => bcrypt('password123')
        ]);
        $teacherUser->assignRole('Teacher');

        $teacher = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $teacherUser->id,
            'name' => 'Teacher'
        ]);

        $room = \App\Modules\Nachhilfe\Infrastructure\Models\Room::create([
            'id' => (string) Str::ulid(),
            'name' => 'Test Room 2',
            'capacity' => 10
        ]);

        $subject = \App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel::create([
            'id' => (string) Str::ulid(),
            'name' => 'Physics'
        ]);

        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'type' => 'individual',
            'date' => '2026-08-01',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'duration_minutes' => 60,
            'status' => 'scheduled'
        ]);

        $student = Student::create([
            'id' => (string) Str::ulid(),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => '2010-01-01',
            'level' => 'G10'
        ]);
        $lesson->students()->attach($student->id, ['id' => (string) Str::ulid()]);
        $ls = $lesson->students()->first()->pivot;
        $ls->attendance()->create(['id' => (string) Str::ulid(), 'status' => 'absent_excused']);

        $response = $this->actingAs($teacherUser)->getJson('/api/v1/mobile/teacher/lessons');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $lesson->id);
        $response->assertJsonPath('data.0.lesson_students.0.attendance.status', 'absent_excused');
    }

    public function test_teacher_can_mark_attendance()
    {
        $teacherUser = User::create([
            'name' => 'Teacher User',
            'email' => 'teacher3@test.com',
            'password' => bcrypt('password123')
        ]);
        $teacherUser->assignRole('Teacher');

        $teacher = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $teacherUser->id,
            'name' => 'Teacher'
        ]);

        $room = \App\Modules\Nachhilfe\Infrastructure\Models\Room::create([
            'id' => (string) Str::ulid(),
            'name' => 'Test Room 3',
            'capacity' => 10
        ]);

        $subject = \App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel::create([
            'id' => (string) Str::ulid(),
            'name' => 'Math 3'
        ]);

        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'type' => 'individual',
            'date' => '2026-08-01',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'duration_minutes' => 60,
            'status' => 'scheduled'
        ]);

        $student = Student::create([
            'id' => (string) Str::ulid(),
            'first_name' => 'Test',
            'last_name' => 'Student 3',
            'birth_date' => '2010-01-01',
            'grade' => 10,
            'school' => 'Test School'
        ]);

        $lesson->students()->attach($student->id, ['id' => (string) Str::ulid()]);

        $pivot = \App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent::where('lesson_id', $lesson->id)
            ->where('student_id', $student->id)
            ->first();
        $pivotId = $pivot->id;

        $response = $this->actingAs($teacherUser)->postJson("/api/v1/mobile/teacher/lessons/{$lesson->id}/attendance", [
            'student_id' => $student->id,
            'status' => 'present',
            'notes' => 'Good student'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('attendances', [
            'lesson_student_id' => $pivotId,
            'status' => 'present',
            'note' => 'Good student'
        ]);
    }

    public function test_mobile_login_returns_token_and_roles()
    {
        $user = User::create([
            'name' => 'Mobile User',
            'email' => 'mobile@test.com',
            'password' => bcrypt('password123')
        ]);
        $user->assignRole('Student');

        $response = $this->postJson('/api/v1/mobile/login', [
            'email' => 'mobile@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'user' => [
                'id',
                'name',
                'email',
                'roles'
            ]
        ]);
        $this->assertContains('Student', $response->json('user.roles'));
    }

    public function test_student_can_fetch_contracts()
    {
        $user = User::create([
            'name' => 'Student User',
            'email' => 'student_contract@test.com',
            'password' => bcrypt('password123')
        ]);
        $user->assignRole('Student');

        $student = Student::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => '2010-01-01',
            'level' => 'G10'
        ]);

        $subject = \App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel::create([
            'id' => (string) Str::ulid(),
            'name' => 'English'
        ]);

        \App\Modules\Nachhilfe\Infrastructure\Models\StudentContract::create([
            'id' => (string) Str::ulid(),
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'hours_per_week' => 2.0,
            'hourly_rate' => 25.00,
            'start_date' => '2026-07-01',
            'status' => 'active'
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/mobile/student/subscriptions');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.student_id', $student->id);
        $response->assertJsonPath('data.0.subject.name', 'English');
    }

    public function test_student_can_fetch_invoices()
    {
        $user = User::create([
            'name' => 'Student User',
            'email' => 'student_invoice@test.com',
            'password' => bcrypt('password123')
        ]);
        $user->assignRole('Student');

        $student = Student::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => '2010-01-01',
            'level' => 'G10'
        ]);

        \App\Modules\Nachhilfe\Infrastructure\Models\Invoice::create([
            'id' => (string) Str::ulid(),
            'invoice_number' => 'INV-202607-0001',
            'student_id' => $student->id,
            'month' => '2026-07',
            'total_amount' => 150.00,
            'status' => 'unpaid',
            'due_date' => '2026-07-31'
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/mobile/student/invoices');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.student_id', $student->id);
        $response->assertJsonPath('data.0.invoice_number', 'INV-202607-0001');
    }

    public function test_teacher_can_fetch_payrolls()
    {
        $teacherUser = User::create([
            'name' => 'Teacher User',
            'email' => 'teacher_payroll@test.com',
            'password' => bcrypt('password123')
        ]);
        $teacherUser->assignRole('Teacher');

        $teacher = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $teacherUser->id,
            'name' => 'Mr. Smith'
        ]);

        \App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'month' => '2026-07',
            'total_amount' => 500.00,
            'status' => 'Draft'
        ]);

        $response = $this->actingAs($teacherUser)->getJson('/api/v1/mobile/teacher/payrolls');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.teacher_id', $teacher->id);
        $this->assertEquals(500.00, floatval($response->json('data.0.total_amount')));
    }

    public function test_teacher_cannot_mark_invalid_attendance_status()
    {
        $teacherUser = User::create([
            'name' => 'Teacher User',
            'email' => 'teacher_invalid@test.com',
            'password' => bcrypt('password123')
        ]);
        $teacherUser->assignRole('Teacher');

        $teacher = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $teacherUser->id,
            'name' => 'Teacher'
        ]);

        $room = \App\Modules\Nachhilfe\Infrastructure\Models\Room::create([
            'id' => (string) Str::ulid(),
            'name' => 'Test Room X',
            'capacity' => 10
        ]);

        $subject = \App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel::create([
            'id' => (string) Str::ulid(),
            'name' => 'Math X'
        ]);

        $lesson = Lesson::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $teacher->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'type' => 'individual',
            'date' => '2026-08-01',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'duration_minutes' => 60,
            'status' => 'scheduled'
        ]);

        $student = Student::create([
            'id' => (string) Str::ulid(),
            'first_name' => 'Test',
            'last_name' => 'Student X',
            'birth_date' => '2010-01-01',
            'level' => 'G10'
        ]);

        $lesson->students()->attach($student->id, ['id' => (string) Str::ulid()]);

        // Attempting to post the old 'absent' status which is now invalid (should be absent_excused or absent_unexcused)
        $response = $this->actingAs($teacherUser)->postJson("/api/v1/mobile/teacher/lessons/{$lesson->id}/attendance", [
            'student_id' => $student->id,
            'status' => 'absent',
            'notes' => 'Old status'
        ]);

        $response->assertStatus(422); // Validation failed
        $response->assertJsonValidationErrors(['status']);
    }
}
