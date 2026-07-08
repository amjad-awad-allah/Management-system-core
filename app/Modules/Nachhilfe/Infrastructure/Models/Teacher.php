<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'teachers';

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'qualification',
        'hourly_rate',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
        ];
    }
}
