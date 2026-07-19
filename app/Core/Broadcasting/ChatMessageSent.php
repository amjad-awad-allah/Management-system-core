<?php

namespace App\Core\Broadcasting;

use App\Modules\Nachhilfe\Infrastructure\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ChatMessage $message
    ) {}

    /**
     * Broadcast on a private channel per chat channel.
     * Channel name: private-chat.{channel_id}
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->channel_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->message->id,
            'channel_id' => $this->message->channel_id,
            'sender_id'  => $this->message->sender_id,
            'sender'     => $this->message->sender ? [
                'id'   => $this->message->sender->id,
                'name' => $this->message->sender->name,
            ] : null,
            'body'       => $this->message->body,
            'type'       => $this->message->type,
            'metadata'   => $this->message->metadata,
            'created_at' => $this->message->created_at?->toISOString(),
        ];
    }
}
