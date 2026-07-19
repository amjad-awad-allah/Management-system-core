<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Models\User;

class ChatChannel extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'chat_channels';

    protected $fillable = [
        'type',
        'name',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChatParticipant::class, 'channel_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'channel_id')->orderBy('created_at');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'channel_id')->latest()->limit(1);
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class, 'channel_id');
    }

    /**
     * Get the participant record for a given user.
     */
    public function participantFor(string $userId): ?ChatParticipant
    {
        return $this->participants()->where('user_id', $userId)->first();
    }

    /**
     * Check if a user is a participant (any role).
     */
    public function hasParticipant(string $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }
}
