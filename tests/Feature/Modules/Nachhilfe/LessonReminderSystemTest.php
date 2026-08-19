<?php

use App\Core\Models\User;
use App\Core\Models\Role;
use App\Core\Notification\Models\NotificationEvent;
use App\Core\Notification\Models\NotificationOutbox;
use App\Modules\Nachhilfe\Application\Queries\ReminderCandidateQuery;
use App\Modules\Nachhilfe\Application\Services\LessonReminderService;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    cache()->flush();

    DB::table('module_settings')->insert([
        'id' => Str::ulid()->toString(),
        'module' => 'Nachhilfe',
        'enabled' => true,
    ]);

    // Roles
    $this->adminRole = Role::firstOrCreate(['name' => 'Admin']);
    $this->teacherRole = Role::firstOrCreate(['name' => 'Teacher']);
    $this->studentRole = Role::firstOrCreate(['name' => 'Student']);

    // Admin
    $this->admin = User::forceCreate([
        'id' => (string) Str::ulid(),
        'name' => 'Admin User',
        'email' => 'admin_' . Str::random(5) . '@admin.com',
        'password' => 'password',
        'locale' => 'de',
    ]);
    $this->admin->assignRole($this->adminRole);

    // Teacher User & Model
    $this->teacherUser = User::forceCreate([
        'id' => (string) Str::ulid(),
        'name' => 'Herr Thomas Müller',
        'email' => 'teacher_' . Str::random(5) . '@schule.de',
        'password' => 'password',
        'locale' => 'de',
    ]);
    $this->teacherUser->assignRole($this->teacherRole);

    $this->teacher = Teacher::create([
        'id' => (string) Str::ulid(),
        'user_id' => $this->teacherUser->id,
        'name' => 'Herr Thomas Müller',
        'hourly_rate' => 35.00,
    ]);

    // Parent User
    $this->parentUser = User::forceCreate([
        'id' => (string) Str::ulid(),
        'name' => 'Frau Erika Mustermann',
        'email' => 'parent_' . Str::random(5) . '@eltern.de',
        'password' => 'password',
        'locale' => 'de',
    ]);
    $this->parentUser->assignRole($this->studentRole);

    // Student 1 (Has own User account + linked parent)
    $this->studentUser = User::forceCreate([
        'id' => (string) Str::ulid(),
        'name' => 'Max Mustermann',
        'email' => 'student_' . Str::random(5) . '@schueler.de',
        'password' => 'password',
        'locale' => 'de',
    ]);
    $this->studentUser->assignRole($this->studentRole);

    $this->studentWithUser = Student::create([
        'id' => (string) Str::ulid(),
        'user_id' => $this->studentUser->id,
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'parent_name' => 'Frau Erika Mustermann',
        'parent_email' => $this->parentUser->email,
    ]);

    // Student 2 (Young child without own user, but has linked parent)
    $this->studentChild = Student::create([
        'id' => (string) Str::ulid(),
        'user_id' => null,
        'first_name' => 'Lukas',
        'last_name' => 'Mustermann',
        'parent_name' => 'Frau Erika Mustermann',
        'parent_email' => $this->parentUser->email,
    ]);

    // Subject & Room
    $this->subject = SubjectModel::create([
        'id' => (string) Str::ulid(),
        'name' => 'Mathematik',
    ]);

    $this->room = Room::create([
        'id' => (string) Str::ulid(),
        'name' => 'Raum 101',
        'capacity' => 6,
    ]);
});

