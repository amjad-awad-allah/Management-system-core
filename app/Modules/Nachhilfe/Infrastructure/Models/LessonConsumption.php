<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonConsumption extends Model
{
    use HasUlids;

    protected $table = 'lesson_consumptions';

    protected $fillable = ['lesson_student_id', 'package_id', 'hours_used', 'consumption_type', 'balance_before', 'balance_after', 'notes'];

    public function lessonStudent() { return $this->belongsTo(LessonStudent::class); }
    public function package() { return $this->belongsTo(Package::class); }
}
