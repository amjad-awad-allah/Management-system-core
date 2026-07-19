<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatChannel;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatMessage;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatParticipant;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Survey;
use App\Modules\Nachhilfe\Infrastructure\Models\SurveyResponse;
use App\Modules\Nachhilfe\Application\Actions\Chat\SendMessageAction;
use App\Modules\Nachhilfe\Application\Actions\Chat\SubmitSurveyAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentChatController extends Controller
{
    public function __construct(
        private readonly SendMessageAction $sendMessageAction,
        private readonly SubmitSurveyAction $submitSurveyAction,
    ) {}

    /** Resolve student or abort 403. */
    private function resolveStudent(Request $request): Student
    {
        $student = Student::where('user_id', $request->user()->id)->first();
        abort_unless($student, 403, 'Student profile not found.');
        return $student;
    }

    // ─────────────────────────────────────────────────────────────
    // Channels — students can only see their own channels
    // ─────────────────────────────────────────────────────────────

    public function indexChannels(Request $request): JsonResponse
    {
        $this->resolveStudent($request);

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

    // ─────────────────────────────────────────────────────────────
    // Messages
    // ─────────────────────────────────────────────────────────────

    public function indexMessages(Request $request, string $id): JsonResponse
    {
        $this->resolveStudent($request);

        // Student can only access channels they are part of
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
        $this->resolveStudent($request);

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
        $this->resolveStudent($request);
        $message = ChatMessage::findOrFail($id);

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
    // Surveys
    // ─────────────────────────────────────────────────────────────

    public function indexSurveys(Request $request): JsonResponse
    {
        $this->resolveStudent($request);

        $surveys = Survey::whereHas('channel.participants', fn($q) =>
            $q->where('user_id', $request->user()->id)
        )->where('status', 'active')
         ->with('questions')
         ->latest()
         ->get();

        // Add whether student already responded
        $userId = $request->user()->id;
        $surveys->transform(function ($survey) use ($userId) {
            $survey->already_responded = SurveyResponse::where('survey_id', $survey->id)
                ->where('user_id', $userId)->exists();
            return $survey;
        });

        return response()->json($surveys);
    }

    public function submitSurveyResponse(Request $request, string $id): JsonResponse
    {
        $this->resolveStudent($request);
        $survey = Survey::findOrFail($id);

        // Must be a participant
        abort_unless(
            ChatParticipant::where('channel_id', $survey->channel_id)
                ->where('user_id', $request->user()->id)->exists(),
            403
        );

        // One response per student
        if (SurveyResponse::where('survey_id', $survey->id)->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'You have already submitted a response.'], 409);
        }

        abort_unless($survey->isActive(), 422, 'This survey is closed or expired.');

        $validated = $request->validate([
            'answers' => 'required|array',
        ]);

        $response = $this->submitSurveyAction->execute(
            surveyId: $survey->id,
            userId: $request->user()->id,
            answers: $validated['answers']
        );

        return response()->json($response, 201);
    }

    public function surveyResponses(Request $request, string $id): JsonResponse
    {
        $this->resolveStudent($request);
        $survey = Survey::findOrFail($id);

        // Must be a participant
        abort_unless(
            ChatParticipant::where('channel_id', $survey->channel_id)
                ->where('user_id', $request->user()->id)->exists(),
            403
        );

        return response()->json($survey->responses()->with('user')->get());
    }
}
