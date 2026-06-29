<?php

namespace Tests\Feature\Site;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\SiteTestHelper;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase, SiteTestHelper;

    // ── Category ──────────────────────────────────────────────────────────────

    public function test_category_page_returns_successful_response(): void
    {
        $article = $this->createPublishedArticle();
        $cat = $this->getArticleCategory($article);

        $this->getJson("/api/news/{$cat->slug}")
            ->assertStatus(200);
    }

    public function test_category_page_returns_articles(): void
    {
        $article = $this->createPublishedArticle();
        $cat = $this->getArticleCategory($article);

        $this->getJson("/api/news/{$cat->slug}")
            ->assertStatus(200)
            ->assertJsonPath('data.articles.data.0.title', $article->title);
    }

    public function test_category_page_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/news/non-existent-category')
            ->assertStatus(404);
    }

    public function test_category_does_not_show_draft_articles(): void
    {
        $article = $this->createPublishedArticle(['status' => 'draft', 'published_at' => null]);
        $cat = $this->getArticleCategory($article);

        $response = $this->getJson("/api/news/{$cat->slug}")->assertStatus(200);

        $this->assertEmpty($response->json('data.articles.data'));
    }

    // ── Subcategory ───────────────────────────────────────────────────────────

    public function test_subcategory_articles_returns_successful_response(): void
    {
        $article = $this->createPublishedArticle();
        $cat = $this->getArticleCategory($article);
        $sub = $this->getArticleSubcategory($article);

        $this->getJson("/api/news/{$cat->slug}/{$sub->slug}/articles")
            ->assertStatus(200);
    }

    public function test_subcategory_articles_returns_paginated_data(): void
    {
        $article = $this->createPublishedArticle();
        $cat = $this->getArticleCategory($article);
        $sub = $this->getArticleSubcategory($article);

        $this->getJson("/api/news/{$cat->slug}/{$sub->slug}/articles")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['data', 'pagination' => ['current_page', 'last_page']]]);
    }

    // ── Article ───────────────────────────────────────────────────────────────

    public function test_article_page_returns_successful_response(): void
    {
        $article = $this->createPublishedArticle();
        $cat = $this->getArticleCategory($article);
        $sub = $this->getArticleSubcategory($article);

        $this->getJson("/api/news/{$cat->slug}/{$sub->slug}/{$article->slug}")
            ->assertStatus(200);
    }

    public function test_article_page_returns_correct_data(): void
    {
        $article = $this->createPublishedArticle();
        $cat = $this->getArticleCategory($article);
        $sub = $this->getArticleSubcategory($article);

        $this->getJson("/api/news/{$cat->slug}/{$sub->slug}/{$article->slug}")
            ->assertStatus(200)
            ->assertJsonPath('data.article.title', $article->title)
            ->assertJsonPath('data.article.slug', $article->slug);
    }

    public function test_article_page_returns_sidebar(): void
    {
        $article = $this->createPublishedArticle();
        $cat = $this->getArticleCategory($article);
        $sub = $this->getArticleSubcategory($article);

        $this->getJson("/api/news/{$cat->slug}/{$sub->slug}/{$article->slug}")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['article']]);
    }

    public function test_article_page_increments_views(): void
    {
        $article = $this->createPublishedArticle(['views' => 0]);
        $cat = $this->getArticleCategory($article);
        $sub = $this->getArticleSubcategory($article);

        $this->getJson("/api/news/{$cat->slug}/{$sub->slug}/{$article->slug}")
            ->assertStatus(200);

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'views' => 1]);
    }

    public function test_article_page_returns_404_for_unknown_slug(): void
    {
        $article = $this->createPublishedArticle();
        $cat = $this->getArticleCategory($article);
        $sub = $this->getArticleSubcategory($article);

        $this->getJson("/api/news/{$cat->slug}/{$sub->slug}/non-existent-slug")
            ->assertStatus(404);
    }
}
