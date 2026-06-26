<?php

namespace Tests\Feature\Admin;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

class TagsTest extends TestCase
{
    use RefreshDatabase, AdminTestHelper;

    // ── List ──────────────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_list_tags(): void
    {
        $this->postJson('/api/admin/tags')->assertStatus(403);
    }

    public function test_admin_without_permission_cannot_list_tags(): void
    {
        $this->actingAs($this->adminWithNoAccess(), 'admin')
            ->postJson('/api/admin/tags')
            ->assertStatus(403);
    }

    public function test_admin_can_list_tags(): void
    {
        Tag::create(['name' => 'php', 'slug' => 'php']);
        Tag::create(['name' => 'vue', 'slug' => 'vue']);

        $this->actingAs($this->adminWithView('tags'), 'admin')
            ->postJson('/api/admin/tags', ['per_page' => 10])
            ->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonPath('data.total', 2);
    }

    public function test_admin_can_search_tags(): void
    {
        Tag::create(['name' => 'php', 'slug' => 'php']);
        Tag::create(['name' => 'vue', 'slug' => 'vue']);
        Tag::create(['name' => 'python', 'slug' => 'python']);

        $this->actingAs($this->adminWithView('tags'), 'admin')
            ->postJson('/api/admin/tags', ['search' => 'p'])
            ->assertStatus(200)
            ->assertJsonPath('data.total', 2);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_admin_can_get_tag(): void
    {
        $tag = Tag::create(['name' => 'php', 'slug' => 'php']);

        $this->actingAs($this->adminWithView('tags'), 'admin')
            ->getJson("/api/admin/tag/{$tag->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'php');
    }

    public function test_get_tag_returns_404_when_not_found(): void
    {
        $this->actingAs($this->adminWithView('tags'), 'admin')
            ->getJson('/api/admin/tag/9999')
            ->assertStatus(404);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_admin_can_create_tag(): void
    {
        $this->actingAs($this->adminWithFull('tags'), 'admin')
            ->postJson('/api/admin/tag/create', ['name' => 'Laravel'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Laravel');

        $this->assertDatabaseHas('tags', ['name' => 'Laravel']);
    }

    public function test_create_tag_generates_slug_automatically(): void
    {
        $this->actingAs($this->adminWithFull('tags'), 'admin')
            ->postJson('/api/admin/tag/create', ['name' => 'Vue JS'])
            ->assertStatus(200);

        $this->assertDatabaseHas('tags', ['slug' => 'vue-js']);
    }

    public function test_create_tag_uses_provided_slug(): void
    {
        $this->actingAs($this->adminWithFull('tags'), 'admin')
            ->postJson('/api/admin/tag/create', ['name' => 'Vue JS', 'slug' => 'vuejs'])
            ->assertStatus(200);

        $this->assertDatabaseHas('tags', ['slug' => 'vuejs']);
    }

    public function test_create_tag_fails_without_name(): void
    {
        $this->actingAs($this->adminWithFull('tags'), 'admin')
            ->postJson('/api/admin/tag/create', ['slug' => 'no-name'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_tag_fails_with_duplicate_slug(): void
    {
        Tag::create(['name' => 'php', 'slug' => 'php']);

        $this->actingAs($this->adminWithFull('tags'), 'admin')
            ->postJson('/api/admin/tag/create', ['name' => 'PHP2', 'slug' => 'php'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_create_tag_requires_edit_permission(): void
    {
        $this->actingAs($this->adminWithView('tags'), 'admin')
            ->postJson('/api/admin/tag/create', ['name' => 'X'])
            ->assertStatus(403);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_tag(): void
    {
        $tag = Tag::create(['name' => 'old', 'slug' => 'old']);

        $this->actingAs($this->adminWithFull('tags'), 'admin')
            ->postJson("/api/admin/tag/edit/{$tag->id}", ['name' => 'Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated');

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'Updated']);
    }

    public function test_update_tag_allows_own_slug(): void
    {
        $tag = Tag::create(['name' => 'php', 'slug' => 'php']);

        $this->actingAs($this->adminWithFull('tags'), 'admin')
            ->postJson("/api/admin/tag/edit/{$tag->id}", ['name' => 'PHP Updated', 'slug' => 'php'])
            ->assertStatus(200);
    }

    public function test_update_tag_fails_without_name(): void
    {
        $tag = Tag::create(['name' => 'php', 'slug' => 'php']);

        $this->actingAs($this->adminWithFull('tags'), 'admin')
            ->postJson("/api/admin/tag/edit/{$tag->id}", ['slug' => 'php'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_tag_fails_with_duplicate_slug(): void
    {
        Tag::create(['name' => 'php', 'slug' => 'php']);
        $tag = Tag::create(['name' => 'vue', 'slug' => 'vue']);

        $this->actingAs($this->adminWithFull('tags'), 'admin')
            ->postJson("/api/admin/tag/edit/{$tag->id}", ['name' => 'PHP', 'slug' => 'php'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_update_tag_requires_edit_permission(): void
    {
        $tag = Tag::create(['name' => 'php', 'slug' => 'php']);

        $this->actingAs($this->adminWithView('tags'), 'admin')
            ->postJson("/api/admin/tag/edit/{$tag->id}", ['name' => 'Updated'])
            ->assertStatus(403);
    }
}
