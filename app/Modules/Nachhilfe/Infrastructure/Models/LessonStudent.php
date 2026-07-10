<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;

class LessonStudent extends Pivot
{
    use HasUlids;

    protected $table = 'lesson_students';

    protected $primaryKey = 'id';
    protected $fillable = ['lesson_id', 'student_id', 'package_id', 'hours_consumed', 'notes'];
    public $incrementing = false;
    protected $keyType = 'string';

    public function lesson() { return $this->belongsTo(Lesson::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function package() { return $this->belongsTo(Package::class); }
    public function attendance() { return $this->hasOne(Attendance::class, 'lesson_student_id'); }
}
