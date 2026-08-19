<?php

use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\Holiday;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherAvailability;
use App\Modules\Nachhilfe\Application\Services\ConflictDetectionService;
use App\Modules\Nachhilfe\Application\Services\HolidayService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    \Illuminate\Support\Facades\Cache::flush();
    \Illuminate\Support\Facades\DB::table('module_settings')->insert([
        'id' => (string) Str::ulid(),
        'module' => 'Nachhilfe',
        'enabled' => true,
    ]);
    \App\Core\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
    $this->user->assignRole('Super Admin');
    $this->actingAs($this->user);

    $this->teacher = Teacher::create([
        'id' => (string) Str::ulid(),
        'name' => 'Dr. Schmidt',
        'email' => 'schmidt@nachhilfe.local',
        'hourly_rate' => 35.00,
        'user_id' => $this->user->id,
    ]);

    // Teacher available Monday-Friday 08:00 - 20:00
    for ($d = 1; $d <= 5; $d++) {
        TeacherAvailability::create([
            'id' => (string) Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'day_of_week' => $d,
            'start_time' => '08:00',
            'end_time' => '20:00',
        ]);
    }

    $this->room = Room::create([
        'id' => (string) Str::ulid(),
        'name' => 'Room 101',
        'capacity' => 10,
        'type' => 'physical',
    ]);

    $this->subject = SubjectModel::create([
        'id' => (string) Str::ulid(),
        'name' => 'Mathematics',
        'code' => 'MATH-01',
    ]);
});

test('calendar feed API returns structured response with range, meta Europe/Berlin, holidays and summary', function () {
    // Create a lesson on Monday 10:00-11:00
    $today = Carbon::now('Europe/Berlin');
    $monday = $today->copy()->startOfWeek(1)->toDateString();

    $lesson = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'group',
        'date' => $monday,
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_minutes' => 60,
        'status' => 'scheduled',
    ]);

    // Create a custom center holiday
    Holiday::create([
        'id' => (string) Str::ulid(),
        'source' => 'custom',
        'type' => 'center',
        'name' => 'Center Anniversary',
        'start_date' => $monday,
        'end_date' => $monday,
        'state' => null,
        'is_active' => true,
    ]);

    $response = $this->getJson("/api/v1/nachhilfe/calendar?start_date={$monday}&end_date={$monday}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'range' => ['start', 'end'],
            'lessons' => [
                '*' => ['id', 'date', 'start_time', 'end_time', 'is_in_progress', 'has_conflict', 'conflict_types']
            ],
            'holidays' => [
                '*' => ['id', 'source', 'type', 'name', 'start_date', 'end_date']
            ],
            'meta' => ['timezone', 'week_starts_on', 'state'],
            'summary' => ['total', 'scheduled', 'in_progress', 'completed', 'cancelled', 'conflicts'],
        ]);

    expect($response->json('meta.timezone'))->toBe('Europe/Berlin');
    expect($response->json('meta.week_starts_on'))->toBe('monday');
    expect($response->json('summary.total'))->toBe(1);
});

