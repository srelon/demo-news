<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
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

    // ── Register ──────────────────────────────────────────────────────────────

    public function test_user_can_register(): void
    {
        $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(200)
          ->assertJsonStructure(['data' => ['user']]);
    }

    public function test_register_fails_without_name(): void
    {
        $this->postJson('/api/register', [
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);
    }

    public function test_register_fails_without_email(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Test User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        $user = $this->createUser(['email' => 'existing@test.com']);

        $this->postJson('/api/register', [
            'name' => 'Another User',
            'email' => 'existing@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);
    }

    public function test_register_fails_with_short_password(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => '123',
            'password_confirmation' => '123',
        ])->assertStatus(422);
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function test_user_can_login(): void
    {
        $user = $this->createUser(['email' => 'login@test.com']);

        $this->postJson('/api/login', [
            'email' => 'login@test.com',
            'password' => 'password123',
        ])->assertStatus(200)
          ->assertJsonStructure(['data' => ['user']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->createUser(['email' => 'user@test.com']);

        $this->postJson('/api/login', [
            'email' => 'user@test.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $this->postJson('/api/login', [
            'email' => 'nobody@test.com',
            'password' => 'password123',
        ])->assertStatus(422);
    }

    public function test_login_fails_without_credentials(): void
    {
        $this->postJson('/api/login', [])->assertStatus(422);
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'web')
            ->postJson('/api/logout')
            ->assertStatus(200);
    }

    public function test_unauthenticated_cannot_logout(): void
    {
        $this->postJson('/api/logout')->assertStatus(401);
    }
}
