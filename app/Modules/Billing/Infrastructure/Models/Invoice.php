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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
