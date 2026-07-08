<?php

namespace App\Modules\Nachhilfe\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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

        $this->app->bind(
            \App\Modules\Nachhilfe\Domain\Repositories\StudentRepositoryInterface::class,
            \App\Modules\Nachhilfe\Infrastructure\Repositories\EloquentStudentRepository::class
        );

        $this->app->bind(
            \App\Modules\Nachhilfe\Domain\Repositories\TeacherRepositoryInterface::class,
            \App\Modules\Nachhilfe\Infrastructure\Repositories\EloquentTeacherRepository::class
        );
    }

    /**
     * Bootstrap any module services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Persistence/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Presentation/Routes/api.php');

        Gate::policy(\App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel::class, \App\Modules\Nachhilfe\Presentation\Policies\SubjectPolicy::class);
        Gate::policy(\App\Modules\Nachhilfe\Infrastructure\Models\LessonModel::class, \App\Modules\Nachhilfe\Presentation\Policies\LessonPolicy::class);
    }
}
