<?php

namespace Tests\Feature\Core;

use App\Core\Models\Role;
use App\Core\Models\User;
use App\Core\Services\CenterSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CenterSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

        $this->admin = User::forceCreate([
            'name' => 'Center Admin',
            'email' => 'admin@center.de',
            'password' => 'password',
        ]);
        $this->admin->assignRole($adminRole);

        $this->student = User::forceCreate([
            'name' => 'Regular Student',
            'email' => 'student@center.de',
            'password' => 'password',
        ]);
        $this->student->assignRole($studentRole);
    }

    public function test_can_fetch_center_settings_payload_and_available_bundeslaender(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJsonPath('center.name', 'Muster Nachhilfeinstitut')
            ->assertJsonPath('center.bundesland', 'NW')
            ->assertJsonPath('center.bundesland_name', 'Nordrhein-Westfalen')
            ->assertJsonPath('permissions.can_manage', true);

        $states = $response->json('available_bundeslaender');
        $this->assertCount(16, $states);
        $this->assertEquals('NW', $states[9]['code']);
    }

    public function test_admin_can_update_center_name_and_bundesland(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/settings', [
                'settings' => [
                    ['key' => 'center_name', 'value' => 'Elite Akademie Bayern'],
                    ['key' => 'center_bundesland', 'value' => 'BY'],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.center.name', 'Elite Akademie Bayern')
            ->assertJsonPath('data.center.bundesland', 'BY')
            ->assertJsonPath('data.center.bundesland_name', 'Bayern');

        $this->assertDatabaseHas('settings', [
            'key' => 'center_name',
            'value' => 'Elite Akademie Bayern',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'center_bundesland',
            'value' => 'BY',
        ]);

        // Verify Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'center_settings_updated',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_non_authorized_user_cannot_modify_center_settings(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson('/api/v1/settings', [
                'settings' => [
                    ['key' => 'center_name', 'value' => 'Hacked Name'],
                ],
            ]);

        $response->assertStatus(403);
    }

    public function test_center_logo_upload_atomic_replacement_and_delete(): void
    {
        // 1. Upload initial logo
        $file1 = UploadedFile::fake()->create('logo1.png', 100, 'image/png');
        $upload1 = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/settings/logo', [
                'logo' => $file1,
            ]);

        $upload1->assertStatus(200)->assertJsonStructure(['logo_url', 'message']);
        $service = app(CenterSettingsService::class);
        $path1 = $service->getCenterLogoPath();
        $this->assertNotNull($path1);
        Storage::disk('public')->assertExists($path1);

        // 2. Atomic replacement with logo2
        $file2 = UploadedFile::fake()->create('logo2.webp', 100, 'image/webp');
        $upload2 = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/settings/logo', [
                'logo' => $file2,
            ]);

        $upload2->assertStatus(200);
        $path2 = $service->getCenterLogoPath();
        $this->assertNotEquals($path1, $path2);
        Storage::disk('public')->assertExists($path2);
        Storage::disk('public')->assertMissing($path1); // Old logo unlinked

        // 3. Delete logo
        $delete = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/v1/settings/logo');

        $delete->assertStatus(200);
        $this->assertNull($service->getCenterLogoPath());
        Storage::disk('public')->assertMissing($path2);
    }

    public function test_invalid_bundesland_code_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/settings', [
                'settings' => [
                    ['key' => 'center_bundesland', 'value' => 'INVALID_STATE'],
                ],
            ]);

        $response->assertStatus(422);
    }
}
