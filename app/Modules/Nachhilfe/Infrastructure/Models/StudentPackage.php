<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class StudentPackage extends Model
{
    use HasUlids;
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
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'total_hours' => 'decimal:2',
        'remaining_hours' => 'decimal:2',
        'expires_at' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
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

    public function usages()
    {
        return $this->hasMany(SubscriptionUsage::class, 'subscription_id');
    }
}
