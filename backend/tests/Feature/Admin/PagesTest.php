<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase, AdminTestHelper;

    private function createPage(array $overrides = []): Page
    {
        return Page::create(array_merge([
            'slug' => 'page-' . uniqid(),
            'title' => 'Test Page',
            'content' => '<p>Test content</p>',
            'active' => true,
        ], $overrides));
    }

    // ── List ──────────────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_list_pages(): void
    {
        $this->getJson('/api/admin/pages')->assertStatus(403);
    }

    public function test_admin_without_permission_cannot_list_pages(): void
    {
        $this->actingAs($this->adminWithNoAccess(), 'admin')
            ->getJson('/api/admin/pages')
            ->assertStatus(403);
    }

    public function test_admin_can_list_pages(): void
    {
        $this->createPage(['slug' => 'about', 'title' => 'About Us']);
        $this->createPage(['slug' => 'contact', 'title' => 'Contact Us']);

        $this->actingAs($this->adminWithView('articles'), 'admin')
            ->getJson('/api/admin/pages')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_list_includes_active_field(): void
    {
        $this->createPage(['slug' => 'about', 'active' => true]);
        $this->createPage(['slug' => 'contact', 'active' => false]);

        $response = $this->actingAs($this->adminWithView('articles'), 'admin')
            ->getJson('/api/admin/pages')
            ->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('active', $data[0]);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_get_page(): void
    {
        $page = $this->createPage();

        $this->getJson("/api/admin/page/{$page->id}")->assertStatus(403);
    }

    public function test_admin_can_get_page(): void
    {
        $page = $this->createPage(['slug' => 'about', 'title' => 'About Us']);

        $this->actingAs($this->adminWithView('articles'), 'admin')
            ->getJson("/api/admin/page/{$page->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'About Us')
            ->assertJsonPath('data.slug', 'about')
            ->assertJsonStructure(['data' => ['id', 'slug', 'title', 'content', 'active']]);
    }

    public function test_get_page_returns_404_when_not_found(): void
    {
        $this->actingAs($this->adminWithView('articles'), 'admin')
            ->getJson('/api/admin/page/9999')
            ->assertStatus(404);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_create_page(): void
    {
        $this->postJson('/api/admin/page/create', ['title' => 'New'])->assertStatus(403);
    }

    public function test_admin_without_permission_cannot_create_page(): void
    {
        $this->actingAs($this->adminWithView('articles'), 'admin')
            ->postJson('/api/admin/page/create', ['title' => 'New'])
            ->assertStatus(403);
    }

    public function test_admin_can_create_page(): void
    {
        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/page/create', [
                'title' => 'New Page',
                'content' => '<p>Content</p>',
                'active' => true,
            ])->assertStatus(200)
              ->assertJsonPath('data.title', 'New Page')
              ->assertJsonPath('data.active', true);

        $this->assertDatabaseHas('pages', ['title' => 'New Page', 'slug' => 'new-page']);
    }

    public function test_create_page_requires_title(): void
    {
        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/page/create', ['content' => '<p>text</p>'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_create_page_defaults_to_active(): void
    {
        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/page/create', ['title' => 'Draft Page'])
            ->assertStatus(200)
            ->assertJsonPath('data.active', true);
    }

    public function test_admin_can_create_inactive_page(): void
    {
        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/page/create', [
                'title' => 'Hidden Page',
                'active' => false,
            ])->assertStatus(200)
              ->assertJsonPath('data.active', false);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_edit_page(): void
    {
        $page = $this->createPage();

        $this->postJson("/api/admin/page/edit/{$page->id}", ['title' => 'Updated'])
            ->assertStatus(403);
    }

    public function test_admin_without_permission_cannot_edit_page(): void
    {
        $page = $this->createPage();

        $this->actingAs($this->adminWithView('articles'), 'admin')
            ->postJson("/api/admin/page/edit/{$page->id}", ['title' => 'Updated'])
            ->assertStatus(403);
    }

    public function test_admin_can_edit_page_title(): void
    {
        $page = $this->createPage(['title' => 'Old Title']);

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson("/api/admin/page/edit/{$page->id}", [
                'title' => 'New Title',
                'content' => '<p>New content</p>',
            ])->assertStatus(200)
              ->assertJsonPath('data.title', 'New Title');

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'title' => 'New Title']);
    }

    public function test_admin_can_edit_page_content(): void
    {
        $page = $this->createPage();

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson("/api/admin/page/edit/{$page->id}", [
                'title' => $page->title,
                'content' => '<p>Updated content</p>',
            ])->assertStatus(200)
              ->assertJsonPath('data.content', '<p>Updated content</p>');
    }

    public function test_admin_can_deactivate_page(): void
    {
        $page = $this->createPage(['active' => true]);

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson("/api/admin/page/edit/{$page->id}", [
                'title' => $page->title,
                'active' => false,
            ])->assertStatus(200)
              ->assertJsonPath('data.active', false);

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'active' => false]);
    }

    public function test_admin_can_activate_page(): void
    {
        $page = $this->createPage(['active' => false]);

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson("/api/admin/page/edit/{$page->id}", [
                'title' => $page->title,
                'active' => true,
            ])->assertStatus(200)
              ->assertJsonPath('data.active', true);
    }

    public function test_edit_page_requires_title(): void
    {
        $page = $this->createPage();

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson("/api/admin/page/edit/{$page->id}", ['content' => '<p>text</p>'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_edit_page_content_is_optional(): void
    {
        $page = $this->createPage();

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson("/api/admin/page/edit/{$page->id}", ['title' => 'Title Only'])
            ->assertStatus(200);
    }

    public function test_edit_page_returns_404_when_not_found(): void
    {
        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/page/edit/9999', ['title' => 'Ghost'])
            ->assertStatus(404);
    }
}
