<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class NotificationPreference extends Model
{
    use HasUlids;

    protected $table = 'notification_preferences';

    protected $fillable = [
        'id',
        'user_id',
        'notification_type',
        'channel',
        'enabled',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
