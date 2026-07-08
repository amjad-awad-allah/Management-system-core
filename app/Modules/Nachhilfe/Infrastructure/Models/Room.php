<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'rooms';

    protected $fillable = ['name', 'capacity', 'type', 'description', 'is_active'];

}
