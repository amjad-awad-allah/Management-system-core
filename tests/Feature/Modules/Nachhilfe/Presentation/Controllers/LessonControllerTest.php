<?php

use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonModel;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    DB::table('module_settings')->insert([
        'id' => Str::ulid()->toString(),
        'module' => 'Nachhilfe',
        'enabled' => true,
    ]);
});
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    DB::table('settings')->updateOrInsert(
        ['key' => 'modules.Nachhilfe.enabled'],
        ['id' => (string) \Illuminate\Support\Str::ulid(), 'value' => 'true']
    );
});

test('can list lessons', function () {
    $user = User::forceCreate(['id' => (string) \Illuminate\Support\Str::ulid(), 'name' => 'T', 'email' => 't3@t.com', 'password' => 'p']);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/nachhilfe/lessons');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => []
        ]);
});

test('can book a lesson', function () {
    $user = User::forceCreate(['id' => (string) \Illuminate\Support\Str::ulid(), 'name' => 'T', 'email' => 't4@t.com', 'password' => 'p']);
    
    $student = Student::create(['id' => Str::ulid()->toString(), 'first_name' => 'S', 'last_name' => 'L', 'birth_date' => '2010-01-01', 'level' => 'Grade 10']);
    $teacher = Teacher::create(['id' => Str::ulid()->toString(), 'user_id' => $user->id, 'name' => 'T L']);
    $subject = SubjectModel::create(['id' => Str::ulid()->toString(), 'name' => 'Math']);
    $room = \App\Modules\Nachhilfe\Infrastructure\Models\Room::create(['id' => Str::ulid()->toString(), 'name' => 'Room 1', 'capacity' => 10]);

    $date = now()->addDays(1);
    \App\Modules\Nachhilfe\Infrastructure\Models\TeacherAvailability::create([
        'teacher_id' => $teacher->id,
        'day_of_week' => $date->dayOfWeek,
        'start_time' => '08:00',
        'end_time' => '18:00'
    ]);

    $this->withoutExceptionHandling();
    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/nachhilfe/lessons', [
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'room_id' => $room->id,
        'type' => 'individual',
        'date' => $date->format('Y-m-d'),
        'start_time' => '10:00',
        'end_time' => '11:00',
        'students' => [
            ['student_id' => $student->id]
        ]
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'scheduled')
        ->assertJsonPath('data.duration_minutes', 60);

    $this->assertDatabaseHas('lessons', [
        'teacher_id' => $teacher->id,
        'status' => 'scheduled'
    ]);
});

test('can filter lessons by student and teacher', function () {
    $user = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'T', 'email' => 't5@t.com', 'password' => 'p']);
    
    $student1 = Student::create(['id' => Str::ulid()->toString(), 'first_name' => 'S1', 'last_name' => 'L1', 'birth_date' => '2010-01-01', 'level' => 'Grade 10']);
    $student2 = Student::create(['id' => Str::ulid()->toString(), 'first_name' => 'S2', 'last_name' => 'L2', 'birth_date' => '2010-01-01', 'level' => 'Grade 10']);
    $teacher1 = Teacher::create(['id' => Str::ulid()->toString(), 'user_id' => $user->id, 'name' => 'T1']);
    $teacher2 = Teacher::create(['id' => Str::ulid()->toString(), 'user_id' => $user->id, 'name' => 'T2']);

    $subject = SubjectModel::create(['id' => Str::ulid()->toString(), 'name' => 'Math']);
    $room = \App\Modules\Nachhilfe\Infrastructure\Models\Room::create(['id' => Str::ulid()->toString(), 'name' => 'Room 1', 'capacity' => 10]);

    $lesson1 = \App\Modules\Nachhilfe\Infrastructure\Models\Lesson::create([
        'id' => Str::ulid()->toString(),
        'teacher_id' => $teacher1->id,
        'room_id' => $room->id,
        'subject_id' => $subject->id,
        'type' => 'individual',
        'date' => '2026-08-01',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_minutes' => 60,
        'status' => 'scheduled'
    ]);
    $lesson1->students()->attach($student1->id, ['id' => Str::ulid()->toString()]);

    $lesson2 = \App\Modules\Nachhilfe\Infrastructure\Models\Lesson::create([
        'id' => Str::ulid()->toString(),
        'teacher_id' => $teacher2->id,
        'room_id' => $room->id,
        'subject_id' => $subject->id,
        'type' => 'individual',
        'date' => '2026-08-01',
        'start_time' => '11:00',
        'end_time' => '12:00',
        'duration_minutes' => 60,
        'status' => 'scheduled'
    ]);
    $lesson2->students()->attach($student2->id, ['id' => Str::ulid()->toString()]);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/nachhilfe/lessons?teacher_id=' . $teacher1->id);
    $response->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.teacher_id', $teacher1->id);

    $response2 = $this->actingAs($user, 'sanctum')->getJson('/api/v1/nachhilfe/lessons?student_id=' . $student2->id);
    $response2->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.teacher_id', $teacher2->id);
});

