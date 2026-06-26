<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

class CategoriesTest extends TestCase
{
    use RefreshDatabase, AdminTestHelper;

    // ── List ──────────────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_list_categories(): void
    {
        $this->postJson('/api/admin/categories')->assertStatus(403);
    }

    public function test_admin_without_permission_cannot_list_categories(): void
    {
        $this->actingAs($this->adminWithNoAccess(), 'admin')
            ->postJson('/api/admin/categories')
            ->assertStatus(403);
    }

    public function test_admin_can_list_categories(): void
    {
        Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#ff0000', 'order' => 1]);
        Category::create(['name' => 'Sport', 'slug' => 'sport', 'color' => '#00ff00', 'order' => 2]);

        $this->actingAs($this->adminWithView('categories'), 'admin')
            ->postJson('/api/admin/categories', ['per_page' => 10])
            ->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonPath('data.total', 2);
    }

    public function test_admin_can_search_categories(): void
    {
        Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#333', 'order' => 1]);
        Category::create(['name' => 'Sport', 'slug' => 'sport', 'color' => '#333', 'order' => 2]);

        $this->actingAs($this->adminWithView('categories'), 'admin')
            ->postJson('/api/admin/categories', ['search' => 'sport'])
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_admin_can_get_category(): void
    {
        $cat = Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#333', 'order' => 1]);

        $this->actingAs($this->adminWithView('categories'), 'admin')
            ->getJson("/api/admin/categories/{$cat->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $cat->id)
            ->assertJsonPath('data.name', 'News');
    }

    public function test_get_category_returns_404_when_not_found(): void
    {
        $this->actingAs($this->adminWithView('categories'), 'admin')
            ->getJson('/api/admin/categories/9999')
            ->assertStatus(404);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_admin_can_create_category(): void
    {
        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson('/api/admin/categories/create', [
                'name' => 'Entertainment',
                'slug' => 'entertainment',
                'color' => '#9b59b6',
                'order' => 3,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Entertainment');

        $this->assertDatabaseHas('categories', ['slug' => 'entertainment', 'color' => '#9b59b6']);
    }

    public function test_create_category_generates_slug_automatically(): void
    {
        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson('/api/admin/categories/create', ['name' => 'Travel & Life'])
            ->assertStatus(200);

        $this->assertDatabaseHas('categories', ['slug' => 'travel-life']);
    }

    public function test_create_category_fails_without_name(): void
    {
        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson('/api/admin/categories/create', ['color' => '#333'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_category_fails_with_duplicate_slug(): void
    {
        Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#333', 'order' => 1]);

        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson('/api/admin/categories/create', ['name' => 'News 2', 'slug' => 'news'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_create_category_requires_edit_permission(): void
    {
        $this->actingAs($this->adminWithView('categories'), 'admin')
            ->postJson('/api/admin/categories/create', ['name' => 'X'])
            ->assertStatus(403);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_category(): void
    {
        $cat = Category::create(['name' => 'Old', 'slug' => 'old', 'color' => '#111', 'order' => 0]);

        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson("/api/admin/categories/edit/{$cat->id}", [
                'name' => 'Updated',
                'color' => '#e71d69',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated');

        $this->assertDatabaseHas('categories', ['id' => $cat->id, 'name' => 'Updated', 'color' => '#e71d69']);
    }

    public function test_update_category_fails_without_name(): void
    {
        $cat = Category::create(['name' => 'Old', 'slug' => 'old', 'color' => '#111', 'order' => 0]);

        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson("/api/admin/categories/edit/{$cat->id}", ['color' => '#333'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_category_allows_own_slug(): void
    {
        $cat = Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#111', 'order' => 0]);

        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson("/api/admin/categories/edit/{$cat->id}", [
                'name' => 'News Updated',
                'slug' => 'news',
            ])
            ->assertStatus(200);
    }

    // ── Subcategory Create ────────────────────────────────────────────────────

    public function test_admin_can_create_subcategory(): void
    {
        $cat = Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#333', 'order' => 1]);

        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson("/api/admin/categories/{$cat->id}/subcategory/create", [
                'name' => 'Local News',
                'slug' => 'local-news',
                'order' => 1,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Local News');

        $this->assertDatabaseHas('subcategories', ['category_id' => $cat->id, 'slug' => 'local-news']);
    }

    public function test_create_subcategory_generates_slug_automatically(): void
    {
        $cat = Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#333', 'order' => 1]);

        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson("/api/admin/categories/{$cat->id}/subcategory/create", ['name' => 'World News'])
            ->assertStatus(200);

        $this->assertDatabaseHas('subcategories', ['category_id' => $cat->id, 'slug' => 'world-news']);
    }

    public function test_create_subcategory_fails_without_name(): void
    {
        $cat = Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#333', 'order' => 1]);

        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson("/api/admin/categories/{$cat->id}/subcategory/create", ['order' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // ── Subcategory Update ────────────────────────────────────────────────────

    public function test_admin_can_update_subcategory(): void
    {
        $cat = Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#333', 'order' => 1]);
        $sub = Subcategory::create(['category_id' => $cat->id, 'name' => 'Old Sub', 'slug' => 'old-sub', 'order' => 0]);

        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson("/api/admin/subcategory/edit/{$sub->id}", [
                'name' => 'Updated Sub',
                'slug' => 'updated-sub',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Sub');

        $this->assertDatabaseHas('subcategories', ['id' => $sub->id, 'name' => 'Updated Sub']);
    }

    // ── Subcategory Delete ────────────────────────────────────────────────────

    public function test_admin_can_delete_subcategory(): void
    {
        $cat = Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#333', 'order' => 1]);
        $sub = Subcategory::create(['category_id' => $cat->id, 'name' => 'Sub', 'slug' => 'sub', 'order' => 0]);

        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson("/api/admin/subcategory/delete/{$sub->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('subcategories', ['id' => $sub->id]);
    }

    // ── Subcategory Reorder ───────────────────────────────────────────────────

    public function test_admin_can_reorder_subcategories(): void
    {
        $cat = Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#333', 'order' => 1]);
        $sub1 = Subcategory::create(['category_id' => $cat->id, 'name' => 'First',  'slug' => 'first',  'order' => 0]);
        $sub2 = Subcategory::create(['category_id' => $cat->id, 'name' => 'Second', 'slug' => 'second', 'order' => 1]);

        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson('/api/admin/subcategory/reorder', [
                'items' => [
                    ['id' => $sub1->id, 'order' => 1],
                    ['id' => $sub2->id, 'order' => 0],
                ],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('subcategories', ['id' => $sub1->id, 'order' => 1]);
        $this->assertDatabaseHas('subcategories', ['id' => $sub2->id, 'order' => 0]);
    }

    public function test_reorder_fails_with_invalid_subcategory_id(): void
    {
        $this->actingAs($this->adminWithFull('categories'), 'admin')
            ->postJson('/api/admin/subcategory/reorder', [
                'items' => [
                    ['id' => 9999, 'order' => 0],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.id']);
    }

    public function test_reorder_requires_edit_permission(): void
    {
        $cat = Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#333', 'order' => 1]);
        $sub = Subcategory::create(['category_id' => $cat->id, 'name' => 'Sub', 'slug' => 'sub', 'order' => 0]);

        $this->actingAs($this->adminWithView('categories'), 'admin')
            ->postJson('/api/admin/subcategory/reorder', [
                'items' => [['id' => $sub->id, 'order' => 0]],
            ])
            ->assertStatus(403);
    }
}
