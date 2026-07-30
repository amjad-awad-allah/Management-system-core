<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatChannel;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatMessage;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatParticipant;
use App\Modules\Nachhilfe\Application\Actions\Chat\CreateChannelAction;
use App\Modules\Nachhilfe\Application\Actions\Chat\SendMessageAction;
use App\Modules\Nachhilfe\Application\Actions\Chat\CreateSurveyAction;
use App\Modules\Nachhilfe\Infrastructure\Models\Survey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function __construct(
        private readonly CreateChannelAction $createChannelAction,
        private readonly SendMessageAction   $sendMessageAction,
        private readonly CreateSurveyAction  $createSurveyAction,
    ) {}

    // ──────────────────────────────────────────────────────────
    // Channels
    // ──────────────────────────────────────────────────────────

    /**
     * GET /chat/channels
     * scope=mine → only channels I participate in
     * scope=all  → all channels (admin view)
     */
    public function indexChannels(Request $request): JsonResponse
    {
        $scope = $request->query('scope', 'all');

        $query = ChatChannel::withTrashed(false)
            ->with(['participants.user', 'latestMessage'])
            ->latest();

        if ($scope === 'mine') {
            $query->whereHas('participants', fn($q) =>
                $q->where('user_id', $request->user()->id)
                  ->whereIn('role', ['member', 'admin'])
            );
        }

        $channels = $query->paginate(20);

        // For each channel, count unread messages per user participant record
        $userId = $request->user()->id;
        $channels->getCollection()->transform(function (ChatChannel $channel) use ($userId) {
            $participant = $channel->participants->firstWhere('user_id', $userId);
            $lastReadAt = $participant?->last_read_at;

            $unreadQuery = ChatMessage::where('channel_id', $channel->id)
                ->where('sender_id', '!=', $userId)
                ->where('is_deleted', false);

            if ($lastReadAt) {
                $unreadQuery->where('created_at', '>', $lastReadAt);
            }

            $channel->unread_count = $unreadQuery->count();
            $channel->my_role      = $participant?->role ?? 'observer';
            return $channel;
        });

        return response()->json($channels);
    }

    /**
     * GET /chat/channels/archived
     */
    public function archivedChannels(Request $request): JsonResponse
    {
        $channels = ChatChannel::onlyTrashed()
            ->with(['participants.user'])
            ->latest()
            ->paginate(20);

        return response()->json($channels);
    }

    /**
     * POST /chat/channels
     */
    public function storeChannel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'            => 'required|in:direct,group,survey',
            'name'            => 'nullable|string|max:255',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'required|string|exists:users,id',
            'observer_ids'    => 'nullable|array',
            'observer_ids.*'  => 'nullable|string|exists:users,id',
            'metadata'        => 'nullable|array',
        ]);

        $channel = $this->createChannelAction->execute(
            createdBy:      $request->user()->id,
            type:           $validated['type'],
            name:           $validated['name'] ?? null,
            participantIds: $validated['participant_ids'],
            observerIds:    $validated['observer_ids'] ?? [],
            metadata:       $validated['metadata'] ?? null,
        );

        return response()->json($channel, 201);
    }

    /**
     * GET /chat/channels/{id}
     */
    public function showChannel(string $id): JsonResponse
    {
        $channel = ChatChannel::with(['participants.user', 'surveys'])->findOrFail($id);
        return response()->json($channel);
    }

    /**
     * DELETE /chat/channels/{id}  → soft delete (archive)
     */
    public function destroyChannel(string $id): JsonResponse
    {
        $channel = ChatChannel::findOrFail($id);
        $channel->delete(); // SoftDeletes

        return response()->json(['message' => 'Channel archived successfully.']);
    }

    // ──────────────────────────────────────────────────────────
    // Messages
    // ──────────────────────────────────────────────────────────

    /**
     * GET /chat/channels/{id}/messages
     */
    public function indexMessages(Request $request, string $id): JsonResponse
    {
        $channel = ChatChannel::findOrFail($id);

        $messages = ChatMessage::where('channel_id', $channel->id)
            ->where('is_deleted', false)
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($messages);
    }

    /**
     * POST /chat/channels/{id}/messages
     */
    public function storeMessage(Request $request, string $id): JsonResponse
    {
        $channel = ChatChannel::findOrFail($id);

        $request->validate([
            'body' => 'nullable|string|max:5000',
            'file' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,txt',
        ]);

        if (empty($request->body) && !$request->hasFile('file')) {
            return response()->json(['message' => 'Message body or file is required.'], 422);
        }

        $message = $this->sendMessageAction->execute(
            channel:  $channel,
            senderId: $request->user()->id,
            body:     $request->body,
            file:     $request->file('file'),
        );

        return response()->json($message, 201);
    }

    /**
     * DELETE /chat/messages/{id}
     */
    public function destroyMessage(Request $request, string $id): JsonResponse
    {
        $message = ChatMessage::findOrFail($id);
        $message->update(['is_deleted' => true]);

        return response()->json(['message' => 'Message deleted.']);
    }

    /**
     * POST /chat/channels/{id}/read  — mark last_read_at for current user
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $participant = ChatParticipant::firstOrCreate(
            ['channel_id' => $id, 'user_id' => $request->user()->id],
            ['role' => 'observer', 'joined_at' => now()]
        );

        $participant->update(['last_read_at' => now()]);

        return response()->json(['message' => 'Marked as read.']);
    }

    // ──────────────────────────────────────────────────────────
    // Attachment Download
    // ──────────────────────────────────────────────────────────

    /**
     * GET /chat/messages/{id}/attachment
     */
    public function downloadAttachment(string $id): mixed
    {
        $message = ChatMessage::findOrFail($id);

        if (!$message->hasAttachment()) {
            return response()->json(['message' => 'No attachment.'], 404);
        }

        $filePath = $message->metadata['file_path'];
        $fileName = $message->metadata['file_name'] ?? basename($filePath);

        if (Storage::disk('local')->exists($filePath)) {
            return Storage::disk('local')->download($filePath, $fileName);
        }

        if (Storage::exists($filePath)) {
            return Storage::download($filePath, $fileName);
        }

        return response()->json(['message' => 'File not found on storage.'], 404);
    }

    // ──────────────────────────────────────────────────────────
    // Surveys (Admin)
    // ──────────────────────────────────────────────────────────

    /**
     * GET /chat/surveys
     */
    public function indexSurveys(Request $request): JsonResponse
    {
        $surveys = Survey::with('questions')->latest()->paginate(20);
        return response()->json($surveys);
    }

    /**
     * POST /chat/surveys
     */
    public function storeSurvey(Request $request): JsonResponse
    {
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

    /**
     * GET /chat/surveys/{id}
     */
    public function showSurvey(string $id): JsonResponse
    {
        $survey = Survey::with(['questions', 'responses.user'])->findOrFail($id);
        return response()->json($survey);
    }

    /**
     * GET /chat/surveys/{id}/responses
     */
    public function surveyResponses(string $id): JsonResponse
    {
        $survey = Survey::findOrFail($id);
        $responses = $survey->responses()->with('user')->get();
        return response()->json($responses);
    }
}
