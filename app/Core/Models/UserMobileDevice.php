<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class UserMobileDevice extends Model
{
    use HasUlids;

    protected $table = 'user_mobile_devices';

    protected $fillable = [
        'id',
        'user_id',
        'device_name',
        'device_id',
        'token_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
