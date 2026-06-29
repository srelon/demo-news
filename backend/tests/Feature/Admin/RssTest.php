<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\RssItem;
use App\Models\RssSource;
use App\Models\Subcategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

class RssTest extends TestCase
{
    use RefreshDatabase, AdminTestHelper;

    private function createSubcategory(): Subcategory
    {
        $cat = Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#333', 'order' => 1]);
        return Subcategory::create(['category_id' => $cat->id, 'name' => 'Local', 'slug' => 'local', 'order' => 1]);
    }

    private function createSource(int $subcategory_id, array $overrides = []): RssSource
    {
        return RssSource::create(array_merge([
            'name' => 'Test Feed',
            'url' => 'https://example.com/feed-' . uniqid() . '.xml',
            'active' => true,
            'subcategory_id' => $subcategory_id,
        ], $overrides));
    }

    private function createItem(int $source_id, string $status = 'rejected', array $overrides = []): RssItem
    {
        return RssItem::create(array_merge([
            'rss_source_id' => $source_id,
            'guid_hash' => md5(uniqid()),
            'guid' => 'https://example.com/' . uniqid(),
            'title' => 'Test Item',
            'status' => $status,
            'reason' => 'too short',
            'reason_code' => 'body_too_short',
        ], $overrides));
    }

    // ── List sources ──────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_list_sources(): void
    {
        $this->getJson('/api/admin/rss')->assertStatus(403);
    }

    public function test_admin_without_permission_cannot_list_sources(): void
    {
        $this->actingAs($this->adminWithNoAccess(), 'admin')
            ->getJson('/api/admin/rss')
            ->assertStatus(403);
    }

    public function test_admin_can_list_sources(): void
    {
        $sub = $this->createSubcategory();
        $this->createSource($sub->id);
        $this->createSource($sub->id);

        $this->actingAs($this->adminWithView('rss'), 'admin')
            ->getJson('/api/admin/rss')
            ->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(2, 'data');
    }

    // ── Items (rejected list) ─────────────────────────────────────────────────

    public function test_unauthenticated_cannot_list_items(): void
    {
        $this->postJson('/api/admin/rss/items')->assertStatus(403);
    }

    public function test_admin_can_list_rejected_items(): void
    {
        $sub = $this->createSubcategory();
        $source = $this->createSource($sub->id);
        $this->createItem($source->id, 'rejected');
        $this->createItem($source->id, 'failed');
        $this->createItem($source->id, 'imported');
        $this->createItem($source->id, 'ignored');

        $this->actingAs($this->adminWithView('rss'), 'admin')
            ->postJson('/api/admin/rss/items', ['source_id' => $source->id])
            ->assertStatus(200)
            ->assertJsonPath('data.total', 2);
    }

    // ── Delete single item ────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_delete_item(): void
    {
        $sub = $this->createSubcategory();
        $source = $this->createSource($sub->id);
        $item = $this->createItem($source->id);

        $this->postJson("/api/admin/rss/items/delete/{$item->id}")->assertStatus(403);
    }

