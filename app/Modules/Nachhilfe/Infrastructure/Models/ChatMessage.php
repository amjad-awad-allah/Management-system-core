<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Models\User;

class ChatMessage extends Model
{
    use HasUlids;

    protected $table = 'chat_messages';

    protected $fillable = [
        'channel_id',
        'sender_id',
        'body',
        'type',
        'metadata',
        'is_deleted',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'is_deleted' => 'boolean',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChatChannel::class, 'channel_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Whether this message contains a file/image attachment.
     */
    public function hasAttachment(): bool
    {
        return in_array($this->type, ['file', 'image']) && !empty($this->metadata['file_path']);
    }
}
