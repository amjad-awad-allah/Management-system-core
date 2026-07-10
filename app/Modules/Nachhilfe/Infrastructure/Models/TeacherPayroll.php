<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class TeacherPayroll extends Model
{
    use HasUlids;

    protected $guarded = [];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function items()
    {
        return $this->hasMany(TeacherPayrollItem::class, 'payroll_id');
    }
}
