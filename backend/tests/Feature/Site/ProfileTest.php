<?php

namespace Tests\Feature\Site;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'public_id' => 'usr_' . uniqid(),
            'name' => 'Test User',
            'email' => 'user_' . uniqid() . '@test.com',
            'password' => Hash::make('password123'),
        ], $overrides));
    }

    // ── Get profile ───────────────────────────────────────────────────────────

    public function test_guest_profile_returns_null_user(): void
    {
        $this->getJson('/api/profile')
            ->assertStatus(200)
            ->assertJsonPath('data.user', null);
    }

    public function test_authenticated_user_gets_profile(): void
    {
        $user = $this->createUser(['name' => 'Profile User']);

        $this->actingAs($user, 'web')
            ->getJson('/api/profile')
            ->assertStatus(200)
            ->assertJsonPath('data.user.name', 'Profile User');
    }

    // ── Update profile ────────────────────────────────────────────────────────

    public function test_user_can_update_name(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'web')
            ->postJson('/api/profile/update', ['name' => 'New Name'])
            ->assertStatus(200)
            ->assertJsonPath('data.user.name', 'New Name');

        $this->assertDatabaseHas('users', ['public_id' => $user->public_id, 'name' => 'New Name']);
    }

    public function test_profile_update_requires_name(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'web')
            ->postJson('/api/profile/update', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_cannot_update_profile(): void
    {
        $this->postJson('/api/profile/update', ['name' => 'Hacker'])
            ->assertStatus(401);
    }

    // ── Change password ───────────────────────────────────────────────────────

    public function test_user_can_change_password(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'web')
            ->postJson('/api/profile/password', [
                'old_password' => 'password123',
                'new_password' => 'newpassword456',
            ])->assertStatus(200);

        $this->assertTrue(Hash::check('newpassword456', $user->fresh()->password));
    }

    public function test_password_change_fails_with_wrong_old_password(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'web')
            ->postJson('/api/profile/password', [
                'old_password' => 'wrongpassword',
                'new_password' => 'newpassword456',
            ])->assertStatus(422)
              ->assertJsonValidationErrors(['old_password']);
    }

    public function test_password_change_requires_new_password(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'web')
            ->postJson('/api/profile/password', [
                'old_password' => 'password123',
            ])->assertStatus(422)
              ->assertJsonValidationErrors(['new_password']);
    }

    public function test_password_change_fails_if_too_short(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'web')
            ->postJson('/api/profile/password', [
                'old_password' => 'password123',
                'new_password' => '123',
            ])->assertStatus(422)
              ->assertJsonValidationErrors(['new_password']);
    }

    public function test_unauthenticated_cannot_change_password(): void
    {
        $this->postJson('/api/profile/password', [
            'old_password' => 'password123',
            'new_password' => 'newpassword456',
        ])->assertStatus(401);
    }
}