test('A. Strict Boundary Tests for 24h and 2h sliding windows', function () {
    $now = CarbonImmutable::parse('2026-08-20 10:00:00', 'Europe/Berlin');
    $query = app(ReminderCandidateQuery::class);

    // 24h Window is [now + 23h50m, now + 24h10m] -> [2026-08-21 09:50:00, 2026-08-21 10:10:00]

    // 1. 23h 49m 59s -> 09:49:59 (EXCLUDED)
    $l1 = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-21',
        'start_time' => '09:49:59',
        'end_time' => '10:49:59',
        'status' => 'scheduled',
    ]);

    // 2. 23h 50m 00s -> 09:50:00 (INCLUDED - boundary)
    $l2 = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-21',
        'start_time' => '09:50:00',
        'end_time' => '10:50:00',
        'status' => 'scheduled',
    ]);

    // 3. 24h 10m 00s -> 10:10:00 (INCLUDED - boundary)
    $l3 = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-21',
        'start_time' => '10:10:00',
        'end_time' => '11:10:00',
        'status' => 'scheduled',
    ]);

    // 4. 24h 10m 01s -> 10:10:01 (EXCLUDED)
    $l4 = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-21',
        'start_time' => '10:10:01',
        'end_time' => '11:10:01',
        'status' => 'scheduled',
    ]);

    $candidates24h = $query->getCandidatesForWindow('24h', $now);
    $candidateIds = $candidates24h->pluck('id')->toArray();

    expect($candidateIds)->not->toContain($l1->id);
    expect($candidateIds)->toContain($l2->id);
    expect($candidateIds)->toContain($l3->id);
    expect($candidateIds)->not->toContain($l4->id);

    // 2h Window is [now + 1h50m, now + 2h10m] -> [2026-08-20 11:50:00, 2026-08-20 12:10:00]
    $l2h_boundary = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-20',
        'start_time' => '12:00:00',
        'end_time' => '13:00:00',
        'status' => 'scheduled',
    ]);

    $candidates2h = $query->getCandidatesForWindow('2h', $now);
    expect($candidates2h->pluck('id'))->toContain($l2h_boundary->id);
});

test('B. Recipient Resolution: teacher, student with user, and parent are accurately resolved', function () {
    $now = CarbonImmutable::parse('2026-08-20 10:00:00', 'Europe/Berlin');

    $lesson = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-21',
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'scheduled',
    ]);
    $lesson->students()->attach([
        $this->studentWithUser->id => ['id' => (string) Str::ulid()],
        $this->studentChild->id => ['id' => (string) Str::ulid()],
    ]);

    $service = app(LessonReminderService::class);
    $stats = $service->process($now);

    expect($stats['lessons_24h'])->toBe(1);
    // Recipients: 1 Teacher + 1 Student (with user) + 1 Parent = 3 unique recipients
    expect($stats['enqueued_24h'])->toBe(3);

    // Verify NotificationEvent records
    $events = NotificationEvent::where('event_id', $lesson->id)->get();
    expect($events->count())->toBe(3);

    $recipientUserIds = $events->pluck('recipient_user_id')->toArray();
    expect($recipientUserIds)->toContain($this->teacherUser->id);
    expect($recipientUserIds)->toContain($this->studentUser->id);
    expect($recipientUserIds)->toContain($this->parentUser->id);
});

test('C. Multi-Run Idempotency: repeated scheduler runs create zero duplicate notification events', function () {
    $now = CarbonImmutable::parse('2026-08-20 10:00:00', 'Europe/Berlin');

    $lesson = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-21',
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'scheduled',
    ]);
    $lesson->students()->attach([$this->studentWithUser->id => ['id' => (string) Str::ulid()]]);

    $service = app(LessonReminderService::class);

    // Run 1
    $service->process($now);
    // Run 2
    $service->process($now);
    // Run 3
    $service->process($now);
    // Run 4
    $service->process($now);

    // 3 unique recipients (1 teacher, 1 student, 1 parent)
    expect(NotificationEvent::where('event_id', $lesson->id)->count())->toBe(3);
    // 3 recipients * 3 default channels (in_app, chat, whatsapp) = exactly 9 outbox records
    expect(NotificationOutbox::whereIn('notification_event_id', NotificationEvent::where('event_id', $lesson->id)->pluck('id'))->count())->toBe(9);
});

