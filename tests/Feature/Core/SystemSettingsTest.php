<?php

namespace Tests\Feature\Core;

use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::forceCreate([
            'name' => 'Admin User',
            'email' => 'admin_settings@test.com',
            'password' => 'password',
        ]);
    }

    public function test_can_fetch_and_update_settings(): void
    {
        // 1. Fetch settings initially (should be empty)
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/settings');

        $response->assertStatus(200)->assertJsonCount(0);

        // 2. Update settings (Twilio WhatsApp credentials)
        $updateResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/settings', [
                'settings' => [
                    [
                        'key' => 'twilio_sid',
                        'value' => 'AC_TEST_SID'
                    ],
                    [
                        'key' => 'twilio_token',
                        'value' => 'TOKEN_SECRET'
                    ],
                    [
                        'key' => 'twilio_from',
                        'value' => 'whatsapp:+14155238886'
                    ]
                ]
            ]);

        $updateResponse->assertStatus(200)->assertJsonPath('message', 'Settings updated successfully');

        // 3. Verify in database
        $this->assertDatabaseHas('settings', [
            'key' => 'twilio_sid',
            'value' => 'AC_TEST_SID'
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'twilio_token',
            'value' => 'TOKEN_SECRET'
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'twilio_from',
            'value' => 'whatsapp:+14155238886'
        ]);

        // 4. Fetch again to verify response contains the keys
        $fetchResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/settings');

        $fetchResponse->assertStatus(200)
            ->assertJsonPath('twilio_sid', 'AC_TEST_SID')
            ->assertJsonPath('twilio_token', 'TOKEN_SECRET')
            ->assertJsonPath('twilio_from', 'whatsapp:+14155238886');
    }
}
