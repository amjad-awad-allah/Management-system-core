<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'students';

    protected $fillable = [
        'id',
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
