<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'students';

    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'school',
        'grade',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'encrypted',
        ];
    }
}
