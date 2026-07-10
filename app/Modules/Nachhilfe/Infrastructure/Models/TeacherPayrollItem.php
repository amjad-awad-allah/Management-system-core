<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class TeacherPayrollItem extends Model
{
    use HasUlids;

    protected $guarded = [];

    public function payroll()
    {
        return $this->belongsTo(TeacherPayroll::class, 'payroll_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
