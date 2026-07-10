<?php

namespace App\Modules\Billing\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Modules\Nachhilfe\Domain\Events\AttendanceMarkedEvent;
use App\Modules\Billing\Application\Listeners\DeductVoucherListener;

class BillingServiceProvider extends ServiceProvider
{
    /**
     * Register any module services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Shared\Contracts\Billing\BillingContract::class,
            \App\Modules\Billing\Application\Services\BillingService::class
        );
    }

    /**
     * Bootstrap any module services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Persistence/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Presentation/Routes/api.php');
        $this->loadViewsFrom(__DIR__ . '/../Presentation/Views', 'billing');

        // Listen for attendance to deduct voucher hours
        Event::listen(
            AttendanceMarkedEvent::class,
            DeductVoucherListener::class
        );
    }
}
