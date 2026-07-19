<?php

namespace App\Core\Notification\Drivers;

use App\Core\Models\Notification;
use Illuminate\Support\Facades\Log;

class ChatDriver implements NotificationDriver
{
    public function send(Notification $notification): DeliveryResult
    {
        // In a real application, this would invoke the Chat API to insert a message in a conversation.
        // For now, we simulate by logging the event.
        Log::info("Chat system message sent to User {$notification->user_id}: [{$notification->title}] {$notification->message}");

        return new DeliveryResult('sent', 'chat_msg_' . uniqid());
    }
}
