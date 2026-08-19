<?php

use App\Core\Models\User;
use App\Core\Models\Role;
use App\Core\Notification\Models\NotificationEvent;
use App\Core\Notification\Models\NotificationOutbox;
use App\Core\Notification\Services\NotificationService;
use App\Core\Console\Commands\OutboxWorkCommand;
use App\Core\Notification\Drivers\InAppDriver;
use App\Core\Notification\Drivers\ChatDriver;
use App\Core\Notification\Drivers\WhatsAppDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    cache()->flush();

    $this->admin = User::forceCreate([
        'id' => (string) Str::ulid(),
        'name' => 'Admin User',
        'email' => 'admin_' . Str::random(5) . '@admin.com',
        'password' => 'password',
        'locale' => 'de',
    ]);
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);
    $this->admin->assignRole($adminRole);

    $this->recipient = User::forceCreate([
        'id' => (string) Str::ulid(),
        'name' => 'Student Recipient',
        'email' => 'student_' . Str::random(5) . '@student.com',
        'password' => 'password',
        'locale' => 'de',
    ]);

    $this->notificationService = app(NotificationService::class);
});

test('1. Business Idempotency: same entity event cannot generate duplicate notification_events', function () {
    $lessonId = (string) Str::ulid();
    $payload = [
        'schema_version' => 1,
        'template' => 'lesson_reminder_default',
        'template_version' => 1,
        'locale' => 'de',
        'data' => [
            'lesson_id' => $lessonId,
            'subject' => 'Mathematik',
            'start_time' => '14:00',
        ],
    ];

    // First enqueue
    $event1 = $this->notificationService->enqueue(
        'lesson',
        $lessonId,
        'reminder_24h',
        $this->recipient->id,
        ['in_app', 'whatsapp'],
        $payload,
        'tenant_alpha'
    );

    // Second duplicate enqueue attempt
    $event2 = $this->notificationService->enqueue(
        'lesson',
        $lessonId,
        'reminder_24h',
        $this->recipient->id,
        ['in_app', 'whatsapp'],
        $payload,
        'tenant_alpha'
    );

    expect($event1->id)->toBe($event2->id);
    expect(NotificationEvent::where('tenant_id', 'tenant_alpha')->count())->toBe(1);
    expect(NotificationOutbox::where('notification_event_id', $event1->id)->count())->toBe(2);
});

test('2. Entity vs Notification Type: distinct notification types for same entity produce separate events', function () {
    $lessonId = (string) Str::ulid();
    $payload = ['schema_version' => 1, 'template' => 'lesson_reminder', 'template_version' => 1, 'locale' => 'de', 'data' => []];

    // 24h reminder
    $event24h = $this->notificationService->enqueue(
        'lesson',
        $lessonId,
        'reminder_24h',
        $this->recipient->id,
        ['in_app'],
        $payload,
        'default'
    );

    // 2h reminder for same lesson
    $event2h = $this->notificationService->enqueue(
        'lesson',
        $lessonId,
        'reminder_2h',
        $this->recipient->id,
        ['in_app'],
        $payload,
        'default'
    );

    expect($event24h->id)->not->toBe($event2h->id);
    expect(NotificationEvent::where('event_id', $lessonId)->count())->toBe(2);
});

test('3. 1:N Outbox Fanout: single event creates independent channel jobs with shared correlation_id', function () {
    $invoiceId = (string) Str::ulid();
    $payload = ['schema_version' => 1, 'template' => 'invoice_created', 'template_version' => 1, 'locale' => 'de', 'data' => ['invoice_id' => $invoiceId]];

    $event = $this->notificationService->enqueue(
        'invoice',
        $invoiceId,
        'invoice_created',
        $this->recipient->id,
        ['in_app', 'chat', 'whatsapp'],
        $payload
    );

    $outboxItems = NotificationOutbox::where('notification_event_id', $event->id)->get();
    expect($outboxItems->count())->toBe(3);

    $correlationIds = $outboxItems->pluck('correlation_id')->unique();
    expect($correlationIds->count())->toBe(1);

    $channels = $outboxItems->pluck('channel')->toArray();
    expect($channels)->toContain('in_app', 'chat', 'whatsapp');
});

