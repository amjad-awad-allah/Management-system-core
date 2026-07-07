<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $phone_bidx
 * @property string|null $email_bidx
 * @property string|null $phone
 * @property string|null $email
 */
class StudentParent extends Model
{
    use HasUlids;

    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'phone_bidx',
        'email',
        'email_bidx',
    ];

    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
            'email' => 'encrypted',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function (StudentParent $parent) {
            $saltConfig = config('app.encryption_salt', 'default_salt');
            $salt = is_string($saltConfig) ? $saltConfig : 'default_salt';
            
            if ($parent->isDirty('phone') && !empty($parent->phone)) {
                $parent->phone_bidx = hash('sha256', $parent->phone . $salt);
            }
            if ($parent->isDirty('email') && !empty($parent->email)) {
                $parent->email_bidx = hash('sha256', strtolower($parent->email) . $salt);
            }
        });
    }
}
