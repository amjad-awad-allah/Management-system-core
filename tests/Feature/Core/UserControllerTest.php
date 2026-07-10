<?php

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Core\Models\User;
use App\Core\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_can_list_users()
    {
        $admin = User::whereHas('roles', function($q) {
            $q->where('name', 'Super Admin');
        })->first();

        // Create a regular user
        User::factory()->create(['name' => 'Regular User']);

        $response = $this->actingAs($admin)->getJson('/api/v1/users');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['id', 'name', 'email', 'roles']]]);
    }

    public function test_super_admin_can_create_user()
    {
        $admin = User::whereHas('roles', function($q) {
            $q->where('name', 'Super Admin');
        })->first();

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'password123',
            'roles' => ['Teacher']
        ];

        $response = $this->actingAs($admin)->postJson('/api/v1/users', $userData);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'New User');

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@test.com'
        ]);

        $newUser = User::where('email', 'newuser@test.com')->first();
        $this->assertTrue($newUser->hasRole('Teacher'));
    }

    public function test_super_admin_can_update_user_roles()
    {
        $admin = User::whereHas('roles', function($q) {
            $q->where('name', 'Super Admin');
        })->first();

        $targetUser = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'roles' => ['Center Manager']
        ];

        $response = $this->actingAs($admin)->putJson('/api/v1/users/' . $targetUser->id, $updateData);

        $response->assertStatus(200);

        $targetUser->refresh();
        $this->assertEquals('Updated Name', $targetUser->name);
        $this->assertTrue($targetUser->hasRole('Center Manager'));
    }

    public function test_super_admin_cannot_delete_themselves()
    {
        $admin = User::whereHas('roles', function($q) {
            $q->where('name', 'Super Admin');
        })->first();

        $response = $this->actingAs($admin)->deleteJson('/api/v1/users/' . $admin->id);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Cannot delete yourself']);
    }

    public function test_unauthenticated_user_cannot_access_users()
    {
        $response = $this->getJson('/api/v1/users');
        $response->assertStatus(401);
    }
}
