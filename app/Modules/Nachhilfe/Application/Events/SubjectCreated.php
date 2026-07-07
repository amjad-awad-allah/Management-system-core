<?php

namespace App\Modules\Nachhilfe\Application\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubjectCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $subjectId,
        public readonly string $name,
    ) {}
}
