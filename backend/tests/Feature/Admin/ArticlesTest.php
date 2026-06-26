<?php

namespace Tests\Feature\Admin;

use App\Models\Article;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

class ArticlesTest extends TestCase
{
    use RefreshDatabase, AdminTestHelper;

    private function createSubcategory(): Subcategory
    {
        $cat = Category::create(['name' => 'News', 'slug' => 'news', 'color' => '#333', 'order' => 1]);
        return Subcategory::create(['category_id' => $cat->id, 'name' => 'Local', 'slug' => 'local', 'order' => 1]);
    }

    private function createArticle(int $subcategory_id, array $overrides = []): Article
    {
        return Article::create(array_merge([
            'subcategory_id' => $subcategory_id,
            'title' => 'Test Article',
            'slug' => 'test-article-' . uniqid(),
            'body' => 'Body text.',
            'status' => 'draft',
        ], $overrides));
    }

    // ── List ──────────────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_list_articles(): void
    {
        $this->postJson('/api/admin/articles')->assertStatus(403);
    }

    public function test_admin_without_permission_cannot_list_articles(): void
    {
        $this->actingAs($this->adminWithNoAccess(), 'admin')
            ->postJson('/api/admin/articles')
            ->assertStatus(403);
    }

    public function test_admin_can_list_articles(): void
    {
        $sub = $this->createSubcategory();
        $this->createArticle($sub->id);
        $this->createArticle($sub->id);

        $this->actingAs($this->adminWithView('articles'), 'admin')
            ->postJson('/api/admin/articles', ['per_page' => 10])
            ->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonPath('data.total', 2);
    }

