<?php

namespace Tests\Feature\Core;

use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_profile_name_and_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/user/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Profile updated successfully.')
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.email', 'updated@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_updating_profile_rejects_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/user/profile', [
            'name' => 'User Name',
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-secret-password'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/user/password', [
            'current_password' => 'old-secret-password',
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password updated successfully.');

        $user->refresh();
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
    }

    public function test_password_change_fails_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('real-password'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/user/password', [
            'current_password' => 'wrong-password',
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_public_settings_endpoint_can_be_accessed_without_auth(): void
    {
        $response = $this->getJson('/api/v1/settings');

        $response->assertOk()
            ->assertJsonStructure([
                'center' => ['name', 'bundesland', 'bundesland_name', 'logo_url'],
                'available_bundeslaender',
                'permissions' => ['can_manage'],
            ]);
    }
}
