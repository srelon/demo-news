<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Subcategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ArticleService
{
    public function getBySubcategory(string $slug, int $limit = 4): Collection
    {
        $key = "subcategory.articles.{$slug}.limit.{$limit}";

        return Cache::tags([CacheService::TAG_ARTICLES])->remember(
            $key,
            CacheService::TTL_LIST,
            function () use ($slug, $limit) {
                $subcategory = Subcategory::where('slug', $slug)->firstOrFail();

                return Article::published()
                    ->where('subcategory_id', $subcategory->id)
                    ->with(['author:id,name', 'subcategory.category'])
                    ->latest('published_at')
                    ->limit($limit)
                    ->get();
            }
        );
    }

    public function getPaginatedBySubcategory(string $slug, int $perPage = 12): LengthAwarePaginator
    {
        $page = request()->integer('page', 1);
        $key = "subcategory.paginated.{$slug}.{$page}.{$perPage}";

        return Cache::tags([CacheService::TAG_ARTICLES, CacheService::TAG_CATEGORIES])->remember(
            $key,
            CacheService::TTL_LIST,
            function () use ($slug, $perPage) {
                $subcategory = Subcategory::where('slug', $slug)->firstOrFail();

                return Article::published()
                    ->where('subcategory_id', $subcategory->id)
                    ->with(['author:id,name', 'subcategory.category'])
                    ->latest('published_at')
                    ->paginate($perPage);
            }
        );
    }

    public function getPaginatedByCategory(string $categorySlug, int $perPage = 12): LengthAwarePaginator
    {
        $page = request()->integer('page', 1);
        $key = "category.paginated.{$categorySlug}.{$page}.{$perPage}";

        return Cache::tags([CacheService::TAG_ARTICLES, CacheService::TAG_CATEGORIES])->remember(
            $key,
            CacheService::TTL_LIST,
            fn() => Article::published()
                ->whereHas('subcategory', fn($q) => $q->whereHas(
                    'category', fn($q) => $q->where('slug', $categorySlug)
                ))
                ->with(['author:id,name', 'subcategory.category'])
                ->latest('published_at')
                ->paginate($perPage)
        );
    }

    public function findBySlug(string $slug): Article
    {
        return Cache::tags([CacheService::TAG_ARTICLES])->remember(
            "article.{$slug}",
            CacheService::TTL_ARTICLE,
            fn() => Article::published()
                ->where('slug', $slug)
                ->with(['author:id,name,img', 'subcategory.category', 'tags:id,name,slug'])
                ->withCount('comments')
                ->firstOrFail()
        );
    }

    public function getPaginatedByTag(string $slug, int $perPage = 12): LengthAwarePaginator
    {
        $page = request()->integer('page', 1);
        $key = "tag.paginated.{$slug}.{$page}.{$perPage}";

        return Cache::tags([CacheService::TAG_ARTICLES])->remember(
            $key,
            CacheService::TTL_LIST,
            fn() => Article::published()
                ->whereHas('tags', fn($q) => $q->where('slug', $slug))
                ->with(['author:id,name', 'subcategory.category'])
                ->latest('published_at')
                ->paginate($perPage)
        );
    }

    public function getPaginatedByArchive(int $year, int $month, int $perPage = 12): LengthAwarePaginator
    {
        $page = request()->integer('page', 1);
        $key = "archive.paginated.{$year}.{$month}.{$page}.{$perPage}";

        return Cache::tags([CacheService::TAG_ARTICLES])->remember(
            $key,
            CacheService::TTL_LIST,
            fn() => Article::published()
                ->whereYear('published_at', $year)
                ->whereMonth('published_at', $month)
                ->with(['author:id,name', 'subcategory.category'])
                ->latest('published_at')
                ->paginate($perPage)
        );
    }

    public function searchPaginated(string $query, int $perPage = 12): LengthAwarePaginator
    {
        return Article::published()
            ->where(fn($q) => $q
                ->where('title', 'LIKE', "%{$query}%")
                ->orWhere('excerpt', 'LIKE', "%{$query}%")
            )
            ->with(['author:id,name', 'subcategory.category'])
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function incrementViews(Article $article, string $ip): void
    {
        $inserted = ArticleView::insertOrIgnore([
            'article_id' => $article->id,
            'ip' => $ip,
        ]);

        if (!$inserted) {
            return;
        }

        $article->increment('views');

        $key = "article.{$article->slug}";
        $cached = Cache::tags([CacheService::TAG_ARTICLES])->get($key);

        if ($cached) {
            $cached->views = $article->views;
            Cache::tags([CacheService::TAG_ARTICLES])->put($key, $cached, CacheService::TTL_ARTICLE);
        }
    }
}
