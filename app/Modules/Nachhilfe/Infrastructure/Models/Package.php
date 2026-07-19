<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'packages';

    protected $fillable = [
        'id',
        'name',
        'description',
        'hours',
        'price',
        'is_active',
    ];

    protected $casts = [
        'hours' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function lessonStudents()
    {
        return $this->hasMany(LessonStudent::class);
    }
}
