<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Models\User;

class SurveyResponse extends Model
{
    use HasUlids;

    protected $table = 'survey_responses';

    protected $fillable = [
        'survey_id',
        'user_id',
        'answers',
        'submitted_at',
    ];

    protected $casts = [
        'answers'      => 'array',
        'submitted_at' => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
