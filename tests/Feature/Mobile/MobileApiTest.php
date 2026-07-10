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

        $response = $this->actingAs($user)->getJson('/api/v1/mobile/student/lessons');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $lesson->id);
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

        $response = $this->actingAs($teacherUser)->getJson('/api/v1/mobile/teacher/lessons');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $lesson->id);
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
}
