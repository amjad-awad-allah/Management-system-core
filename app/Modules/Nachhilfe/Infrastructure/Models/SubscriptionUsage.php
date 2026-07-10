<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class SubscriptionUsage extends Model
{
    use HasUlids;

    protected $guarded = [];

    public function subscription()
    {
        return $this->belongsTo(StudentPackage::class, 'subscription_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Core\Models\User::class, 'created_by');
    }
}