test('D. Rescheduling Lifecycle: changing lesson date or time cancels old pending/failed reminder jobs', function () {
    $now = CarbonImmutable::parse('2026-08-20 10:00:00', 'Europe/Berlin');

    $lesson = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-21',
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'scheduled',
    ]);
    $lesson->students()->attach([$this->studentWithUser->id => ['id' => (string) Str::ulid()]]);

    // 1. Generate reminders
    app(LessonReminderService::class)->process($now);

    $outboxItems = NotificationOutbox::whereIn('notification_event_id', NotificationEvent::where('event_id', $lesson->id)->pluck('id'))->get();
    expect($outboxItems->where('status', 'pending')->count())->toBeGreaterThan(0);

    // Mark one as processing to simulate in-flight driver progress
    $processingJob = $outboxItems->first();
    $processingJob->update(['status' => 'processing']);

    // Ensure teacher has availability on new date 2026-08-25
    \App\Modules\Nachhilfe\Infrastructure\Models\TeacherAvailability::create([
        'teacher_id' => $this->teacher->id,
        'day_of_week' => \Carbon\Carbon::parse('2026-08-25')->dayOfWeek,
        'start_time' => '08:00',
        'end_time' => '18:00',
    ]);

    // 2. Reschedule lesson via Controller API
    $response = $this->actingAs($this->admin, 'sanctum')
        ->putJson("/api/v1/nachhilfe/lessons/{$lesson->id}", [
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'type' => 'individual',
            'date' => '2026-08-25', // Rescheduled to new date
            'start_time' => '14:00',
            'end_time' => '15:00',
            'students' => [
                ['student_id' => $this->studentWithUser->id],
            ],
        ]);

    $response->assertStatus(200);

    // 3. Verify old pending records became cancelled, but processing record was untouched
    $updatedOutbox = NotificationOutbox::whereIn('notification_event_id', NotificationEvent::where('event_id', $lesson->id)->pluck('id'))->get();
    
    expect($updatedOutbox->where('status', 'cancelled')->count())->toBe($outboxItems->count() - 1);
    expect($updatedOutbox->where('id', $processingJob->id)->first()->status)->toBe('processing');
});

test('E. DST European Daylight Saving Time transition preserves instant calculation accuracy', function () {
    // 2026 CET -> CEST clock change in Europe/Berlin happens on 2026-03-29 (from 02:00 to 03:00)
    $nowPreDst = CarbonImmutable::parse('2026-03-28 10:00:00', 'Europe/Berlin');

    $lessonPostDst = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-03-29',
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'scheduled',
    ]);

    $query = app(ReminderCandidateQuery::class);
    $candidates = $query->getCandidatesForWindow('24h', $nowPreDst);

    expect($candidates->pluck('id'))->toContain($lessonPostDst->id);
});

test('F. Observability: payload contains immutable snapshot, correlation_id, and lesson metadata', function () {
    $now = CarbonImmutable::parse('2026-08-20 10:00:00', 'Europe/Berlin');

    $lesson = Lesson::create([
        'id' => (string) Str::ulid(),
        'teacher_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'subject_id' => $this->subject->id,
        'date' => '2026-08-21',
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => 'scheduled',
    ]);
    $lesson->students()->attach([$this->studentWithUser->id => ['id' => (string) Str::ulid()]]);

    app(LessonReminderService::class)->process($now);

    $outbox = NotificationOutbox::whereIn('notification_event_id', NotificationEvent::where('event_id', $lesson->id)->pluck('id'))->first();

    expect($outbox)->not->toBeNull();
    expect($outbox->correlation_id)->not->toBeEmpty();
    expect($outbox->payload['schema_version'])->toBe(1);
    expect($outbox->payload['data']['lesson_id'])->toBe($lesson->id);
    expect($outbox->payload['data']['reminder_window'])->toBe('24h');
    expect($outbox->payload['data']['subject'])->toBe('Mathematik');
});
