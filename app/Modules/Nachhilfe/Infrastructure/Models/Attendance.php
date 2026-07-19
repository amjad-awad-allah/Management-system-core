<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasUlids, \App\Core\Models\Traits\Auditable;

    protected static function booted()
    {
        static::saved(function ($model) {
            $studentId = $model->lessonStudent?->student_id;
            if ($studentId) {
                \Illuminate\Support\Facades\Cache::forever("student:{$studentId}:timeline_version", time());
            }
        });
        static::deleted(function ($model) {
            $studentId = $model->lessonStudent?->student_id;
            if ($studentId) {
                \Illuminate\Support\Facades\Cache::forever("student:{$studentId}:timeline_version", time());
            }
        });
    }

    protected $table = 'attendances';

    protected $fillable = ['lesson_student_id', 'status', 'marked_by', 'marked_at', 'note'];

    protected function casts(): array
    {
        return ['marked_at' => 'datetime'];
    }

    public function lessonStudent() { return $this->belongsTo(LessonStudent::class); }
}
