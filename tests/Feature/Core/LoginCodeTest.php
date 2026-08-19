<?php

namespace Tests\Feature\Core;

use App\Core\Models\User;
use App\Core\Models\UserLoginCode;
use App\Core\Models\UserMobileDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginCodeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::forceCreate([
            'name' => 'Admin User',
            'email' => 'admin_code@test.com',
            'password' => bcrypt('adminpass123'),
        ]);

        \App\Core\Models\Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);
        \App\Core\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);

        $this->studentUser = User::forceCreate([
            'name' => 'Student User',
            'email' => 'student_code@test.com',
            'password' => bcrypt('studentpass123'),
        ]);
        $this->studentUser->assignRole('Student');
    }

    public function test_can_retrieve_and_regenerate_login_code(): void
    {
        // 1. Show endpoint will automatically generate an active login code
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/users/{$this->studentUser->id}/login-code");

        $response->assertStatus(200);
        $codeData = $response->json('login_code');
        $this->assertNotNull($codeData['code']);
        $this->assertEquals('active', $codeData['status']);

        $originalCode = $codeData['code'];

        // 2. Regenerate with incorrect password fails
        $regenResponseFail = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/users/{$this->studentUser->id}/login-code/regenerate", [
                'admin_password' => 'wrongpassword'
            ]);
        $regenResponseFail->assertStatus(422);

        // 3. Regenerate with correct password succeeds
        $regenResponseSuccess = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/users/{$this->studentUser->id}/login-code/regenerate", [
                'admin_password' => 'adminpass123'
            ]);

        $regenResponseSuccess->assertStatus(200);
        $newCodeData = $regenResponseSuccess->json('login_code');
        $this->assertNotEquals($originalCode, $newCodeData['code']);

        // Assert database has revoked status for original code
        $this->assertDatabaseHas('user_login_codes', [
            'user_id' => $this->studentUser->id,
            'code' => $originalCode,
            'status' => 'revoked'
        ]);

        $this->assertDatabaseHas('user_login_codes', [
            'user_id' => $this->studentUser->id,
            'code' => $newCodeData['code'],
            'status' => 'active'
        ]);
    }

    public function test_can_authenticate_via_mobile_and_track_device(): void
    {
        // Create an active login code
        $loginCode = UserLoginCode::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->studentUser->id,
            'code' => 'K98PLQXZ',
            'status' => 'active'
        ]);

        // Login using mobile code auth
        $loginResponse = $this->postJson('/api/v1/mobile/auth/code', [
            'code' => 'K98PLQXZ',
            'device_name' => 'Pixel 8'
        ]);

        $loginResponse->assertStatus(200);
        $loginResponse->assertJsonStructure(['access_token', 'token_type', 'user']);

        // Assert device entry is saved
        $this->assertDatabaseHas('user_mobile_devices', [
            'user_id' => $this->studentUser->id,
            'device_name' => 'Pixel 8'
        ]);
    }
}
