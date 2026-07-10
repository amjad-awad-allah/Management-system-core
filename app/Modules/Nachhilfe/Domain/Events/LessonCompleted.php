<?php

namespace App\Modules\Nachhilfe\Domain\Events;

use App\Modules\Nachhilfe\Infrastructure\Models\Lesson;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LessonCompleted
{
    use Dispatchable, SerializesModels;

    public Lesson $lesson;

    /**
     * Create a new event instance.
     */
    public function __construct(Lesson $lesson)
    {
        $this->lesson = $lesson;
    }
}
