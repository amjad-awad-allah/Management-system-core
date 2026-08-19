<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class TeacherPayroll extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
        'total_hours' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'snapshot_hash_version' => 'integer',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function approver()
    {
        return $this->belongsTo(\App\Core\Models\User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(TeacherPayrollItem::class, 'payroll_id');
    }
}
