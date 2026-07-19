<?php

namespace App\Core\Notification\Drivers;

use App\Core\Models\Notification;
use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatChannel;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatMessage;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatParticipant;
use App\Modules\Nachhilfe\Application\Actions\Chat\CreateChannelAction;
use App\Core\Broadcasting\ChatMessageSent;
use Illuminate\Support\Facades\Log;

class ChatDriver implements NotificationDriver
{
    public function send(Notification $notification): DeliveryResult
    {
        try {
            $recipientId = $notification->user_id;
            $metadata = $notification->metadata ?? [];
            $channelId = $metadata['channel_id'] ?? null;
            $senderId = $metadata['sender_id'] ?? null;

            // Resolve Sender
            if (!$senderId) {
                // Fallback to first Super Admin user
                $admin = User::whereHas('roles', function ($query) {
                    $query->where('name', 'Super Admin');
                })->first();
                $senderId = $admin ? $admin->id : $recipientId; // fallback to recipient themselves if no admin
            }

            // Resolve Channel
            $channel = null;
            if ($channelId) {
                $channel = ChatChannel::find($channelId);
            }

            if (!$channel) {
                // Look for direct channel between sender and recipient
                $channel = ChatChannel::where('type', 'direct')
                    ->whereHas('participants', fn($q) => $q->where('user_id', $senderId))
                    ->whereHas('participants', fn($q) => $q->where('user_id', $recipientId))
                    ->first();

                if (!$channel) {
                    // Create direct channel
                    $createChannelAction = app(CreateChannelAction::class);
                    $channel = $createChannelAction->execute(
                        createdBy: $senderId,
                        type: 'direct',
                        name: null,
                        participantIds: [$recipientId]
                    );
                }
            }

            // Create real ChatMessage
            $message = ChatMessage::create([
                'channel_id' => $channel->id,
                'sender_id' => $senderId,
                'body' => "[{$notification->title}] {$notification->message}",
                'type' => 'system',
                'metadata' => [
                    'notification_id' => $notification->id,
                    'type' => $notification->type,
                ],
            ]);

            // Broadcast via Reverb for Real-Time UI update
            try {
                broadcast(new ChatMessageSent($message->load('sender')))->toOthers();
            } catch (\Exception $ex) {
                Log::warning("Reverb broadcast failed in ChatDriver: " . $ex->getMessage());
            }

            return new DeliveryResult('sent', $message->id);
        } catch (\Exception $e) {
            Log::error("ChatDriver failed to send notification: " . $e->getMessage());
            return new DeliveryResult('failed');
        }
    }
}
