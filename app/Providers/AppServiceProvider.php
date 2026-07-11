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
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Nachhilfe\Domain\Events\LessonCompleted::class,
            \App\Modules\Nachhilfe\Application\Listeners\PayrollListener::class
        );
    }
}
