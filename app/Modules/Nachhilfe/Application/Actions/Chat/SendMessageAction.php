<?php

namespace App\Modules\Nachhilfe\Application\Actions\Chat;

use App\Core\Models\User;
use App\Core\Models\Notification;
use App\Core\Notification\Data\NotificationMessage;
use App\Core\Notification\Data\NotificationOptions;
use App\Core\Notification\Services\NotificationService;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatChannel;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatMessage;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatParticipant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SendMessageAction
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Send a message to a chat channel.
     *
     * @param  ChatChannel    $channel
     * @param  string         $senderId  User ID of the sender
     * @param  string|null    $body      Message text (nullable if attachment only)
     * @param  UploadedFile|null $file   Optional file attachment
     * @return ChatMessage
     */
    public function execute(
        ChatChannel $channel,
        string $senderId,
        ?string $body,
        ?UploadedFile $file = null
    ): ChatMessage {
        $type = 'text';
        $metadata = null;

        // Handle file/image attachment
        if ($file !== null) {
            $mime = $file->getMimeType();
            $type = str_starts_with($mime, 'image/') ? 'image' : 'file';
            $path = Storage::disk('local')->putFile(
                'chat_attachments/' . $channel->id,
                $file
            );
            $metadata = [
                'file_path'  => $path,
                'file_name'  => $file->getClientOriginalName(),
                'file_size'  => $file->getSize(),
                'mime_type'  => $mime,
            ];
        }

        // Save the message
        $message = ChatMessage::create([
            'channel_id' => $channel->id,
            'sender_id'  => $senderId,
            'body'       => $body,
            'type'       => $type,
            'metadata'   => $metadata,
        ]);

        // Broadcast via Reverb for Real-Time UI update
        broadcast(new \App\Core\Broadcasting\ChatMessageSent($message->load('sender')))->toOthers();

        // Send in-app notifications to non-sender, non-observer participants
        $participants = ChatParticipant::where('channel_id', $channel->id)
            ->where('user_id', '!=', $senderId)
            ->get();

        foreach ($participants as $participant) {
            // Observers see a silent badge — no notification
            if ($participant->isObserver()) {
                continue;
            }

            $recipient = User::find($participant->user_id);
            if (!$recipient) continue;

            $this->notificationService->send(
                $recipient,
                new NotificationMessage(
                    type: 'new_chat_message',
                    title: 'رسالة جديدة',
                    message: $body ?? 'مرفق جديد',
                    sourceType: 'chat_message',
                    sourceId: $message->id,
                    periodKey: null,
                    metadata: [
                        'channel_id'   => $channel->id,
                        'channel_name' => $channel->name,
                        'sender_id'    => $senderId,
                    ],
                ),
                new NotificationOptions(
                    channels: ['in_app'],
                    priority: 'normal'
                )
            );
        }

        return $message->load('sender');
    }
}
