<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teacher extends Model
{
    use SoftDeletes, HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'teachers';

    protected $fillable = [
        'id', 'user_id', 'name', 'email', 'phone', 'qualification', 'hourly_rate', 'status'
    ];

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(SubjectModel::class, 'teacher_subjects', 'teacher_id', 'subject_id');
    }

    public function students()
    {
        return $this->hasManyThrough(LessonStudent::class, Lesson::class, 'teacher_id', 'lesson_id')
            ->join('students', 'students.id', '=', 'lesson_students.student_id')
            ->select('students.*')
            ->distinct();
    }

    public function scheduleTemplates()
    {
        return $this->hasMany(ScheduleTemplate::class);
    }

    public function availabilities()
    {
        return $this->hasMany(TeacherAvailability::class);
    }

    public function payrolls()
    {
        return $this->hasMany(TeacherPayroll::class);
    }

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
        ];
    }
}
