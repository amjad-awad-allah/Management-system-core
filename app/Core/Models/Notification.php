<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Notification extends Model
{
    use HasUlids;

    protected $table = 'notifications';

    protected $fillable = [
        'id',
        'notification_group_id',
        'user_id',
        'recipient_type',
        'recipient_id',
        'delivery_channel',
        'type',
        'source_type',
        'source_id',
        'title',
        'message',
        'metadata',
        'external_id',
        'priority',
        'delivery_status',
        'period_key',
        'retry_count',
        'last_attempt_at',
        'read_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_attempt_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
