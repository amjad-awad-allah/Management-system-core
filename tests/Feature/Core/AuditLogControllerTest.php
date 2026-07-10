<?php

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Core\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;

class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_can_list_audit_logs()
    {
        $admin = User::whereHas('roles', function($q) {
            $q->where('name', 'Super Admin');
        })->first();

        // Create a user to trigger an audit log (via Auditable trait)
        User::factory()->create(['name' => 'Auditable User']);

        $response = $this->actingAs($admin)->getJson('/api/v1/audit-logs');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['id', 'event', 'auditable_type', 'auditable_id', 'user_name']]]);
                 
        // Verify that the count is at least 1, and event is created
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('created', $data[0]['event']);
        $this->assertEquals(User::class, $data[0]['auditable_type']);
    }
}
