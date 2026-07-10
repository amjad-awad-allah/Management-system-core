<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class TeacherAvailability extends Model
{
    use HasUlids;

    protected $guarded = [];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