test('conflict detection mathematically flags overlaps and ignores cancelled lessons', function () {
    $service = new ConflictDetectionService();
    $date = '2026-09-01';

    // Lesson A: 10:00 - 11:00 (Scheduled)
    $lessonA = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'group',
        'date' => $date,
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_minutes' => 60,
        'status' => 'scheduled',
    ]);

    // Lesson B: 11:00 - 12:00 (Scheduled - Back to back after, NO conflict)
    $lessonB = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'group',
        'date' => $date,
        'start_time' => '11:00',
        'end_time' => '12:00',
        'duration_minutes' => 60,
        'status' => 'scheduled',
    ]);

    // Lesson C: 08:30 - 09:30 (Scheduled - Completely before, NO conflict)
    $lessonC = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'group',
        'date' => $date,
        'start_time' => '08:30',
        'end_time' => '09:30',
        'duration_minutes' => 60,
        'status' => 'scheduled',
    ]);

    // Lesson D: 10:30 - 11:30 (Cancelled - Cancelled lesson should NOT cause conflict)
    $lessonD = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'group',
        'date' => $date,
        'start_time' => '10:30',
        'end_time' => '11:30',
        'duration_minutes' => 60,
        'status' => 'cancelled',
    ]);

    // Lesson E: 09:59 - 10:30 (Scheduled - Overlaps Lesson A at 10:00-10:30 -> CONFLICT)
    $lessonE = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'group',
        'date' => $date,
        'start_time' => '09:59',
        'end_time' => '10:30',
        'duration_minutes' => 31,
        'status' => 'scheduled',
    ]);

    $lessons = collect([$lessonA, $lessonB, $lessonC, $lessonD, $lessonE]);
    $conflicts = $service->detectConflicts($lessons);

    expect($conflicts[$lessonA->id]['has_conflict'])->toBeTrue();
    expect($conflicts[$lessonE->id]['has_conflict'])->toBeTrue();
    expect($conflicts[$lessonB->id]['has_conflict'])->toBeFalse();
    expect($conflicts[$lessonC->id]['has_conflict'])->toBeFalse();
});

test('booking a lesson in an overlapping slot returns HTTP 422 Unprocessable Entity', function () {
    $date = '2026-09-10';

    $student = \App\Modules\Nachhilfe\Infrastructure\Models\Student::create([
        'id' => (string) Str::ulid(),
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'school' => 'Gymnasium',
        'grade' => '10',
    ]);

    // Existing lesson 10:00 - 11:00
    Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'group',
        'date' => $date,
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_minutes' => 60,
        'status' => 'scheduled',
    ]);

    // Attempt to book overlapping lesson 10:30 - 11:30 with same teacher
    $payload = [
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'group',
        'date' => $date,
        'start_time' => '10:30',
        'end_time' => '11:30',
        'students' => [
            ['student_id' => $student->id]
        ],
    ];

    $response = $this->postJson('/api/v1/nachhilfe/lessons', $payload);

    $response->assertStatus(422)
        ->assertJsonStructure(['message', 'conflicts']);
});

test('DST clock transition in Europe/Berlin calculates date and time math accurately', function () {
    // Germany DST transition in March (Clock jumps from 02:00 to 03:00)
    $marchDstDate = '2026-03-29';

    $lesson = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'group',
        'date' => $marchDstDate,
        'start_time' => '09:00',
        'end_time' => '10:00',
        'duration_minutes' => 60,
        'status' => 'scheduled',
    ]);

    $response = $this->getJson("/api/v1/nachhilfe/calendar?start_date={$marchDstDate}&end_date={$marchDstDate}");

    $response->assertStatus(200);
    expect($response->json('lessons.0.date'))->toBe($marchDstDate);
    expect($response->json('meta.timezone'))->toBe('Europe/Berlin');
});

test('teacher user receives only their own lessons in calendar feed', function () {
    $teacherUser = User::factory()->create();
    $teacherRole = \App\Core\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
    $teacherUser->assignRole($teacherRole);

    $teacher2 = Teacher::create([
        'id' => (string) Str::ulid(),
        'user_id' => $teacherUser->id,
        'name' => 'Frau Weber',
        'email' => 'weber@nachhilfe.local',
        'hourly_rate' => 30.00,
    ]);

    $date = '2026-09-15';

    // Lesson 1 for teacher 1
    Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'group',
        'date' => $date,
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_minutes' => 60,
        'status' => 'scheduled',
    ]);

    // Lesson 2 for teacher 2
    $lesson2 = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $teacher2->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'type' => 'group',
        'date' => $date,
        'start_time' => '12:00',
        'end_time' => '13:00',
        'duration_minutes' => 60,
        'status' => 'scheduled',
    ]);

    $response = $this->actingAs($teacherUser)
        ->getJson("/api/v1/nachhilfe/calendar?start_date={$date}&end_date={$date}");

    $response->assertStatus(200);
    expect(count($response->json('lessons')))->toBe(1);
    expect($response->json('lessons.0.id'))->toBe($lesson2->id);
});
