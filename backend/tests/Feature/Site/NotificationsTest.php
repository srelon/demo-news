<?php

namespace Tests\Feature\Site;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationsTest extends TestCase
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

    private function createNotification(User $user, array $overrides = []): UserNotification
    {
        $notif = UserNotification::create([
            'user_id' => $user->id,
            'type' => 'reply',
            'data' => ['from_name' => 'Someone', 'comment_preview' => 'Hello'],
        ]);

        // read_at is not in $fillable — set it via forceFill after creation
        if (isset($overrides['read_at'])) {
            $notif->forceFill(['read_at' => $overrides['read_at']])->save();
        }

        return $notif->fresh();
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_notifications(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
    }

    public function test_guest_cannot_access_all_notifications(): void
    {
        $this->getJson('/api/notifications/all')->assertStatus(401);
    }

    public function test_guest_cannot_get_unread_count(): void
    {
        $this->getJson('/api/notifications/unread-count')->assertStatus(401);
    }

    // ── GET /api/notifications ────────────────────────────────────────────────

    public function test_index_returns_notifications_and_unread_count(): void
    {
        $user = $this->createUser();
        $this->createNotification($user);
        $this->createNotification($user);

        $this->actingAs($user, 'web')
            ->getJson('/api/notifications')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['notifications', 'unread_count']]);
    }

    public function test_index_marks_top_notifications_as_read(): void
    {
        $user = $this->createUser();
        $notif = $this->createNotification($user);

        $this->assertNull($notif->fresh()->read_at);

        $this->actingAs($user, 'web')
            ->getJson('/api/notifications')
            ->assertStatus(200);

        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_index_unread_count_reflects_only_outside_top10(): void
    {
        $user = $this->createUser();

        // Create 12 unread notifications — only 10 get fetched and marked
        for ($i = 0; $i < 12; $i++) {
            $this->createNotification($user);
        }

        $response = $this->actingAs($user, 'web')
            ->getJson('/api/notifications')
            ->assertStatus(200);

        // 10 in list are marked read, 2 remain unread → count = 2
        $this->assertEquals(2, $response->json('data.unread_count'));
    }

    public function test_index_returns_only_own_notifications(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();

        $this->createNotification($user);
        $this->createNotification($other);

        $response = $this->actingAs($user, 'web')
            ->getJson('/api/notifications')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data.notifications'));
    }

    // ── GET /api/notifications/all ────────────────────────────────────────────

    public function test_all_returns_paginated_notifications(): void
    {
        $user = $this->createUser();

        for ($i = 0; $i < 5; $i++) {
            $this->createNotification($user);
        }

        $response = $this->actingAs($user, 'web')
            ->getJson('/api/notifications/all')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['notifications', 'pagination']]);

        $this->assertEquals(5, $response->json('data.pagination.total'));
    }

    public function test_all_returns_only_own_notifications(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();

        for ($i = 0; $i < 3; $i++) {
            $this->createNotification($user);
        }
        $this->createNotification($other);

        $response = $this->actingAs($user, 'web')
            ->getJson('/api/notifications/all')
            ->assertStatus(200);

        $this->assertEquals(3, $response->json('data.pagination.total'));
    }

    // ── GET /api/notifications/unread-count ───────────────────────────────────

    public function test_unread_count_returns_number_of_unread(): void
    {
        $user = $this->createUser();

        $this->createNotification($user);
        $this->createNotification($user);
        $this->createNotification($user, ['read_at' => now()]);

        $response = $this->actingAs($user, 'web')
            ->getJson('/api/notifications/unread-count')
            ->assertStatus(200);

        $this->assertEquals(2, $response->json('data.count'));
    }

    public function test_unread_count_is_zero_when_all_read(): void
    {
        $user = $this->createUser();
        $this->createNotification($user, ['read_at' => now()]);

        $response = $this->actingAs($user, 'web')
            ->getJson('/api/notifications/unread-count')
            ->assertStatus(200);

        $this->assertEquals(0, $response->json('data.count'));
    }

    // ── PATCH /api/notifications/{id}/read ────────────────────────────────────

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = $this->createUser();
        $notif = $this->createNotification($user);

        $this->assertNull($notif->fresh()->read_at);

        $this->actingAs($user, 'web')
            ->patchJson("/api/notifications/{$notif->id}/read")
            ->assertStatus(200)
            ->assertJsonPath('data.ok', true);

        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_mark_read_is_idempotent(): void
    {
        $user = $this->createUser();
        $notif = $this->createNotification($user, ['read_at' => now()]);

        $this->actingAs($user, 'web')
            ->patchJson("/api/notifications/{$notif->id}/read")
            ->assertStatus(200);

        // Still read — no error, no duplicate update
        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $notif = $this->createNotification($owner);

        $this->actingAs($other, 'web')
            ->patchJson("/api/notifications/{$notif->id}/read")
            ->assertStatus(200); // No error, but record is not changed

        // Owner's notification remains unread
        $this->assertNull($notif->fresh()->read_at);
    }

    public function test_guest_cannot_mark_notification_as_read(): void
    {
        $user = $this->createUser();
        $notif = $this->createNotification($user);

        $this->patchJson("/api/notifications/{$notif->id}/read")
            ->assertStatus(401);
    }
}
