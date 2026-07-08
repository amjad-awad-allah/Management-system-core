<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonStatusHistory extends Model
{
    use HasUlids;

    protected $table = 'lesson_status_history';

    protected $fillable = ['lesson_id', 'old_status', 'new_status', 'changed_by', 'reason', 'created_at'];

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

}