test('can mark attendance and update it', function () {
    $user = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'T', 'email' => 't6@t.com', 'password' => 'p']);
    $student = Student::create(['id' => Str::ulid()->toString(), 'first_name' => 'Att', 'last_name' => 'St', 'birth_date' => '2010-01-01', 'level' => 'Grade 10']);
    $teacher = Teacher::create(['id' => Str::ulid()->toString(), 'user_id' => $user->id, 'name' => 'AttT']);
    
    $subject = SubjectModel::create(['id' => Str::ulid()->toString(), 'name' => 'Math']);
    $room = \App\Modules\Nachhilfe\Infrastructure\Models\Room::create(['id' => Str::ulid()->toString(), 'name' => 'Room 1', 'capacity' => 10]);

    $lesson = \App\Modules\Nachhilfe\Infrastructure\Models\Lesson::create([
        'id' => Str::ulid()->toString(),
        'teacher_id' => $teacher->id,
        'room_id' => $room->id,
        'subject_id' => $subject->id,
        'type' => 'individual',
        'date' => '2026-08-01',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_minutes' => 60,
        'status' => 'scheduled'
    ]);
    $lessonStudent = \App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent::create([
        'lesson_id' => $lesson->id,
        'student_id' => $student->id
    ]);
    $pivotId = $lessonStudent->id;

    // Mark present
    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/nachhilfe/lesson-students/{$pivotId}/attendance", ['status' => 'present']);
        
    $response->assertStatus(201);
        
    $this->assertDatabaseHas('attendances', ['lesson_student_id' => $pivotId, 'status' => 'present']);

    // Update to late
    $response2 = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/nachhilfe/lesson-students/{$pivotId}/attendance", ['status' => 'late']);
        
    $response2->assertStatus(201);
        
    $this->assertDatabaseHas('attendances', ['lesson_student_id' => $pivotId, 'status' => 'late']);
});

test('can edit a lesson', function () {
    $user = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'T', 'email' => 't_edit@t.com', 'password' => 'p']);
    $student = Student::create(['id' => Str::ulid()->toString(), 'first_name' => 'S', 'last_name' => 'L', 'birth_date' => '2010-01-01', 'level' => 'Grade 10']);
    $teacher = Teacher::create(['id' => Str::ulid()->toString(), 'user_id' => $user->id, 'name' => 'Teacher Edit']);
    $subject = SubjectModel::create(['id' => Str::ulid()->toString(), 'name' => 'Math']);
    $room = \App\Modules\Nachhilfe\Infrastructure\Models\Room::create(['id' => Str::ulid()->toString(), 'name' => 'Room 1', 'capacity' => 10]);

    $date = now()->addDays(1);
    \App\Modules\Nachhilfe\Infrastructure\Models\TeacherAvailability::create([
        'teacher_id' => $teacher->id,
        'day_of_week' => $date->dayOfWeek,
        'start_time' => '08:00',
        'end_time' => '18:00'
    ]);

    $lesson = \App\Modules\Nachhilfe\Infrastructure\Models\Lesson::create([
        'id' => Str::ulid()->toString(),
        'teacher_id' => $teacher->id,
        'room_id' => $room->id,
        'subject_id' => $subject->id,
        'type' => 'individual',
        'date' => $date->format('Y-m-d'),
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_minutes' => 60,
        'status' => 'scheduled'
    ]);

    $lesson->students()->attach($student->id, ['id' => Str::ulid()->toString()]);

    $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/nachhilfe/lessons/{$lesson->id}", [
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'room_id' => $room->id,
        'type' => 'individual',
        'date' => $date->format('Y-m-d'),
        'start_time' => '11:00',
        'end_time' => '12:00',
        'students' => [
            ['student_id' => $student->id]
        ]
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('lessons', [
        'id' => $lesson->id,
        'start_time' => '11:00',
        'end_time' => '12:00'
    ]);
});


