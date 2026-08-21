<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOnboardingState extends Model
{
    use HasUlids;

    protected $table = 'user_onboarding_states';

    protected $fillable = [
        'id',
        'user_id',
        'tour_key',
        'version',
        'status',
        'last_step_id',
        'started_at',
        'completed_at',
        'skipped_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'skipped_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if this state is from an outdated tour version.
     */
    public function isOutdated(int $currentVersion): bool
    {
        return $this->version < $currentVersion;
    }
}
