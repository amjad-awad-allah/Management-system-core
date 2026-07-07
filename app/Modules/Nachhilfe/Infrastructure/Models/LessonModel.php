<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonModel extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'lessons';

    protected $fillable = [
        'student_id',
        'teacher_id',
        'subject_id',
        'scheduled_at',
        'duration_minutes',
        'status',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'price' => 'decimal:2',
            'duration_minutes' => 'integer',
        ];
    }
}
