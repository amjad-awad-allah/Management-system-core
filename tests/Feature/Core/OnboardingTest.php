<?php

namespace Tests\Feature\Core;

use App\Core\Models\User;
use App\Core\Models\UserOnboardingState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_retrieves_default_onboarding_state(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/onboarding/main_product_tour');

        $response->assertStatus(200)
            ->assertJson([
                'tour_key' => 'main_product_tour',
                'is_enabled' => true,
                'current_version' => 1,
                'state' => [
                    'user_id' => $this->user->id,
                    'tour_key' => 'main_product_tour',
                    'status' => 'not_started',
                    'version' => 1,
                    'last_step_id' => null,
                ],
            ]);

        $this->assertDatabaseHas('user_onboarding_states', [
            'user_id' => $this->user->id,
            'tour_key' => 'main_product_tour',
            'status' => 'not_started',
        ]);
    }

    public function test_user_can_start_step_and_complete_tour(): void
    {
        // 1. Start tour
        $startRes = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/main_product_tour/start');
        $startRes->assertStatus(200);

        $this->assertDatabaseHas('user_onboarding_states', [
            'user_id' => $this->user->id,
            'status' => 'in_progress',
        ]);

        // 2. Persist step
        $stepRes = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/main_product_tour/step', [
                'step_id' => 'schedule',
            ]);
        $stepRes->assertStatus(200);

        $this->assertDatabaseHas('user_onboarding_states', [
            'user_id' => $this->user->id,
            'last_step_id' => 'schedule',
            'status' => 'in_progress',
        ]);

        // 3. Complete tour
        $completeRes = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/main_product_tour/complete');
        $completeRes->assertStatus(200);

        $this->assertDatabaseHas('user_onboarding_states', [
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);
        $this->assertNotNull(UserOnboardingState::where('user_id', $this->user->id)->first()->completed_at);
    }

    public function test_version_bump_clears_stale_resume_state(): void
    {
        // Seed completed state on version 1
        UserOnboardingState::create([
            'user_id' => $this->user->id,
            'tour_key' => 'main_product_tour',
            'version' => 1,
            'status' => 'completed',
            'last_step_id' => 'billing',
            'completed_at' => now()->subDays(2),
        ]);

        // Bump application config version to 2
        Config::set('onboarding.tours.main_product_tour.version', 2);

        // Fetch state via API
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/onboarding/main_product_tour');

        $response->assertStatus(200)
            ->assertJson([
                'current_version' => 2,
                'state' => [
                    'version' => 2,
                    'status' => 'not_started',
                    'last_step_id' => null,
                    'completed_at' => null,
                    'skipped_at' => null,
                ],
            ]);

        $this->assertDatabaseHas('user_onboarding_states', [
            'user_id' => $this->user->id,
            'tour_key' => 'main_product_tour',
            'version' => 2,
            'status' => 'not_started',
            'last_step_id' => null,
            'completed_at' => null,
        ]);
    }

    public function test_skip_and_reset_endpoints_are_strictly_user_scoped(): void
    {
        // Skip
        $skipRes = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/main_product_tour/skip');
        $skipRes->assertStatus(200);

        $this->assertDatabaseHas('user_onboarding_states', [
            'user_id' => $this->user->id,
            'status' => 'skipped',
        ]);

        // Reset
        $resetRes = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/main_product_tour/reset');
        $resetRes->assertStatus(200);

        $this->assertDatabaseHas('user_onboarding_states', [
            'user_id' => $this->user->id,
            'status' => 'not_started',
            'last_step_id' => null,
            'completed_at' => null,
            'skipped_at' => null,
        ]);
    }

    public function test_multi_user_isolation(): void
    {
        $userB = User::factory()->create();

        // User A completes tour
        $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/main_product_tour/complete');

        // User B checks state -> must be not_started
        $resB = $this->actingAs($userB)
            ->getJson('/api/v1/onboarding/main_product_tour');

        $resB->assertStatus(200)
            ->assertJson([
                'state' => [
                    'user_id' => $userB->id,
                    'status' => 'not_started',
                ],
            ]);
    }
}
