<?php

use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
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

test('can create a student', function () {
    $user = User::forceCreate(['id' => (string) \Illuminate\Support\Str::ulid(), 'name' => 'T', 'email' => 's1@t.com', 'password' => 'p']);

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/nachhilfe/students', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '2010-05-15',
        'school' => 'High School',
        'grade' => 10,
        'parent_name_1' => 'Parent One',
        'parent_phone_1' => '123456789'
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.first_name', 'John')
        ->assertJsonPath('data.last_name', 'Doe')
        ->assertJsonPath('data.school', 'High School')
        ->assertJsonPath('data.grade', 10);

    $this->assertDatabaseHas('students', ['first_name' => 'John', 'last_name' => 'Doe']);
});

test('can list students', function () {
    $user = User::forceCreate(['id' => (string) \Illuminate\Support\Str::ulid(), 'name' => 'T', 'email' => 's2@t.com', 'password' => 'p']);
    
    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Jane';
    $student->last_name = 'Doe';
    $student->birth_date = '2012-01-01';
    $student->school = 'Middle School';
    $student->grade = 8;
    $student->save();

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/nachhilfe/students');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.first_name', 'Jane');
});

test('can view a soft-deleted student', function () {
    $user = User::forceCreate(['id' => (string) \Illuminate\Support\Str::ulid(), 'name' => 'T', 'email' => 's3@t.com', 'password' => 'p']);
    
    $student = new Student();
    $student->id = (string) Str::ulid();
    $student->first_name = 'Deleted';
    $student->last_name = 'Student';
    $student->birth_date = '2012-01-01';
    $student->school = 'Middle School';
    $student->grade = 8;
    $student->save();

    $student->delete(); // Soft delete

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/nachhilfe/students/' . $student->id);

    $response->assertStatus(200)
        ->assertJsonPath('data.first_name', 'Deleted');
});