    public function test_admin_can_search_articles(): void
    {
        $sub = $this->createSubcategory();
        Article::create(['subcategory_id' => $sub->id, 'title' => 'Breaking News', 'slug' => 'breaking-news', 'body' => 'Body.', 'status' => 'draft']);
        Article::create(['subcategory_id' => $sub->id, 'title' => 'Sports Update', 'slug' => 'sports-update', 'body' => 'Body.', 'status' => 'draft']);

        $this->actingAs($this->adminWithView('articles'), 'admin')
            ->postJson('/api/admin/articles', ['search' => 'breaking'])
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_admin_can_get_article(): void
    {
        $sub = $this->createSubcategory();
        $article = $this->createArticle($sub->id);

        $this->actingAs($this->adminWithView('articles'), 'admin')
            ->getJson("/api/admin/article/{$article->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['article', 'tags', 'subcategory_options']]);
    }

    public function test_get_article_returns_tags_as_comma_string(): void
    {
        $sub = $this->createSubcategory();
        $article = $this->createArticle($sub->id);
        $article->tags()->attach(Tag::create(['name' => 'php', 'slug' => 'php']));
        $article->tags()->attach(Tag::create(['name' => 'laravel', 'slug' => 'laravel']));

        $response = $this->actingAs($this->adminWithView('articles'), 'admin')
            ->getJson("/api/admin/article/{$article->id}")
            ->assertStatus(200);

        $tags = $response->json('data.tags');
        $this->assertStringContainsString('php', $tags);
        $this->assertStringContainsString('laravel', $tags);
    }

    public function test_get_article_returns_404_when_not_found(): void
    {
        $this->actingAs($this->adminWithView('articles'), 'admin')
            ->getJson('/api/admin/article/9999')
            ->assertStatus(404);
    }

    // ── Subcategory options ───────────────────────────────────────────────────

    public function test_admin_can_get_subcategory_options(): void
    {
        $this->createSubcategory();

        $this->actingAs($this->adminWithView('articles'), 'admin')
            ->getJson('/api/admin/articles/subcategory-options')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_admin_can_create_article(): void
    {
        $sub = $this->createSubcategory();

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/article/create', [
                'title' => 'My First Article',
                'subcategory_id' => $sub->id,
                'status' => 'draft',
                'body' => 'Article body.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'My First Article');

        $this->assertDatabaseHas('articles', ['title' => 'My First Article', 'subcategory_id' => $sub->id]);
    }

    public function test_create_article_generates_slug_automatically(): void
    {
        $sub = $this->createSubcategory();

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/article/create', [
                'title' => 'Hello World',
                'subcategory_id' => $sub->id,
                'status' => 'draft',
                'body' => 'Article body.',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('articles', ['slug' => 'hello-world']);
    }

    public function test_create_article_syncs_tags(): void
    {
        $sub = $this->createSubcategory();

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/article/create', [
                'title' => 'Tagged Article',
                'subcategory_id' => $sub->id,
                'status' => 'draft',
                'body' => 'Article body.',
                'tags' => 'php, laravel, vue',
            ])
            ->assertStatus(200);

        $article = Article::where('title', 'Tagged Article')->first();
        $this->assertCount(3, $article->tags);
        $this->assertDatabaseHas('tags', ['name' => 'php']);
        $this->assertDatabaseHas('tags', ['name' => 'laravel']);
        $this->assertDatabaseHas('tags', ['name' => 'vue']);
    }

    public function test_create_article_fails_without_title(): void
    {
        $sub = $this->createSubcategory();

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/article/create', [
                'subcategory_id' => $sub->id,
                'status' => 'draft',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_create_article_fails_without_subcategory(): void
    {
        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/article/create', [
                'title' => 'No Sub',
                'status' => 'draft',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subcategory_id']);
    }

    public function test_create_article_fails_with_invalid_subcategory(): void
    {
        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/article/create', [
                'title' => 'No Sub',
                'subcategory_id' => 9999,
                'status' => 'draft',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subcategory_id']);
    }

    public function test_create_article_fails_with_invalid_status(): void
    {
        $sub = $this->createSubcategory();

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson('/api/admin/article/create', [
                'title' => 'Bad Status',
                'subcategory_id' => $sub->id,
                'status' => 'pending',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_create_article_requires_edit_permission(): void
    {
        $sub = $this->createSubcategory();

        $this->actingAs($this->adminWithView('articles'), 'admin')
            ->postJson('/api/admin/article/create', [
                'title' => 'X',
                'subcategory_id' => $sub->id,
                'status' => 'draft',
            ])
            ->assertStatus(403);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_admin_can_update_article(): void
    {
        $sub = $this->createSubcategory();
        $article = $this->createArticle($sub->id);

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson("/api/admin/article/edit/{$article->id}", [
                'title' => 'Updated Title',
                'subcategory_id' => $sub->id,
                'status' => 'published',
                'body' => 'Updated body.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.article.title', 'Updated Title');

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'title' => 'Updated Title', 'status' => 'published']);
    }

    public function test_update_article_allows_own_slug(): void
    {
        $sub = $this->createSubcategory();
        $article = $this->createArticle($sub->id);

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson("/api/admin/article/edit/{$article->id}", [
                'title' => 'Updated',
                'slug' => $article->slug,
                'subcategory_id' => $sub->id,
                'status' => 'draft',
                'body' => 'Body.',
            ])
            ->assertStatus(200);
    }

    public function test_update_article_syncs_tags(): void
    {
        $sub = $this->createSubcategory();
        $article = $this->createArticle($sub->id);
        $article->tags()->attach(Tag::create(['name' => 'old-tag', 'slug' => 'old-tag']));

        $this->actingAs($this->adminWithFull('articles'), 'admin')
            ->postJson("/api/admin/article/edit/{$article->id}", [
                'title' => 'Test Article',
                'subcategory_id' => $sub->id,
                'status' => 'draft',
                'body' => 'Body.',
                'tags' => 'new-tag, another-tag',
            ])
            ->assertStatus(200);

        $article->refresh();
        $this->assertCount(2, $article->tags);
        $this->assertFalse($article->tags->pluck('name')->contains('old-tag'));
    }
}
