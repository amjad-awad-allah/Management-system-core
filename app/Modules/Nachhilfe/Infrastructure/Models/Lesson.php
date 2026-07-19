<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasUlids, SoftDeletes;

    protected static function booted()
    {
        static::saved(function ($lesson) {
            // Check if relationship is loaded/available to prevent loop issues during migrations or seeding
            if ($lesson->relationLoaded('students') || $lesson->students()->exists()) {
                foreach ($lesson->students as $student) {
                    \Illuminate\Support\Facades\Cache::forever("student:{$student->id}:timeline_version", time());
                }
            }
        });
        static::deleted(function ($lesson) {
            if ($lesson->relationLoaded('students') || $lesson->students()->exists()) {
                foreach ($lesson->students as $student) {
                    \Illuminate\Support\Facades\Cache::forever("student:{$student->id}:timeline_version", time());
                }
            }
        });
    }

    protected $table = 'lessons';

    protected $fillable = ['teacher_id', 'room_id', 'subject_id', 'schedule_template_id', 'type', 'date', 'start_time', 'end_time', 'duration_minutes', 'status', 'notes'];

    public function teacher() { return $this->belongsTo(Teacher::class); }
    public function room() { return $this->belongsTo(Room::class); }
    public function subject() { return $this->belongsTo(SubjectModel::class); }
    public function scheduleTemplate() { return $this->belongsTo(ScheduleTemplate::class); }
    public function students() { return $this->belongsToMany(Student::class, 'lesson_students')->withPivot('id', 'package_id', 'hours_consumed', 'notes')->withTimestamps()->using(LessonStudent::class); }
    public function lessonStudents() { return $this->hasMany(LessonStudent::class); }
    public function usages() { return $this->hasMany(SubscriptionUsage::class); }
    public function payrollItems() { return $this->hasMany(TeacherPayrollItem::class); }
}
