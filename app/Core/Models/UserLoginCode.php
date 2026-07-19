<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class UserLoginCode extends Model
{
    use HasUlids;

    protected $table = 'user_login_codes';

    protected $fillable = [
        'id',
        'user_id',
        'code',
        'status',
        'last_used_at',
        'created_by'
    ];

    protected $casts = [
        'last_used_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
