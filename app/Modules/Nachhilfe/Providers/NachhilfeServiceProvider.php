<?php

namespace App\Modules\Nachhilfe\Providers;

use Illuminate\Support\ServiceProvider;

class NachhilfeServiceProvider extends ServiceProvider
{
    /**
     * Register any module services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Modules\Nachhilfe\Domain\Repositories\LessonRepositoryInterface::class,
            \App\Modules\Nachhilfe\Infrastructure\Repositories\EloquentLessonRepository::class
        );
    }

    /**
     * Bootstrap any module services.
     */
    public function boot(): void
    {
        // Load routes and migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Persistence/Migrations');
    }
}
