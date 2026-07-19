<?php

namespace App\Core\Providers;

use Illuminate\Support\ServiceProvider;
use App\Core\Auth\Contracts\AuthenticationContract;
use App\Core\Auth\Providers\SessionAuthenticationProvider;
use App\Core\Authorization\Contracts\AuthorizationContract;
use App\Core\Authorization\Providers\SpatieAuthorizationProvider;
use Illuminate\Support\Facades\Gate;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AuthenticationContract::class, SessionAuthenticationProvider::class);
        $this->app->singleton(AuthorizationContract::class, SpatieAuthorizationProvider::class);
        $this->app->singleton(\App\Shared\Contracts\Events\DomainEventBus::class, \App\Core\Events\OutboxEventBus::class);
        $this->app->singleton(\App\Core\Notification\Contracts\WhatsAppNotificationServiceInterface::class, \App\Core\Notification\Services\TwilioWhatsAppNotificationService::class);
        // Register Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Core\Console\Commands\LoadTestCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Core\Console\Commands\OutboxWorkCommand::class,
            ]);
        }

        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
