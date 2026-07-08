<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBalance extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'student_balances';

    protected $fillable = [
        'id',
        'student_id',
        'remaining_hours',
    ];

    protected $casts = [
        'remaining_hours' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }
}
