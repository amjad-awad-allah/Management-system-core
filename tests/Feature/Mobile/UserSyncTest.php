<?php

namespace Tests\Feature\Mobile;

use App\Core\Models\Role;
use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UserSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        cache()->flush();
        DB::table('module_settings')->insert([
            'id' => Str::ulid()->toString(),
            'module' => 'Nachhilfe',
            'enabled' => true,
        ]);
        Role::firstOrCreate(['name' => 'Student']);
        Role::firstOrCreate(['name' => 'Teacher']);
        // Super Admin role to bypass gates
        $role = Role::firstOrCreate(['name' => 'Super Admin']);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123')
        ]);
        $this->admin->assignRole($role);
    }

    public function test_creating_student_creates_user()
    {
        $payload = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => '2010-01-01',
            'grade' => 10,
            'school' => 'Test High',
            'parent_name' => 'Jane Doe',
            'parent_email' => 'jane@test.com',
            'parent_phone_1' => '1234567890'
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/nachhilfe/students', $payload);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('users', [
            'email' => 'jane@test.com',
        ]);

        $user = User::where('email', 'jane@test.com')->first();
        $this->assertTrue($user->hasRole('Student'));

        $this->assertDatabaseHas('students', [
            'first_name' => 'John',
            'user_id' => $user->id
        ]);
    }

    public function test_updating_student_email_syncs_user()
    {
        $payload = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => '2010-01-01',
            'grade' => 10,
            'school' => 'Test High',
            'parent_name' => 'Jane Doe',
            'parent_email' => 'jane@test.com',
            'parent_phone_1' => '1234567890'
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/nachhilfe/students', $payload);
        $studentId = $response->json('data.id');

        $updatePayload = $payload;
        $updatePayload['parent_email'] = 'newjane@test.com';

        $updateResponse = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/nachhilfe/students/{$studentId}", $updatePayload);
        $updateResponse->assertStatus(200);

        // It should have created a new user or found an existing one and assigned the new user_id
        $this->assertDatabaseHas('users', [
            'email' => 'newjane@test.com',
        ]);

        $newUser = User::where('email', 'newjane@test.com')->first();
        $this->assertDatabaseHas('students', [
            'id' => $studentId,
            'user_id' => $newUser->id
        ]);
    }

    public function test_creating_teacher_creates_user()
    {
        $payload = [
            'name' => 'Mr. Smith',
            'qualification' => 'PhD',
            'email' => 'smith@test.com',
            'phone' => '1234567890',
            'hourly_rate' => 25.0
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/nachhilfe/teachers', $payload);
        
        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'smith@test.com',
        ]);

        $user = User::where('email', 'smith@test.com')->first();
        $this->assertTrue($user->hasRole('Teacher'));

        $this->assertDatabaseHas('teachers', [
            'name' => 'Mr. Smith',
            'user_id' => $user->id
        ]);
    }

    public function test_updating_teacher_email_syncs_user()
    {
        $payload = [
            'name' => 'Mr. Smith',
            'qualification' => 'PhD',
            'email' => 'smith@test.com',
            'phone' => '1234567890',
            'hourly_rate' => 25.0
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/nachhilfe/teachers', $payload);
        $teacherId = $response->json('data.id');

        $updatePayload = $payload;
        $updatePayload['email'] = 'newsmith@test.com';

        $teacherUser = User::where('email', 'smith@test.com')->first();
        $userId = $teacherUser->id;

        $updateResponse = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/nachhilfe/teachers/{$teacherId}", $updatePayload);
        $updateResponse->assertStatus(200);

        // For teachers, we expect the EXISTING user's email to be updated
        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'email' => 'newsmith@test.com',
        ]);
    }
}
