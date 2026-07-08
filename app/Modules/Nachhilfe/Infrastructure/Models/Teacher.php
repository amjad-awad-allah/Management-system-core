<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teacher extends Model
{
    use HasFactory;

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

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function scheduleTemplates()
    {
        return $this->hasMany(ScheduleTemplate::class);
    }

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
        ];
    }
}
