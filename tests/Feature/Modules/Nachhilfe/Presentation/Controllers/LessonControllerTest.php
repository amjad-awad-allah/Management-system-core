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

    $this->withoutExceptionHandling();
    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/nachhilfe/lessons', [
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'room_id' => $room->id,
        'type' => 'individual',
        'date' => now()->addDays(1)->format('Y-m-d'),
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
