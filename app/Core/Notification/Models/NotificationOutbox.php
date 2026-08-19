<?php

namespace App\Core\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NotificationOutbox extends Model
{
    protected $table = 'notification_outbox';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'notification_event_id',
        'correlation_id',
        'recipient_user_id',
        'channel',
        'payload',
        'status',
        'attempts',
        'max_attempts',
        'available_at',
        'processed_at',
        'error_log',
    ];

    protected $casts = [
        'payload' => 'array',
        'available_at' => 'datetime',
        'processed_at' => 'datetime',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::ulid();
            }
            if (empty($model->tenant_id)) {
                $model->tenant_id = 'default';
            }
            if (empty($model->available_at)) {
                $model->available_at = now();
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(NotificationEvent::class, 'notification_event_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('available_at', '<=', now());
    }

    public function scopeDead($query)
    {
        return $query->where('status', 'dead');
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
