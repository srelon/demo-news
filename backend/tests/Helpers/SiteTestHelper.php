<?php

namespace Tests\Helpers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Subcategory;

trait SiteTestHelper
{
    protected function createPublishedArticle(array $overrides = []): Article
    {
        $cat = Category::create([
            'name' => 'Test Cat ' . uniqid(),
            'slug' => 'test-cat-' . uniqid(),
            'color' => '#333333',
            'order' => 1,
        ]);

        $sub = Subcategory::create([
            'category_id' => $cat->id,
            'name' => 'Test Sub ' . uniqid(),
            'slug' => 'test-sub-' . uniqid(),
            'order' => 1,
        ]);

        return Article::create(array_merge([
            'subcategory_id' => $sub->id,
            'title' => 'Test Article',
            'slug' => 'test-article-' . uniqid(),
            'body' => '<p>Test body content.</p>',
            'excerpt' => 'Test excerpt.',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ], $overrides));
    }

    protected function getArticleCategory(Article $article): Category
    {
        return $article->subcategory->category;
    }

    protected function getArticleSubcategory(Article $article): Subcategory
    {
        return $article->subcategory;
    }
}
