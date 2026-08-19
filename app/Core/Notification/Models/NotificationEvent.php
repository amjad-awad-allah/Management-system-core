<?php

namespace App\Core\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NotificationEvent extends Model
{
    protected $table = 'notification_events';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'event_type',
        'event_id',
        'notification_type',
        'recipient_user_id',
        'status',
        'scheduled_at',
        'triggered_at',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'triggered_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid();
            }
            if (empty($model->tenant_id)) {
                $model->tenant_id = 'default';
            }
        });
    }

    public function outboxItems(): HasMany
    {
        return $this->hasMany(NotificationOutbox::class, 'notification_event_id');
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
