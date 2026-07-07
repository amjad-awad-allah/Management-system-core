<?php

namespace App\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class ModuleLoaderServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /** @var array<string, array{enabled: bool, provider: string}> $modules */
        $modules = config('modules') ?? [];
        /** @var array<string, array<string, mixed>> $loadedModules */
        $loadedModules = [];

        foreach ($modules as $name => $config) {
            if (!$config['enabled']) {
                continue;
            }

            $modulePath = app_path("Modules/{$name}");
            $manifestPath = "{$modulePath}/module.json";

            if (!file_exists($manifestPath)) {
                Log::warning("Module [{$name}] has no module.json manifest.");
                continue;
            }

            $rawContent = file_get_contents($manifestPath);
            if ($rawContent === false) {
                Log::error("Failed to read manifest path [{$manifestPath}] for module [{$name}].");
                continue;
            }

            /** @var array{name: string, version: string, enabled: bool, dependencies?: array<string>, description?: string} | null $manifest */
            $manifest = json_decode($rawContent, true);
            if (!is_array($manifest) || json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Failed to parse module.json for [{$name}]: " . json_last_error_msg());
                continue;
            }

            // Verify if the manifest itself says enabled
            if (!$manifest['enabled']) {
                continue;
            }

            // Verify dependencies
            $dependencies = $manifest['dependencies'] ?? [];
            $dependenciesSatisfied = true;

            foreach ($dependencies as $dependency) {
                $depConfig = $modules[$dependency] ?? null;
                if (!$depConfig || !$depConfig['enabled']) {
                    Log::error("Module [{$name}] requires dependency [{$dependency}] which is not enabled.");
                    $dependenciesSatisfied = false;
                    break;
                }
            }

            if (!$dependenciesSatisfied) {
                continue;
            }

            // Register the module service provider dynamically
            /** @var string $providerClass */
            $providerClass = $config['provider'];
            if (class_exists($providerClass)) {
                /** @var class-string<ServiceProvider> $providerClass */
                $this->app->register($providerClass);
                $loadedModules[$name] = $manifest;
            } else {
                Log::error("Service Provider [{$providerClass}] for Module [{$name}] not found.");
            }
        }

        // Store loaded modules in container for runtime queries
        $this->app->singleton('modules.loaded', function () use ($loadedModules) {
            return $loadedModules;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
