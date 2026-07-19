<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class StudentDocument extends Model
{
    use HasUlids, \App\Core\Models\Traits\Auditable;

    protected static function booted()
    {
        static::saved(fn($model) => \Illuminate\Support\Facades\Cache::forever("student:{$model->student_id}:timeline_version", microtime(true)));
        static::deleted(fn($model) => \Illuminate\Support\Facades\Cache::forever("student:{$model->student_id}:timeline_version", microtime(true)));
    }

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'student_documents';

    protected $fillable = [
        'id',
        'student_id',
        'category',
        'title',
        'file_path',
        'mime_type',
        'size',
        'uploaded_by',
        'document_date',
        'expires_at',
    ];

    protected $casts = [
        'document_date' => 'date',
        'expires_at' => 'date',
        'size' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\User::class, 'uploaded_by');
    }
}
