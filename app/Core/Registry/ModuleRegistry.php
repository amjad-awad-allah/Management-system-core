<?php

namespace App\Core\Registry;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModuleRegistry
{
    /**
     * Check if a module is enabled using a cache-first strategy.
     */
    public static function isEnabled(string $module): bool
    {
        $cacheKey = "module_enabled_{$module}";

        try {
            return (bool) Cache::remember($cacheKey, 3600, function () use ($module) {
                // Fallback to config if the schema settings table does not exist yet (like during migrations)
                if (!Schema::hasTable('module_settings')) {
                    return (bool) config("modules.{$module}.enabled", false);
                }

                return DB::table('module_settings')
                    ->where('module', $module)
                    ->where('enabled', true)
                    ->exists();
            });
        } catch (\Throwable $e) {
            // Fallback to static configuration if database or cache check fails
            return (bool) config("modules.{$module}.enabled", false);
        }
    }
}
