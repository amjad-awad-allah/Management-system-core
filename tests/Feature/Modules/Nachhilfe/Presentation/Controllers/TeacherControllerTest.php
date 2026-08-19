<?php

use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use Illuminate\Support\Str;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    cache()->flush(); // Clear EnsureModuleActive cache
    DB::table('module_settings')->insert([
        'id' => Str::ulid()->toString(),
        'module' => 'Nachhilfe',
        'enabled' => true,
    ]);
});

test('can create a teacher', function () {
    $user = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'T', 'email' => 'tr1@t.com', 'password' => 'p']);

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/nachhilfe/teachers', [
        'user_id' => (string) Str::ulid(),
        'name' => 'Mr. Smith',
        'qualification' => 'M.Sc. Mathematics',
        'hourly_rate' => 25.50,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Mr. Smith')
        ->assertJsonPath('data.qualification', 'M.Sc. Mathematics')
        ->assertJsonPath('data.hourly_rate', 25.5);

    $this->assertDatabaseHas('teachers', ['name' => 'Mr. Smith']);
});

test('can list teachers', function () {
    $user = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'T', 'email' => 'tr2@t.com', 'password' => 'p']);
    
    $teacher = new Teacher();
    $teacher->id = (string) Str::ulid();
    $teacher->user_id = (string) Str::ulid();
    $teacher->name = 'Mrs. Robinson';
    $teacher->qualification = 'B.A. English';
    $teacher->hourly_rate = 30.00;
    $teacher->save();

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/nachhilfe/teachers');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Mrs. Robinson');
});

test('can view a soft-deleted teacher', function () {
    $user = User::forceCreate(['id' => (string) Str::ulid(), 'name' => 'T', 'email' => 'tr3@t.com', 'password' => 'p']);
    
    $teacher = new Teacher();
    $teacher->id = (string) Str::ulid();
    $teacher->user_id = (string) Str::ulid();
    $teacher->name = 'Deleted Teacher';
    $teacher->qualification = 'B.A. English';
    $teacher->hourly_rate = 30.00;
    $teacher->save();

    $teacher->delete(); // Soft delete

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/nachhilfe/teachers/' . $teacher->id);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Deleted Teacher');
});
