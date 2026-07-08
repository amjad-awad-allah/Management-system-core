<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonModel extends Model
{
    use SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'lessons';

    protected $fillable = [
        'id',
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
