<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\FeaturedBlock;
use Illuminate\Support\Facades\Cache;

class HomeService
{
    public function getData(): array
    {
        return Cache::tags([CacheService::TAG_HOME, CacheService::TAG_ARTICLES])->remember(
            'home.data',
            CacheService::TTL_HOME,
            fn() => [
                'featured_block' => $this->buildFeaturedBlock('home'),
                'category_tabs' => $this->buildCategoryTabs(),
            ]
        );
    }

    public function buildFeaturedBlock(string $context): ?array
    {
        return Cache::tags([CacheService::TAG_HOME, CacheService::TAG_ARTICLES])->remember(
            "featured_block.{$context}",
            CacheService::TTL_HOME,
            function () use ($context) {
                $block = FeaturedBlock::where('context', $context)
                    ->with([
                        'featured.subcategory.category',
                        'topRight.subcategory.category',
                        'bottomLeft.subcategory.category',
                        'bottomRight.subcategory.category',
                    ])
                    ->first();

                if (!$block) {
                    return null;
                }

                return [
                    'featured' => $this->mapBlockArticle($block->featured),
                    'top_right' => $this->mapBlockArticle($block->topRight),
                    'bottom_left' => $this->mapBlockArticle($block->bottomLeft),
                    'bottom_right' => $this->mapBlockArticle($block->bottomRight),
                ];
            }
        );
    }

    private function mapBlockArticle(?Article $article): ?array
    {
        if (!$article || $article->status !== 'published' || !$article->published_at) {
            return null;
        }

        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'image' => $article->image,
            'date' => $article->published_at?->toDateString(),
            'category_name' => $article->subcategory->category->name,
            'category_slug' => $article->subcategory->category->slug,
            'subcategory_slug' => $article->subcategory->slug,
            'page_slug' => $article->slug,
        ];
    }

    private function buildCategoryTabs(): array
    {
        $categories = Category::with('subcategories:id,category_id,name,slug')
            ->whereHas('subcategories', fn($q) => $q->whereHas('articles', fn($q) => $q->published()))
            ->orderBy('order')
            ->get(['id', 'name', 'slug', 'color']);

        return $categories->map(function ($category) {
            $articles = Article::published()
                ->whereHas('subcategory', fn($q) => $q->where('category_id', $category->id))
                ->with(['author:id,name', 'subcategory.category'])
                ->latest('published_at')
                ->limit(4)
                ->get();

            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'color' => $category->color,
                'subcategories' => $category->subcategories->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'slug' => $s->slug,
                ]),
                'articles' => $articles->map(fn($a) => [
                    'id' => $a->id,
                    'title' => $a->title,
                    'slug' => $a->slug,
                    'image' => $a->image,
                    'date' => $a->published_at?->toDateString(),
                    'author' => $a->author?->name,
                    'category_name' => $a->subcategory->category->name,
                    'category_slug' => $a->subcategory->category->slug,
                    'subcategory_slug' => $a->subcategory->slug,
                    'page_slug' => $a->slug,
                ]),
            ];
        })->toArray();
    }
}
