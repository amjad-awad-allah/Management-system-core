<?php

use Illuminate\Support\Facades\Route;
use App\Core\Http\Middleware\EnsureModuleActive;

beforeEach(function () {
    // Dynamic mock routes protected by module.active middleware
    Route::middleware(['api', 'module.active:Nachhilfe'])->get('/test-nachhilfe', function () {
        return response()->json(['status' => 'ok']);
    });

    Route::middleware(['api', 'module.active:Beauty'])->get('/test-beauty', function () {
        return response()->json(['status' => 'ok']);
    });
});

test('allows request to active module', function () {
    $response = $this->getJson('/test-nachhilfe');
    $response->assertStatus(200)
             ->assertJson(['status' => 'ok']);
});

test('blocks request to disabled module with 403 response', function () {
    $response = $this->getJson('/test-beauty');
    $response->assertStatus(403)
             ->assertJson([
                 'success' => false,
                 'error' => [
                     'code' => 'MODULE_DISABLED',
                     'message' => 'The requested module [Beauty] is currently disabled.'
                 ]
             ]);
});