    public function test_view_only_cannot_delete_item(): void
    {
        $sub = $this->createSubcategory();
        $source = $this->createSource($sub->id);
        $item = $this->createItem($source->id);

        $this->actingAs($this->adminWithView('rss'), 'admin')
            ->postJson("/api/admin/rss/items/delete/{$item->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_delete_item(): void
    {
        $sub = $this->createSubcategory();
        $source = $this->createSource($sub->id);
        $item = $this->createItem($source->id);

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson("/api/admin/rss/items/delete/{$item->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseHas('rss_items', ['id' => $item->id, 'status' => 'ignored']);
    }

    // ── Delete all rejected ───────────────────────────────────────────────────

    public function test_unauthenticated_cannot_delete_rejected(): void
    {
        $this->postJson('/api/admin/rss/items/delete-rejected')->assertStatus(403);
    }

    public function test_view_only_cannot_delete_all_rejected(): void
    {
        $sub = $this->createSubcategory();
        $source = $this->createSource($sub->id);

        $this->actingAs($this->adminWithView('rss'), 'admin')
            ->postJson('/api/admin/rss/items/delete-rejected', ['source_id' => $source->id])
            ->assertStatus(403);
    }

    public function test_admin_can_delete_all_rejected(): void
    {
        $sub = $this->createSubcategory();
        $source = $this->createSource($sub->id);
        $this->createItem($source->id, 'rejected');
        $this->createItem($source->id, 'rejected');
        $this->createItem($source->id, 'failed');
        $imported = $this->createItem($source->id, 'imported');
        $ignored = $this->createItem($source->id, 'ignored');

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/rss/items/delete-rejected', ['source_id' => $source->id])
            ->assertStatus(200)
            ->assertJsonPath('data.deleted', 3);

        $this->assertDatabaseHas('rss_items', ['id' => $imported->id, 'status' => 'imported']);
        $this->assertDatabaseHas('rss_items', ['id' => $ignored->id, 'status' => 'ignored']);
        $this->assertEquals(
            0,
            RssItem::where('rss_source_id', $source->id)->whereNotIn('status', ['imported', 'ignored'])->count()
        );
    }

    public function test_delete_all_rejected_only_affects_target_source(): void
    {
        $sub = $this->createSubcategory();
        $source_a = $this->createSource($sub->id);
        $source_b = $this->createSource($sub->id);
        $this->createItem($source_a->id, 'rejected');
        $item_b = $this->createItem($source_b->id, 'rejected');

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/rss/items/delete-rejected', ['source_id' => $source_a->id])
            ->assertStatus(200)
            ->assertJsonPath('data.deleted', 1);

        $this->assertDatabaseHas('rss_items', ['id' => $item_b->id, 'status' => 'rejected']);
    }

    // ── Toggle ────────────────────────────────────────────────────────────────

    public function test_admin_can_toggle_source(): void
    {
        $sub = $this->createSubcategory();
        $source = $this->createSource($sub->id, ['active' => true]);

        $this->actingAs($this->adminWithFull('rss'), 'admin')
            ->postJson("/api/admin/rss/toggle/{$source->id}")
            ->assertStatus(200);

        $this->assertDatabaseHas('rss_sources', ['id' => $source->id, 'active' => false]);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_admin_can_create_source(): void
    {
        $sub = $this->createSubcategory();

        $this->actingAs($this->adminWithFull('rss'), 'admin')
            ->postJson('/api/admin/rss/create', [
                'name' => 'My Feed',
                'url' => 'https://example.com/feed.xml',
                'subcategory_id' => $sub->id,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('rss_sources', ['name' => 'My Feed', 'url' => 'https://example.com/feed.xml']);
    }

    public function test_create_source_fails_with_duplicate_url(): void
    {
        $sub = $this->createSubcategory();
        $this->createSource($sub->id, ['url' => 'https://example.com/feed.xml']);

        $this->actingAs($this->adminWithFull('rss'), 'admin')
            ->postJson('/api/admin/rss/create', [
                'name' => 'Another Feed',
                'url' => 'https://example.com/feed.xml',
                'subcategory_id' => $sub->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_create_source_fails_without_required_fields(): void
    {
        $this->actingAs($this->adminWithFull('rss'), 'admin')
            ->postJson('/api/admin/rss/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'url', 'subcategory_id']);
    }

    public function test_create_source_requires_edit_permission(): void
    {
        $sub = $this->createSubcategory();

        $this->actingAs($this->adminWithView('rss'), 'admin')
            ->postJson('/api/admin/rss/create', [
                'name' => 'Feed',
                'url' => 'https://example.com/feed.xml',
                'subcategory_id' => $sub->id,
            ])
            ->assertStatus(403);
    }

    // ── Delete source ─────────────────────────────────────────────────────────

    public function test_admin_can_delete_source(): void
    {
        $sub = $this->createSubcategory();
        $source = $this->createSource($sub->id);

        $this->actingAs($this->adminWithFull('rss'), 'admin')
            ->postJson("/api/admin/rss/delete/{$source->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('rss_sources', ['id' => $source->id]);
    }

    public function test_delete_source_requires_edit_permission(): void
    {
        $sub = $this->createSubcategory();
        $source = $this->createSource($sub->id);

        $this->actingAs($this->adminWithView('rss'), 'admin')
            ->postJson("/api/admin/rss/delete/{$source->id}")
            ->assertStatus(403);
    }
}
