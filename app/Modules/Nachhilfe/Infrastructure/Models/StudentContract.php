<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

class StudentContract extends Model
{
    use HasUlids, \App\Core\Models\Traits\Auditable;

    protected static function booted()
    {
        static::saved(fn($model) => \Illuminate\Support\Facades\Cache::forever("student:{$model->student_id}:timeline_version", time()));
        static::deleted(fn($model) => \Illuminate\Support\Facades\Cache::forever("student:{$model->student_id}:timeline_version", time()));
    }

    protected $table = 'student_contracts';

    protected $fillable = [
        'student_id',
        'subject_id',
        'hours_per_week',
        'hourly_rate',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'hours_per_week' => 'integer',
        'hourly_rate' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(SubjectModel::class);
    }
}
