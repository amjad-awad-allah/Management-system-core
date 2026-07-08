<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Nachhilfe\Domain\Events\AttendanceMarkedEvent::class,
            \App\Modules\Billing\Application\Listeners\DeductVoucherListener::class
        );
    }
}
