<?php

namespace Tests\Feature\Core;

use App\Core\Models\User;
use App\Core\Models\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::forceCreate([
            'name' => 'Test User',
            'email' => 'pref_user@test.com',
            'password' => 'password',
        ]);
    }

    public function test_can_fetch_and_update_notification_preferences(): void
    {
        // 1. Fetch preferences initially (should be empty)
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications/preferences');

        $response->assertStatus(200)->assertJsonCount(0);

        // 2. Update preferences
        $updateResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'preferences' => [
                    [
                        'notification_type' => 'voucher_low_hours',
                        'channel' => 'whatsapp',
                        'enabled' => false,
                        'quiet_hours_start' => '22:00',
                        'quiet_hours_end' => '08:00'
                    ],
                    [
                        'notification_type' => 'voucher_expiring_soon',
                        'channel' => 'in_app',
                        'enabled' => true,
                        'quiet_hours_start' => null,
                        'quiet_hours_end' => null
                    ]
                ]
            ]);

        $updateResponse->assertStatus(200)->assertJsonPath('message', 'Notification preferences updated successfully');

        // 3. Verify in database
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => 'voucher_low_hours',
            'channel' => 'whatsapp',
            'enabled' => false,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00'
        ]);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => 'voucher_expiring_soon',
            'channel' => 'in_app',
            'enabled' => true
        ]);

        // 4. Fetch again to verify response structures
        $fetchResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications/preferences');

        $fetchResponse->assertStatus(200)->assertJsonCount(2);
    }
}
