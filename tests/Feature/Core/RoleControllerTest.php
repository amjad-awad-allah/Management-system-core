<?php

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Core\Models\User;
use App\Core\Models\Role;
use App\Core\Models\Permission;
use Database\Seeders\RolesAndPermissionsSeeder;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_can_list_roles()
    {
        $admin = User::whereHas('roles', function($q) {
            $q->where('name', 'Super Admin');
        })->first();

        $response = $this->actingAs($admin)->getJson('/api/v1/roles');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['id', 'name', 'permissions']]]);
    }

    public function test_can_create_role_with_permissions()
    {
        $admin = User::whereHas('roles', function($q) {
            $q->where('name', 'Super Admin');
        })->first();

        $roleData = [
            'name' => 'Custom Role',
            'permissions' => ['manage lessons', 'view dashboard']
        ];

        $response = $this->actingAs($admin)->postJson('/api/v1/roles', $roleData);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'Custom Role');

        $this->assertDatabaseHas('roles', [
            'name' => 'Custom Role'
        ]);
        
        $role = Role::where('name', 'Custom Role')->first();
        $this->assertTrue($role->hasPermissionTo('manage lessons'));
        $this->assertTrue($role->hasPermissionTo('view dashboard'));
    }

    public function test_cannot_modify_super_admin_role()
    {
        $admin = User::whereHas('roles', function($q) {
            $q->where('name', 'Super Admin');
        })->first();

        $superAdminRole = Role::where('name', 'Super Admin')->first();

        $updateData = [
            'name' => 'Hacked Admin',
            'permissions' => []
        ];

        $response = $this->actingAs($admin)->putJson('/api/v1/roles/' . $superAdminRole->id, $updateData);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Cannot modify Super Admin role']);
                 
        $this->assertDatabaseHas('roles', [
            'name' => 'Super Admin'
        ]);
    }

    public function test_cannot_delete_super_admin_role()
    {
        $admin = User::whereHas('roles', function($q) {
            $q->where('name', 'Super Admin');
        })->first();

        $superAdminRole = Role::where('name', 'Super Admin')->first();

        $response = $this->actingAs($admin)->deleteJson('/api/v1/roles/' . $superAdminRole->id);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Cannot delete Super Admin role']);
                 
        $this->assertDatabaseHas('roles', [
            'name' => 'Super Admin'
        ]);
    }
}
