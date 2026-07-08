<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPackage extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'student_packages';

    protected $fillable = [
        'id',
        'student_id',
        'package_id',
        'subject_id',
        'funding_source',
        'voucher_reference',
        'total_hours',
        'remaining_hours',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'total_hours' => 'integer',
        'remaining_hours' => 'integer',
        'expires_at' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
