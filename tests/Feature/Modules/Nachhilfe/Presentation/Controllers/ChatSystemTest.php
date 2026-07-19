<?php

namespace Tests\Feature\Modules\Nachhilfe\Presentation\Controllers;

use App\Core\Models\User;
use App\Core\Models\Role;
use App\Core\Models\Notification;
use App\Core\Notification\Services\NotificationService;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatChannel;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatMessage;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatParticipant;
use App\Modules\Nachhilfe\Infrastructure\Models\Survey;
use App\Modules\Nachhilfe\Infrastructure\Models\SurveyResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Broadcast;
use App\Core\Broadcasting\ChatMessageSent;
use Tests\TestCase;

class ChatSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacherUser;
    private User $studentUser;
    private Teacher $teacher;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Roles
        $adminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $teacherRole = Role::firstOrCreate(['name' => 'Teacher']);
        $studentRole = Role::firstOrCreate(['name' => 'Student']);

        // Setup Users
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->teacherUser = User::factory()->create();
        $this->teacherUser->assignRole($teacherRole);
        $this->teacher = Teacher::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'user_id' => $this->teacherUser->id,
            'name' => 'Herr Müller',
            'email' => $this->teacherUser->email,
        ]);

        $this->studentUser = User::factory()->create();
        $this->studentUser->assignRole($studentRole);
        $this->student = Student::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'user_id' => $this->studentUser->id,
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
        ]);
    }

    /** @test */
    public function admin_can_create_channels_and_send_messages()
    {
        $this->actingAs($this->admin);

        // 1. Create channel
        $response = $this->postJson('/api/v1/nachhilfe/chat/channels', [
            'type' => 'group',
            'name' => 'Support Group',
            'participant_ids' => [$this->teacherUser->id, $this->studentUser->id],
        ]);

        $response->assertStatus(201);
        $channelId = $response->json('id');
        $this->assertDatabaseHas('chat_channels', ['id' => $channelId, 'name' => 'Support Group']);

        // 2. Send message
        $responseMsg = $this->postJson("/api/v1/nachhilfe/chat/channels/{$channelId}/messages", [
            'body' => 'Welcome to the support channel!',
        ]);

        $responseMsg->assertStatus(201);
        $this->assertDatabaseHas('chat_messages', [
            'channel_id' => $channelId,
            'body' => 'Welcome to the support channel!',
        ]);
    }

    /** @test */
    public function direct_channels_are_deduplicated_and_reused()
    {
        $this->actingAs($this->admin);

        // Create first direct channel
        $res1 = $this->postJson('/api/v1/nachhilfe/chat/channels', [
            'type' => 'direct',
            'participant_ids' => [$this->studentUser->id],
        ]);
        $res1->assertStatus(201);
        $id1 = $res1->json('id');

        // Try creating second direct channel between same users
        $res2 = $this->postJson('/api/v1/nachhilfe/chat/channels', [
            'type' => 'direct',
            'participant_ids' => [$this->studentUser->id],
        ]);
        $res2->assertStatus(201);
        $id2 = $res2->json('id');

        // IDs must match
        $this->assertEquals($id1, $id2);
    }

    /** @test */
    public function teacher_can_only_chat_with_their_own_students()
    {
        // 1. Unlinked student (no lessons together)
        $this->actingAs($this->teacherUser);
        $response = $this->postJson('/api/v1/mobile/teacher/chat/channels', [
            'type' => 'direct',
            'participant_ids' => [$this->studentUser->id],
        ]);
        $response->assertStatus(403); // Forbidden

        // 2. Link student to teacher via lesson
        $roomId = (string) \Illuminate\Support\Str::ulid();
        \Illuminate\Support\Facades\DB::table('rooms')->insert([
            'id' => $roomId,
            'name' => 'Room A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subjectId = (string) \Illuminate\Support\Str::ulid();
        \Illuminate\Support\Facades\DB::table('subjects')->insert([
            'id' => $subjectId,
            'name' => 'Math',
            'code' => 'MATH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lesson = Lesson::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'teacher_id' => $this->teacher->id,
            'room_id' => $roomId,
            'subject_id' => $subjectId,
            'date' => now()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);
        LessonStudent::create([
            'lesson_id' => $lesson->id,
            'student_id' => $this->student->id,
        ]);

        // 3. Retry creating chat — should succeed now
        $responseOk = $this->postJson('/api/v1/mobile/teacher/chat/channels', [
            'type' => 'direct',
            'participant_ids' => [$this->studentUser->id],
        ]);
        $responseOk->assertStatus(201);
    }

    /** @test */
    public function student_can_submit_survey_response()
    {
        $this->actingAs($this->admin);

        // 1. Create a survey channel
        $chanRes = $this->postJson('/api/v1/nachhilfe/chat/channels', [
            'type' => 'survey',
            'name' => 'Satisfaction Survey',
            'participant_ids' => [$this->studentUser->id],
        ]);
        $channelId = $chanRes->json('id');

        // 2. Create survey
        $surveyRes = $this->postJson('/api/v1/nachhilfe/chat/surveys', [
            'channel_id' => $channelId,
            'title' => 'Weekly Tutoring Feedback',
            'questions' => [
                [
                    'question' => 'How satisfied are you?',
                    'type' => 'rating',
                    'order' => 0
                ]
            ]
        ]);
        $surveyRes->assertStatus(201);
        $surveyId = $surveyRes->json('id');

        // 3. Submit response as student
        $this->actingAs($this->studentUser);
        $response = $this->postJson("/api/v1/mobile/student/chat/surveys/{$surveyId}/respond", [
            'answers' => [
                $surveyRes->json('questions.0.id') => 5
            ]
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('survey_responses', [
            'survey_id' => $surveyId,
            'user_id' => $this->studentUser->id,
        ]);
    }
}
