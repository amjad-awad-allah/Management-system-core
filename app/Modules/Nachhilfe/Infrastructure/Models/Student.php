<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use SoftDeletes, HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'students';

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'birth_date',
        'school',
        'grade',
        'parent_phone_1',
        'parent_phone_2',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'encrypted',
        ];
    }

    public function lessons()
    {
        return $this->belongsToMany(Lesson::class, 'lesson_students')
            ->withPivot('id', 'package_id', 'hours_consumed', 'notes')
            ->withTimestamps()
            ->using(LessonStudent::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(SubjectModel::class, 'student_subjects', 'student_id', 'subject_id');
    }

    public function teachers()
    {
        return $this->hasManyThrough(Lesson::class, LessonStudent::class, 'student_id', 'id', 'id', 'lesson_id')
            ->join('teachers', 'teachers.id', '=', 'lessons.teacher_id')
            ->select('teachers.*')
            ->distinct();
    }

    public function lessonStudents()
    {
        return $this->hasMany(LessonStudent::class);
    }
}