test('4. Worker non-blocking execution: outbox:work claims and transitions pending jobs to sent', function () {
    $lessonId = (string) Str::ulid();
    $payload = ['schema_version' => 1, 'template' => 'lesson_reminder', 'template_version' => 1, 'locale' => 'de', 'data' => ['title' => 'Erinnerung']];

    $this->notificationService->enqueue(
        'lesson',
        $lessonId,
        'reminder_24h',
        $this->recipient->id,
        ['in_app', 'chat'],
        $payload
    );

    expect(NotificationOutbox::where('status', 'pending')->count())->toBe(2);

    Artisan::call('outbox:work', ['--once' => true]);

    expect(NotificationOutbox::where('status', 'sent')->count())->toBe(2);
    expect(NotificationOutbox::where('status', 'pending')->count())->toBe(0);
});

test('5. DLQ Failure Transition: job moves to dead status after exceeding max_attempts', function () {
    $event = NotificationEvent::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'default',
        'event_type' => 'invoice',
        'event_id' => (string) Str::ulid(),
        'notification_type' => 'overdue_alert',
        'recipient_user_id' => $this->recipient->id,
        'status' => 'generated',
    ]);

    $failingOutbox = NotificationOutbox::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'default',
        'notification_event_id' => $event->id,
        'correlation_id' => (string) Str::ulid(),
        'recipient_user_id' => 'non_existent_user_for_failure',
        'channel' => 'invalid_channel',
        'payload' => ['schema_version' => 1],
        'status' => 'pending',
        'attempts' => 2,
        'max_attempts' => 3,
        'available_at' => now(),
    ]);

    $mockWhatsApp = Mockery::mock(WhatsAppDriver::class);
    $mockInApp = Mockery::mock(InAppDriver::class);
    $mockChat = Mockery::mock(ChatDriver::class);

    $worker = app(OutboxWorkCommand::class);
    $worker->processNotificationOutboxBatch($mockInApp, $mockChat, $mockWhatsApp);

    $failingOutbox->refresh();
    expect($failingOutbox->status)->toBe('dead');
    expect($failingOutbox->attempts)->toBe(3);
});

test('6. In-Place DLQ Retry API: updates existing dead record preserving id, correlation_id, and payload', function () {
    $event = NotificationEvent::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'default',
        'event_type' => 'payment',
        'event_id' => (string) Str::ulid(),
        'notification_type' => 'receipt',
        'recipient_user_id' => $this->recipient->id,
        'status' => 'generated',
    ]);

    $deadOutbox = NotificationOutbox::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'default',
        'notification_event_id' => $event->id,
        'correlation_id' => 'CORRELATION_AUDIT_KEY_123',
        'recipient_user_id' => $this->recipient->id,
        'channel' => 'whatsapp',
        'payload' => ['schema_version' => 1, 'template' => 'vital_alert'],
        'status' => 'dead',
        'attempts' => 3,
        'max_attempts' => 3,
        'available_at' => now()->subHour(),
        'error_log' => 'Network timeout after 3 attempts',
    ]);

    $response = $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/v1/admin/notifications/outbox/{$deadOutbox->id}/retry");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Outbox record successfully scheduled for retry',
            'outbox' => [
                'id' => $deadOutbox->id,
                'status' => 'pending',
                'attempts' => 0,
                'correlation_id' => 'CORRELATION_AUDIT_KEY_123',
                'error_log' => null,
            ],
        ]);

    expect(NotificationOutbox::where('id', $deadOutbox->id)->count())->toBe(1);
});

test('7. Business and Delivery State Isolation: event generated state is distinct from outbox delivery status', function () {
    $lessonId = (string) Str::ulid();
    $payload = ['schema_version' => 1, 'template' => 'test', 'template_version' => 1, 'locale' => 'de', 'data' => []];

    $event = $this->notificationService->enqueue(
        'lesson',
        $lessonId,
        'reminder_24h',
        $this->recipient->id,
        ['whatsapp'],
        $payload
    );

    // Assert domain event was generated
    expect($event->status)->toBe('generated');

    $outbox = NotificationOutbox::where('notification_event_id', $event->id)->first();
    expect($outbox->status)->toBe('pending');

    // Simulate worker failure
    $outbox->update(['status' => 'dead', 'attempts' => 3]);

    $event->refresh();
    expect($event->status)->toBe('generated'); // Domain event state remains generated
    expect($outbox->fresh()->status)->toBe('dead');
});

