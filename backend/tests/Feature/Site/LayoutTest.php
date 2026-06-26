<?php

namespace Tests\Feature\Site;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheService;
use Tests\Helpers\SiteTestHelper;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase, SiteTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::tags([CacheService::TAG_LAYOUT])->flush();
    }

    public function test_layout_returns_successful_response(): void
    {
        $this->getJson('/api/layout')
            ->assertStatus(200);
    }

    public function test_layout_returns_nav_structure(): void
    {
        $this->getJson('/api/layout')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['nav', 'categories', 'tags']]);
    }

    public function test_layout_includes_categories_with_subcategories(): void
    {
        $article = $this->createPublishedArticle();
        $cat = $this->getArticleCategory($article);

        $this->getJson('/api/layout')
            ->assertStatus(200)
            ->assertJsonFragment(['slug' => $cat->slug]);
    }
}
