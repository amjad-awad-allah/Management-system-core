<?php

use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    DB::table('module_settings')->insert([
        'id' => Str::ulid()->toString(),
        'module' => 'Nachhilfe',
        'enabled' => true,
    ]);
});
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure module is active in DB registry for tests
    DB::table('settings')->updateOrInsert(
        ['key' => 'modules.Nachhilfe.enabled'],
        ['id' => (string) Str::ulid(), 'value' => 'true']
    );
});

test('can list subjects', function () {
    $user = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'T', 'email' => 't1@t.com', 'password' => 'p']);
    SubjectModel::create(['id' => '01H...1', 'name' => 'Math']);
    SubjectModel::create(['id' => '01H...2', 'name' => 'Physics']);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/nachhilfe/subjects');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'description', 'is_active', 'created_at']
            ]
        ]);
});

test('can create a subject', function () {
    $user = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'T', 'email' => 't2@t.com', 'password' => 'p']);

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/nachhilfe/subjects', [
        'name' => 'Chemistry',
        'description' => 'Chemistry 101',
        'is_active' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Chemistry');

    $this->assertDatabaseHas('subjects', ['name' => 'Chemistry']);
});
