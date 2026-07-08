<?php

namespace App\Modules\Nachhilfe\Domain\Events;

use App\Modules\Nachhilfe\Infrastructure\Models\Attendance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceMarkedEvent
{
    use Dispatchable, SerializesModels;

    public Attendance $attendance;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }
}
