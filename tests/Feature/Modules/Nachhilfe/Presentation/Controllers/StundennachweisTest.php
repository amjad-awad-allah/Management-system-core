<?php

use App\Core\Models\User;
use App\Core\Models\Role;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use App\Modules\Nachhilfe\Infrastructure\Models\Attendance;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    cache()->flush();

    // Ensure settings
    DB::table('module_settings')->insert([
        'id' => Str::ulid()->toString(),
        'module' => 'Nachhilfe',
        'enabled' => true,
    ]);

    $this->adminRole = Role::firstOrCreate(['name' => 'Super Admin']);
    $this->teacherRole = Role::firstOrCreate(['name' => 'Teacher']);
    $this->parentRole = Role::firstOrCreate(['name' => 'Student']);

    // Admin
    $this->adminUser = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'p']);
    $this->adminUser->assignRole($this->adminRole);
});

test('admin can generate Stundennachweis PDF containing only present or late lessons', function () {
    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Stunden';
    $student->last_name = 'Student';
    $student->save();

    $teacherUser = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'Teacher', 'email' => 'teacher@test.com', 'password' => 'p']);
    $teacherUser->assignRole($this->teacherRole);

    $teacher = new Teacher();
    $teacher->id = (string) Str::ulid();
    $teacher->user_id = $teacherUser->id;
    $teacher->name = 'Teacher';
    $teacher->save();

    $roomId = (string) Str::ulid();
    DB::table('rooms')->insert(['id' => $roomId, 'name' => 'Room A', 'is_active' => true]);
    $subjectId = (string) Str::ulid();
    DB::table('subjects')->insert(['id' => $subjectId, 'name' => 'Mathematics', 'code' => 'MATH', 'is_active' => true]);

    // Create 3 lessons in July
    // Lesson 1: Present (should be in PDF)
    $lesson1 = Lesson::create([
        'id' => (string) Str::ulid(), 'teacher_id' => $teacher->id, 'room_id' => $roomId, 'subject_id' => $subjectId,
        'date' => '2026-07-05', 'start_time' => '14:00:00', 'end_time' => '15:30:00', 'duration_minutes' => 90, 'status' => 'completed'
    ]);
    $ls1 = LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $lesson1->id, 'student_id' => $student->id]);
    Attendance::create(['id' => (string) Str::ulid(), 'lesson_student_id' => $ls1->id, 'status' => 'present', 'marked_by' => $teacherUser->id]);

    // Lesson 2: Late (should be in PDF)
    $lesson2 = Lesson::create([
        'id' => (string) Str::ulid(), 'teacher_id' => $teacher->id, 'room_id' => $roomId, 'subject_id' => $subjectId,
        'date' => '2026-07-12', 'start_time' => '14:00:00', 'end_time' => '15:30:00', 'duration_minutes' => 90, 'status' => 'completed'
    ]);
    $ls2 = LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $lesson2->id, 'student_id' => $student->id]);
    Attendance::create(['id' => (string) Str::ulid(), 'lesson_student_id' => $ls2->id, 'status' => 'late', 'marked_by' => $teacherUser->id]);

    // Lesson 3: Excused absence (should NOT be in PDF)
    $lesson3 = Lesson::create([
        'id' => (string) Str::ulid(), 'teacher_id' => $teacher->id, 'room_id' => $roomId, 'subject_id' => $subjectId,
        'date' => '2026-07-19', 'start_time' => '14:00:00', 'end_time' => '15:30:00', 'duration_minutes' => 90, 'status' => 'completed'
    ]);
    $ls3 = LessonStudent::create(['id' => (string) Str::ulid(), 'lesson_id' => $lesson3->id, 'student_id' => $student->id]);
    Attendance::create(['id' => (string) Str::ulid(), 'lesson_student_id' => $ls3->id, 'status' => 'absent_excused', 'marked_by' => $teacherUser->id]);

    // Call endpoint
    $response = $this->actingAs($this->adminUser, 'sanctum')
        ->get("/api/v1/nachhilfe/students/{$student->id}/stundennachweis?month=2026-07");

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('Content-Disposition', 'attachment; filename="Stundennachweis_Student_2026-07.pdf"');
});

test('parent can generate their own child Stundennachweis but is isolated from others', function () {
    $parentUser = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'Parent', 'email' => 'parent@test.com', 'password' => 'p']);
    $parentUser->assignRole($this->parentRole);

    $studentA = new Student();
    $studentA->id = (string) Str::ulid();
    $studentA->user_id = $parentUser->id; // Own child
    $studentA->first_name = 'ChildA';
    $studentA->last_name = 'A';
    $studentA->save();

    $studentB = new Student();
    $studentB->id = (string) Str::ulid(); // Another student
    $studentB->first_name = 'ChildB';
    $studentB->last_name = 'B';
    $studentB->save();

    // 1. Download own child Stundennachweis -> allowed
    $response = $this->actingAs($parentUser, 'sanctum')
        ->get("/api/v1/nachhilfe/students/{$studentA->id}/stundennachweis?month=2026-07");
    $response->assertStatus(200);

    // 2. Download another student Stundennachweis -> forbidden 403
    $response = $this->actingAs($parentUser, 'sanctum')
        ->get("/api/v1/nachhilfe/students/{$studentB->id}/stundennachweis?month=2026-07");
    $response->assertStatus(403);
});