test('8. Cross-Tenant Isolation: tenant queries strictly isolate outbox records', function () {
    $eventA = NotificationEvent::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'tenant_A',
        'event_type' => 'lesson',
        'event_id' => (string) Str::ulid(),
        'notification_type' => 'reminder_24h',
        'recipient_user_id' => $this->recipient->id,
        'status' => 'generated',
    ]);

    $eventB = NotificationEvent::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'tenant_B',
        'event_type' => 'lesson',
        'event_id' => (string) Str::ulid(),
        'notification_type' => 'reminder_24h',
        'recipient_user_id' => $this->recipient->id,
        'status' => 'generated',
    ]);

    NotificationOutbox::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'tenant_A',
        'notification_event_id' => $eventA->id,
        'correlation_id' => (string) Str::ulid(),
        'recipient_user_id' => $this->recipient->id,
        'channel' => 'in_app',
        'payload' => ['schema_version' => 1],
        'status' => 'sent',
        'available_at' => now(),
    ]);

    NotificationOutbox::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'tenant_B',
        'notification_event_id' => $eventB->id,
        'correlation_id' => (string) Str::ulid(),
        'recipient_user_id' => $this->recipient->id,
        'channel' => 'in_app',
        'payload' => ['schema_version' => 1],
        'status' => 'sent',
        'available_at' => now(),
    ]);

    $tenantAItems = NotificationOutbox::forTenant('tenant_A')->get();
    expect($tenantAItems->count())->toBe(1);
    expect($tenantAItems->first()->tenant_id)->toBe('tenant_A');

    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/admin/notifications/outbox?tenant_id=tenant_B');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['tenant_id'])->toBe('tenant_B');
});

test('9. Retention Pruning Command: prunes sent items older than threshold and preserves dead/failed logs', function () {
    $event1 = NotificationEvent::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'default',
        'event_type' => 'lesson',
        'event_id' => (string) Str::ulid(),
        'notification_type' => 'reminder_24h',
        'recipient_user_id' => $this->recipient->id,
        'status' => 'generated',
    ]);

    $event2 = NotificationEvent::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'default',
        'event_type' => 'lesson',
        'event_id' => (string) Str::ulid(),
        'notification_type' => 'reminder_24h',
        'recipient_user_id' => $this->recipient->id,
        'status' => 'generated',
    ]);

    $event3 = NotificationEvent::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'default',
        'event_type' => 'lesson',
        'event_id' => (string) Str::ulid(),
        'notification_type' => 'reminder_24h',
        'recipient_user_id' => $this->recipient->id,
        'status' => 'generated',
    ]);

    // 1. Sent record 200 days old (should be deleted)
    $oldSent = NotificationOutbox::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'default',
        'notification_event_id' => $event1->id,
        'correlation_id' => (string) Str::ulid(),
        'recipient_user_id' => $this->recipient->id,
        'channel' => 'in_app',
        'payload' => ['schema_version' => 1],
        'status' => 'sent',
        'available_at' => now()->subDays(200),
    ]);
    // Force created_at to 200 days ago
    DB::table('notification_outbox')->where('id', $oldSent->id)->update(['created_at' => now()->subDays(200)]);

    // 2. Sent record 10 days old (should be kept)
    $recentSent = NotificationOutbox::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'default',
        'notification_event_id' => $event2->id,
        'correlation_id' => (string) Str::ulid(),
        'recipient_user_id' => $this->recipient->id,
        'channel' => 'in_app',
        'payload' => ['schema_version' => 1],
        'status' => 'sent',
        'available_at' => now()->subDays(10),
    ]);
    DB::table('notification_outbox')->where('id', $recentSent->id)->update(['created_at' => now()->subDays(10)]);

    // 3. Dead record 200 days old (MUST BE PRESERVED FOR AUDIT)
    $oldDead = NotificationOutbox::create([
        'id' => (string) Str::ulid(),
        'tenant_id' => 'default',
        'notification_event_id' => $event3->id,
        'correlation_id' => (string) Str::ulid(),
        'recipient_user_id' => $this->recipient->id,
        'channel' => 'whatsapp',
        'payload' => ['schema_version' => 1],
        'status' => 'dead',
        'available_at' => now()->subDays(200),
    ]);
    DB::table('notification_outbox')->where('id', $oldDead->id)->update(['created_at' => now()->subDays(200)]);

    Artisan::call('notifications:cleanup', ['--days' => 180, '--chunk' => 500]);

    expect(NotificationOutbox::where('id', $oldSent->id)->exists())->toBeFalse();
    expect(NotificationOutbox::where('id', $recentSent->id)->exists())->toBeTrue();
    expect(NotificationOutbox::where('id', $oldDead->id)->exists())->toBeTrue();
});
