<?php

use Illuminate\Support\Facades\Broadcast;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatParticipant;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Private chat channel: private-chat.{channelId}
 * Only participants with role member or admin can subscribe.
 * Observers (admins watching silently) are intentionally excluded
 * from receiving real-time broadcasts; they see a silent badge instead.
 */
Broadcast::channel('chat.{channelId}', function ($user, string $channelId) {
    return ChatParticipant::where('channel_id', $channelId)
        ->where('user_id', $user->id)
        ->whereIn('role', ['member', 'admin'])
        ->exists();
});
