<?php

namespace App\Modules\Nachhilfe\Application\Actions\Chat;

use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatChannel;
use App\Modules\Nachhilfe\Infrastructure\Models\ChatParticipant;
use Illuminate\Support\Collection;

class CreateChannelAction
{
    /**
     * Create a new chat channel and add participants.
     *
     * @param  string        $createdBy     User ID of the creator
     * @param  string        $type          'direct' | 'group' | 'survey'
     * @param  string|null   $name          Channel name (required for group/survey)
     * @param  array         $participantIds User IDs to add as 'member'
     * @param  array         $observerIds   User IDs to add as 'observer' (admins watching silently)
     * @param  array|null    $metadata      Optional metadata
     */
    public function execute(
        string $createdBy,
        string $type,
        ?string $name,
        array $participantIds,
        array $observerIds = [],
        ?array $metadata = null
    ): ChatChannel {
        if ($type === 'direct' && count($participantIds) === 1) {
            $recipientId = $participantIds[0];
            $existingChannel = ChatChannel::withTrashed()
                ->where('type', 'direct')
                ->whereHas('participants', fn($q) => $q->where('user_id', $createdBy))
                ->whereHas('participants', fn($q) => $q->where('user_id', $recipientId))
                ->first();

            if ($existingChannel) {
                if ($existingChannel->trashed()) {
                    $existingChannel->restore();
                }
                return $existingChannel->load('participants');
            }
        }

        $channel = ChatChannel::create([
            'type'       => $type,
            'name'       => $name,
            'created_by' => $createdBy,
            'metadata'   => $metadata,
        ]);

        // Add the creator as admin
        ChatParticipant::create([
            'channel_id' => $channel->id,
            'user_id'    => $createdBy,
            'role'       => 'admin',
            'joined_at'  => now(),
        ]);

        // Add members (participants who get notifications)
        foreach (array_unique($participantIds) as $userId) {
            if ($userId === $createdBy) continue; // already added above
            ChatParticipant::create([
                'channel_id' => $channel->id,
                'user_id'    => $userId,
                'role'       => 'member',
                'joined_at'  => now(),
            ]);
        }

        // Add observers (admins watching silently — no push notification)
        foreach (array_unique($observerIds) as $userId) {
            if ($userId === $createdBy) continue;
            ChatParticipant::firstOrCreate(
                ['channel_id' => $channel->id, 'user_id' => $userId],
                ['role' => 'observer', 'joined_at' => now()]
            );
        }

        return $channel->load('participants');
    }
}
