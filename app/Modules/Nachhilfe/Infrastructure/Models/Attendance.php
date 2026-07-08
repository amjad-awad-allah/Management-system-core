<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasUlids;

    protected $table = 'attendances';

    protected $fillable = ['lesson_student_id', 'status', 'marked_by', 'marked_at', 'note'];

    protected function casts(): array
    {
        return ['marked_at' => 'datetime'];
    }

    public function lessonStudent() { return $this->belongsTo(LessonStudent::class); }
}
