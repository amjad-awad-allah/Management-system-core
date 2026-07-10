<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'student_id',
        'month',
        'total_amount',
        'status',
        'due_date',
        'paid_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
