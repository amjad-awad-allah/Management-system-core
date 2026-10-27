<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Core\Models\User;
use App\Core\Models\Role;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatChannel;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatMessage;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatParticipant;
use App\Core\Notification\Models\NotificationOutbox;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Str;

class Phase0SecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacherUser;
    private User $unauthorizedTeacherUser;
    private User $studentUser;
    private User $unauthorizedStudentUser;
    private Teacher $teacher;
    private Teacher $unauthorizedTeacher;
    private Student $student;
    private Student $unauthorizedStudent;
    private ChatChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        $adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $teacherRole = Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

        // 1. Admin
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        // 2. Legitimate Teacher in channel
        $this->teacherUser = User::factory()->create();
        $this->teacherUser->assignRole($teacherRole);
        $this->teacher = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->teacherUser->id,
            'name' => 'Teacher One',
            'email' => $this->teacherUser->email,
        ]);

        // 3. Unauthorized Teacher outside channel
        $this->unauthorizedTeacherUser = User::factory()->create();
        $this->unauthorizedTeacherUser->assignRole($teacherRole);
        $this->unauthorizedTeacher = Teacher::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->unauthorizedTeacherUser->id,
            'name' => 'Teacher Two',
            'email' => $this->unauthorizedTeacherUser->email,
        ]);

        // 4. Legitimate Student in channel
        $this->studentUser = User::factory()->create();
        $this->studentUser->assignRole($studentRole);
        $this->student = Student::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->studentUser->id,
            'first_name' => 'Student',
            'last_name' => 'One',
        ]);

        // 5. Unauthorized Student outside channel
        $this->unauthorizedStudentUser = User::factory()->create();
        $this->unauthorizedStudentUser->assignRole($studentRole);
        $this->unauthorizedStudent = Student::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->unauthorizedStudentUser->id,
            'first_name' => 'Student',
            'last_name' => 'Two',
        ]);

        // Create a private channel with teacherUser and studentUser only
        $this->channel = ChatChannel::create([
            'id' => (string) Str::ulid(),
            'type' => 'direct',
            'name' => 'Private Channel',
            'created_by' => $this->admin->id,
        ]);

        ChatParticipant::create([
            'id' => (string) Str::ulid(),
            'channel_id' => $this->channel->id,
            'user_id' => $this->teacherUser->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        ChatParticipant::create([
            'id' => (string) Str::ulid(),
            'channel_id' => $this->channel->id,
            'user_id' => $this->studentUser->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // 1. Chat markRead Self-Enrollment Backdoor Prevention
    // ─────────────────────────────────────────────────────────────

    public function test_unauthorized_student_cannot_join_channel_via_mark_read(): void
    {
        $initialCount = ChatParticipant::where('channel_id', $this->channel->id)->count();
        $this->assertEquals(2, $initialCount);

        // Unauthorized student attempts markRead on channel they are not part of
        $response = $this->actingAs($this->unauthorizedStudentUser, 'sanctum')
            ->postJson("/api/v1/mobile/student/chat/channels/{$this->channel->id}/read");

        $response->assertStatus(403);

        // Verify participant count is unchanged and unauthorized student was NOT enrolled
        $this->assertEquals($initialCount, ChatParticipant::where('channel_id', $this->channel->id)->count());
        $this->assertFalse(
            ChatParticipant::where('channel_id', $this->channel->id)
                ->where('user_id', $this->unauthorizedStudentUser->id)
                ->exists()
        );
    }

    public function test_unauthorized_teacher_cannot_join_channel_via_mark_read(): void
    {
        $initialCount = ChatParticipant::where('channel_id', $this->channel->id)->count();

        // Unauthorized teacher attempts markRead on foreign channel
        $response = $this->actingAs($this->unauthorizedTeacherUser, 'sanctum')
            ->postJson("/api/v1/mobile/teacher/chat/channels/{$this->channel->id}/read");

        $response->assertStatus(403);

        // Verify participant count is unchanged
        $this->assertEquals($initialCount, ChatParticipant::where('channel_id', $this->channel->id)->count());
        $this->assertFalse(
            ChatParticipant::where('channel_id', $this->channel->id)
                ->where('user_id', $this->unauthorizedTeacherUser->id)
                ->exists()
        );
    }

    public function test_authorized_participants_can_mark_read_successfully(): void
    {
        // 1. Legitimate student marks read
        $resStudent = $this->actingAs($this->studentUser, 'sanctum')
            ->postJson("/api/v1/mobile/student/chat/channels/{$this->channel->id}/read");
        $resStudent->assertStatus(200);
        $resStudent->assertJson(['message' => 'Marked as read.']);

        $studentParticipant = ChatParticipant::where('channel_id', $this->channel->id)
            ->where('user_id', $this->studentUser->id)
            ->first();
        $this->assertNotNull($studentParticipant->last_read_at);

        // 2. Legitimate teacher marks read
        $resTeacher = $this->actingAs($this->teacherUser, 'sanctum')
            ->postJson("/api/v1/mobile/teacher/chat/channels/{$this->channel->id}/read");
        $resTeacher->assertStatus(200);
        $resTeacher->assertJson(['message' => 'Marked as read.']);

        $teacherParticipant = ChatParticipant::where('channel_id', $this->channel->id)
            ->where('user_id', $this->teacherUser->id)
            ->first();
        $this->assertNotNull($teacherParticipant->last_read_at);
    }

    // ─────────────────────────────────────────────────────────────
    // 2. Administrative Chat Authorization
    // ─────────────────────────────────────────────────────────────

    public function test_unauthorized_users_cannot_access_administrative_chat_endpoints(): void
    {
        // Student denied on admin channels list
        $this->actingAs($this->studentUser, 'sanctum')
            ->getJson('/api/v1/nachhilfe/chat/channels')
            ->assertStatus(403);

        // Teacher denied on admin channels list
        $this->actingAs($this->teacherUser, 'sanctum')
            ->getJson('/api/v1/nachhilfe/chat/channels')
            ->assertStatus(403);

        // Student denied on deleting channel
        $this->actingAs($this->studentUser, 'sanctum')
            ->deleteJson("/api/v1/nachhilfe/chat/channels/{$this->channel->id}")
            ->assertStatus(403);

        // Student denied on admin surveys list
        $this->actingAs($this->studentUser, 'sanctum')
            ->getJson('/api/v1/nachhilfe/chat/surveys')
            ->assertStatus(403);
    }

    public function test_admin_can_access_administrative_chat_endpoints(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/nachhilfe/chat/channels');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'current_page']);
    }

    public function test_attachment_download_authorization(): void
    {
        // Create a message with attachment in the channel
        $message = ChatMessage::create([
            'id' => (string) Str::ulid(),
            'channel_id' => $this->channel->id,
            'sender_id' => $this->teacherUser->id,
            'message' => 'Test file',
            'attachment_url' => 'chat-attachments/test.pdf',
            'attachment_name' => 'test.pdf',
            'attachment_type' => 'application/pdf',
        ]);

        // Negative: Unauthorized student cannot download attachment from private channel
        $this->actingAs($this->unauthorizedStudentUser, 'sanctum')
            ->getJson("/api/v1/nachhilfe/chat/messages/{$message->id}/attachment")
            ->assertStatus(403);

        // Negative: Unauthorized teacher cannot download attachment from private channel
        $this->actingAs($this->unauthorizedTeacherUser, 'sanctum')
            ->getJson("/api/v1/nachhilfe/chat/messages/{$message->id}/attachment")
            ->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────
    // 3. Notification Outbox DLQ Authorization
    // ─────────────────────────────────────────────────────────────

    public function test_unauthorized_users_cannot_access_notification_outbox_or_retry(): void
    {
        $event = \App\Core\Notification\Models\NotificationEvent::create([
            'id' => (string) Str::ulid(),
            'event_type' => 'test',
            'event_id' => (string) Str::ulid(),
            'notification_type' => 'test',
            'recipient_user_id' => $this->admin->id,
            'status' => 'pending',
        ]);

        $outbox = NotificationOutbox::create([
            'id' => (string) Str::ulid(),
            'notification_event_id' => $event->id,
            'recipient_user_id' => $this->admin->id,
            'channel' => 'in_app',
            'payload' => ['body' => 'Secret message'],
            'status' => 'dead',
            'attempts' => 3,
        ]);

        // Student denied
        $this->actingAs($this->studentUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/outbox')
            ->assertStatus(403);

        $this->actingAs($this->studentUser, 'sanctum')
            ->postJson("/api/v1/admin/notifications/outbox/{$outbox->id}/retry")
            ->assertStatus(403);

        // Teacher denied
        $this->actingAs($this->teacherUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/outbox')
            ->assertStatus(403);

        $this->actingAs($this->teacherUser, 'sanctum')
            ->postJson("/api/v1/admin/notifications/outbox/{$outbox->id}/retry")
            ->assertStatus(403);
    }

    public function test_admin_can_access_outbox_and_retry(): void
    {
        $event = \App\Core\Notification\Models\NotificationEvent::create([
            'id' => (string) Str::ulid(),
            'event_type' => 'test',
            'event_id' => (string) Str::ulid(),
            'notification_type' => 'test',
            'recipient_user_id' => $this->admin->id,
            'status' => 'pending',
        ]);

        $outbox = NotificationOutbox::create([
            'id' => (string) Str::ulid(),
            'notification_event_id' => $event->id,
            'recipient_user_id' => $this->admin->id,
            'channel' => 'in_app',
            'payload' => ['body' => 'Test message'],
            'status' => 'dead',
            'attempts' => 3,
        ]);

        $resList = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/notifications/outbox');
        $resList->assertStatus(200);

        $resRetry = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/notifications/outbox/{$outbox->id}/retry");
        $resRetry->assertStatus(200);
        $this->assertEquals('pending', $outbox->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────
    // 4. WebSocket ULID Broadcast Channel Authorization
    // ─────────────────────────────────────────────────────────────

    public function test_websocket_channel_authorization_with_ulids_does_not_truncate_to_int(): void
    {
        Broadcast::extend('test_driver', function ($app, $config) {
            return new class extends \Illuminate\Broadcasting\Broadcasters\Broadcaster {
                public function auth($request) {
                    $channelName = str_replace('private-', '', $request->channel_name);
                    return $this->verifyUserCanAccessChannel($request, $channelName);
                }
                public function validAuthenticationResponse($request, $result) {
                    return response()->json(['auth' => true]);
                }
                public function broadcast(array $channels, $event, array $payload = []) {}
            };
        });

        config([
            'broadcasting.default' => 'test_conn',
            'broadcasting.connections.test_conn' => ['driver' => 'test_driver'],
        ]);
        Broadcast::purge('test_conn');
        require base_path('routes/channels.php');

        // Construct two users whose ULIDs both start with '01'
        $userA = User::factory()->create(['id' => '01JBYQW8C6M4Y1J6Q5Z4M1K90A']);
        $userB = User::factory()->create(['id' => '01JBYQW8C6M4Y1J6Q5Z4M1K90B']);

        // Positive test: User A can authenticate to their own channel
        $responseA = $this->actingAs($userA, 'sanctum')
            ->postJson('/api/v1/broadcasting/auth', [
                'channel_name' => "private-App.Models.User.{$userA->id}",
            ]);
        $responseA->assertStatus(200);

        // Negative test: User A cannot authenticate to User B's channel despite sharing '01' prefix
        $responseB = $this->actingAs($userA, 'sanctum')
            ->postJson('/api/v1/broadcasting/auth', [
                'channel_name' => "private-App.Models.User.{$userB->id}",
            ]);
        $responseB->assertStatus(403);
    }
}
