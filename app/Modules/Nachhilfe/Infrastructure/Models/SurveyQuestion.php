<?php

namespace App\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyQuestion extends Model
{
    use HasUlids;

    protected $table = 'survey_questions';

    protected $fillable = [
        'survey_id',
        'question',
        'type',
        'options',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'order'   => 'integer',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }
}
