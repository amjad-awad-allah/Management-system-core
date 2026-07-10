<?php

namespace App\Modules\Billing\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
    ];

    protected $appends = ['total_paid', 'balance'];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getBalanceAttribute()
    {
        return max(0, $this->amount - $this->total_paid);
    }
}
