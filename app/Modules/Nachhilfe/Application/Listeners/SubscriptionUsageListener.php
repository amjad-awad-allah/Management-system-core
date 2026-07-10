<?php

namespace App\Modules\Nachhilfe\Application\Listeners;

use App\Modules\Nachhilfe\Domain\Events\LessonCompleted;
use App\Modules\Nachhilfe\Application\Services\SubscriptionUsageService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SubscriptionUsageListener implements ShouldQueue
{
    private SubscriptionUsageService $usageService;

    public function __construct(SubscriptionUsageService $usageService)
    {
        $this->usageService = $usageService;
    }

    public function handle(LessonCompleted $event): void
    {
        $this->usageService->deductForLesson($event->lesson);
    }
}
