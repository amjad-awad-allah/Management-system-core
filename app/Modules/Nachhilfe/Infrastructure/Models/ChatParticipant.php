<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Models\User;

class ChatParticipant extends Model
{
    use HasUlids;

    protected $table = 'chat_participants';

    protected $fillable = [
        'channel_id',
        'user_id',
        'role',
        'joined_at',
        'last_read_at',
    ];

    protected $casts = [
        'joined_at'    => 'datetime',
        'last_read_at' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChatChannel::class, 'channel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Observers should not receive push notifications.
     */
    public function isObserver(): bool
    {
        return $this->role === 'observer';
    }
}
