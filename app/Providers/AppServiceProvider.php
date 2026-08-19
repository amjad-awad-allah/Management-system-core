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

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Core\Console\Commands\OutboxWorkCommand::class,
                \App\Core\Console\Commands\NotificationCleanupCommand::class,
                \App\Modules\Nachhilfe\Presentation\Console\CheckPackageAlertsCommand::class,
                \App\Modules\Nachhilfe\Presentation\Console\SendLessonRemindersCommand::class,
            ]);
        }

        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Nachhilfe\Domain\Events\LessonCompleted::class,
            \App\Modules\Nachhilfe\Application\Listeners\PayrollListener::class
        );
    }
}
