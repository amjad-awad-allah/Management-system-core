<?php

namespace App\Modules\Billing\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

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
    }
}
