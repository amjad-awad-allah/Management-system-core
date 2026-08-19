<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Holiday extends Model
{
    use HasUlids;

    protected $table = 'holidays';

    protected $fillable = [
        'id',
        'source',
        'external_id',
        'type',
        'name',
        'start_date',
        'end_date',
        'state',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];
}
