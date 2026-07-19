<?php

namespace App\Modules\Nachhilfe\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Modules\Nachhilfe\Domain\Events\VoucherExpiringSoon;
use App\Modules\Nachhilfe\Domain\Events\VoucherLowHours;
use App\Modules\Nachhilfe\Application\Listeners\VoucherAlertListener;
use App\Modules\Nachhilfe\Domain\Events\LessonCompleted;
use App\Modules\Nachhilfe\Application\Listeners\SubscriptionUsageListener;

class NachhilfeEventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(
            VoucherExpiringSoon::class,
            [VoucherAlertListener::class, 'handleExpiringSoon']
        );

        Event::listen(
            VoucherLowHours::class,
            [VoucherAlertListener::class, 'handleLowHours']
        );

        Event::listen(
            LessonCompleted::class,
            SubscriptionUsageListener::class
        );
    }
}
