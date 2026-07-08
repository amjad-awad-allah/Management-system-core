<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

class CancellationPolicy extends Model
{
    use HasUlids;

    protected $table = 'cancellation_policies';

    protected $fillable = ['name', 'hours_before', 'deduct_percentage', 'description', 'is_active'];

}
