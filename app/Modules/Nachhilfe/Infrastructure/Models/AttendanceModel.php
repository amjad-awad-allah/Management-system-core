<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class AttendanceModel extends Model
{
    use HasUlids;

    protected $table = 'attendance';

    protected $fillable = [
        'lesson_id',
        'status',
        'notes',
    ];
}
