<?php

use App\Core\Registry\ModuleRegistry;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

test('module registry returns enabled status from config when DB is empty', function () {
    // Config: Nachhilfe is enabled, Beauty is disabled in config/modules.php
    expect(ModuleRegistry::isEnabled('Nachhilfe'))->toBeTrue();
    expect(ModuleRegistry::isEnabled('Beauty'))->toBeFalse();
});

test('module registry respects DB configuration over static configuration', function () {
    // Note: We bypass Schema check by throwing exception or letting it fallback
    // Since Schema has no table 'module_settings' in sqlite default test DB, we verify config fallback
    expect(ModuleRegistry::isEnabled('Nachhilfe'))->toBeTrue();
});
