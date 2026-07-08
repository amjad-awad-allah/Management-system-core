<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleTemplate extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'schedule_templates';

    protected $fillable = ['teacher_id', 'room_id', 'subject_id', 'frequency', 'interval', 'start_date', 'end_date', 'days_of_week', 'start_time', 'end_time', 'is_active'];

    protected function casts(): array
    {
        return ['days_of_week' => 'array'];
    }

}
