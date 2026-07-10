<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

class InvoiceItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'invoice_id',
        'lesson_id',
        'description',
        'hours',
        'rate',
        'amount',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
