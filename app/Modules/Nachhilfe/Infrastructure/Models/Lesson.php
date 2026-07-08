<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'lessons';

    protected $fillable = ['teacher_id', 'room_id', 'subject_id', 'schedule_template_id', 'type', 'date', 'start_time', 'end_time', 'duration_minutes', 'status', 'notes'];

    public function teacher() { return $this->belongsTo(Teacher::class); }
    public function room() { return $this->belongsTo(Room::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function scheduleTemplate() { return $this->belongsTo(ScheduleTemplate::class); }
    public function students() { return $this->belongsToMany(Student::class, 'lesson_students')->withPivot('id', 'package_id', 'hours_consumed', 'notes')->withTimestamps()->using(LessonStudent::class); }
    public function lessonStudents() { return $this->hasMany(LessonStudent::class); }
}
