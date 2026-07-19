<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatChannel;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatMessage;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatParticipant;
use App\Modules\Nachhilfe\Infrastructure\Models\Survey;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\LessonStudent;
use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use App\Modules\Nachhilfe\Application\Actions\Chat\CreateChannelAction;
use App\Modules\Nachhilfe\Application\Actions\Chat\SendMessageAction;
use App\Modules\Nachhilfe\Application\Actions\Chat\CreateSurveyAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeacherChatController extends Controller
{
    public function __construct(
        private readonly CreateChannelAction $createChannelAction,
        private readonly SendMessageAction   $sendMessageAction,
        private readonly CreateSurveyAction  $createSurveyAction,
    ) {}

    /** Resolve teacher or abort 403. */
    private function resolveTeacher(Request $request): Teacher
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->first();
        abort_unless($teacher, 403, 'Teacher profile not found.');
        return $teacher;
    }

    /** Get student IDs that belong to this teacher. */
    private function getMyStudentUserIds(Teacher $teacher): array
    {
        return LessonStudent::whereHas('lesson', fn($q) =>
            $q->where('teacher_id', $teacher->id)
        )->with('student.user')
         ->get()
         ->pluck('student.user_id')
         ->filter()
         ->unique()
         ->values()
         ->toArray();
    }

    // ─────────────────────────────────────────────────────────────
    // Channels
    // ─────────────────────────────────────────────────────────────

    public function indexChannels(Request $request): JsonResponse
    {
        $teacher = $this->resolveTeacher($request);

        $channels = ChatChannel::whereHas('participants', fn($q) =>
            $q->where('user_id', $request->user()->id)
              ->whereIn('role', ['member', 'admin'])
        )->with(['participants.user', 'latestMessage'])->latest()->get();

        $userId = $request->user()->id;
        $channels->transform(function ($channel) use ($userId) {
            $participant = $channel->participants->firstWhere('user_id', $userId);
            $lastReadAt  = $participant?->last_read_at;
            $unread = ChatMessage::where('channel_id', $channel->id)
                ->where('sender_id', '!=', $userId)
                ->where('is_deleted', false)
                ->when($lastReadAt, fn($q) => $q->where('created_at', '>', $lastReadAt))
                ->count();
            $channel->unread_count = $unread;
            return $channel;
        });

        return response()->json($channels);
    }

    public function storeChannel(Request $request): JsonResponse
    {
        $teacher = $this->resolveTeacher($request);
        $myStudentIds = $this->getMyStudentUserIds($teacher);

        $validated = $request->validate([
            'type'            => 'required|in:direct,group',
            'name'            => 'nullable|string|max:255',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'required|string',
        ]);

        // Ensure teacher can only add their own students
        foreach ($validated['participant_ids'] as $uid) {
            if (!in_array($uid, $myStudentIds)) {
                return response()->json(['message' => 'Unauthorized: You can only chat with your own students.'], 403);
            }
        }

        $channel = $this->createChannelAction->execute(
            createdBy:      $request->user()->id,
            type:           $validated['type'],
            name:           $validated['name'] ?? null,
            participantIds: $validated['participant_ids'],
        );

        return response()->json($channel, 201);
    }

    // ─────────────────────────────────────────────────────────────
    // Messages
    // ─────────────────────────────────────────────────────────────

    public function indexMessages(Request $request, string $id): JsonResponse
    {
        $this->resolveTeacher($request);

        $channel = ChatChannel::whereHas('participants', fn($q) =>
            $q->where('user_id', $request->user()->id)
        )->findOrFail($id);

        $messages = ChatMessage::where('channel_id', $channel->id)
            ->where('is_deleted', false)
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($messages);
    }

    public function storeMessage(Request $request, string $id): JsonResponse
    {
        $this->resolveTeacher($request);

        $channel = ChatChannel::whereHas('participants', fn($q) =>
            $q->where('user_id', $request->user()->id)
        )->findOrFail($id);

        $request->validate([
            'body' => 'nullable|string|max:5000',
            'file' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,txt',
        ]);

        if (empty($request->body) && !$request->hasFile('file')) {
            return response()->json(['message' => 'Body or file required.'], 422);
        }

        $message = $this->sendMessageAction->execute(
            channel:  $channel,
            senderId: $request->user()->id,
            body:     $request->body,
            file:     $request->file('file'),
        );

        return response()->json($message, 201);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $participant = ChatParticipant::firstOrCreate(
            ['channel_id' => $id, 'user_id' => $request->user()->id],
            ['role' => 'member', 'joined_at' => now()]
        );

        $participant->update(['last_read_at' => now()]);

        return response()->json(['message' => 'Marked as read.']);
    }

    public function downloadAttachment(Request $request, string $id): mixed
    {
        $this->resolveTeacher($request);
        $message = ChatMessage::findOrFail($id);

        // Teacher must be participant in the channel
        abort_unless(
            ChatParticipant::where('channel_id', $message->channel_id)
                ->where('user_id', $request->user()->id)->exists(),
            403
        );

        if (!$message->hasAttachment()) {
            return response()->json(['message' => 'No attachment.'], 404);
        }

        return Storage::download($message->metadata['file_path'], $message->metadata['file_name'] ?? 'file');
    }

    // ─────────────────────────────────────────────────────────────
    // Surveys (stubs — full implementation in Phase 5)
    // ─────────────────────────────────────────────────────────────

    public function indexSurveys(Request $request): JsonResponse
    {
        $teacher = $this->resolveTeacher($request);

        $surveys = Survey::whereHas('channel.participants', fn($q) =>
            $q->where('user_id', $request->user()->id)
        )->with('questions')->latest()->get();

        return response()->json($surveys);
    }

    public function storeSurvey(Request $request): JsonResponse
    {
        $teacher = $this->resolveTeacher($request);

        $validated = $request->validate([
            'channel_id' => 'required|string|exists:chat_channels,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.type' => 'required|in:text,single_choice,multiple_choice,rating',
            'questions.*.options' => 'nullable|array',
            'questions.*.order' => 'nullable|integer',
            'expires_at' => 'nullable|date_format:Y-m-d H:i:s|after:now',
        ]);

        // Verify teacher belongs to the channel
        abort_unless(
            ChatParticipant::where('channel_id', $validated['channel_id'])
                ->where('user_id', $request->user()->id)->exists(),
            403,
            'Unauthorized'
        );

        $survey = $this->createSurveyAction->execute(
            channelId: $validated['channel_id'],
            createdBy: $request->user()->id,
            title: $validated['title'],
            description: $validated['description'] ?? null,
            questions: $validated['questions'],
            expiresAt: $validated['expires_at'] ?? null
        );

        return response()->json($survey, 201);
    }

    public function surveyResponses(Request $request, string $id): JsonResponse
    {
        $teacher = $this->resolveTeacher($request);
        $survey = Survey::findOrFail($id);

        // Teacher must own the survey channel
        abort_unless(
            ChatParticipant::where('channel_id', $survey->channel_id)
                ->where('user_id', $request->user()->id)->exists(),
            403
        );

        return response()->json($survey->responses()->with('user')->get());
    }
}
