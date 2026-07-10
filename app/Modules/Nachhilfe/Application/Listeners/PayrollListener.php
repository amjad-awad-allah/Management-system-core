<?php

namespace App\Modules\Nachhilfe\Application\Listeners;

use App\Modules\Nachhilfe\Domain\Events\LessonCompleted;
use App\Modules\Nachhilfe\Application\Services\PayrollService;
use Illuminate\Contracts\Queue\ShouldQueue;

class PayrollListener implements ShouldQueue
{
    private PayrollService $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function handle(LessonCompleted $event): void
    {
        $this->payrollService->generateItemForLesson($event->lesson);
    }
}
