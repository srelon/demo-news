<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Article;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;

class LayoutService
{
    public function getData(): array
    {
        $data = Cache::tags([CacheService::TAG_LAYOUT])->remember(
            'layout.data',
            CacheService::TTL_LAYOUT,
            fn() => [
                'nav' => $this->buildNav(),
                'categories' => $this->buildCategories(),
                'tags' => Tag::whereHas('articles', fn($q) => $q->published())
                    ->get(['id', 'name', 'slug']),
                'footer' => $this->buildFooter(),
                'sidebar' => $this->buildSidebar(),
            ]
        );

        return $data;
    }

    private function buildNav(): array
    {
        $categories = Category::with([
            'subcategories' => fn($q) => $q
                ->whereHas('articles', fn($q) => $q->published())
                ->select('id', 'category_id', 'name', 'slug'),
        ])
            ->whereHas('subcategories.articles', fn($q) => $q->published())
            ->orderBy('order')
            ->get(['id', 'name', 'slug', 'color']);

        return $categories->map(function ($cat) {
            return [
                'id'    => $cat->id,
                'label' => $cat->name,
                'slug'  => $cat->slug,
                'color' => $cat->color,
                'tabs'  => $cat->subcategories->map(function ($sub) {
                    $posts = Article::published()
                        ->where('subcategory_id', $sub->id)
                        ->with('subcategory.category')
                        ->latest('published_at')
                        ->limit(4)
                        ->get();

                    return [
                        'id'    => $sub->id,
                        'label' => $sub->name,
                        'slug'  => $sub->slug,
                        'posts' => $posts->map(fn($a) => [
                            'title'            => $a->title,
                            'page_slug'        => $a->slug,
                            'image'            => $a->image,
                            'date'             => $a->published_at?->toDateString(),
                            'category_name'    => $a->subcategory->category->name,
                            'category_slug'    => $a->subcategory->category->slug,
                            'subcategory_slug' => $a->subcategory->slug,
                        ])->values(),
                    ];
                })->values(),
            ];
        })->toArray();
    }

    private function buildSidebar(): array
    {
        $isSqlite = \DB::getDriverName() === 'sqlite';

        $yearExpr = $isSqlite ? "strftime('%Y', published_at)" : 'YEAR(published_at)';
        $monthExpr = $isSqlite ? "strftime('%m', published_at)" : 'MONTH(published_at)';

        $archive = Article::published()
            ->selectRaw("{$yearExpr} as year, {$monthExpr} as month, COUNT(*) as count")
            ->groupByRaw("{$yearExpr}, {$monthExpr}")
            ->orderByRaw("{$yearExpr} DESC, {$monthExpr} DESC")
            ->get()
            ->map(fn($row) => [
                'year'  => (int) $row->year,
                'month' => (int) $row->month,
                'label' => date('F Y', mktime(0, 0, 0, (int) $row->month, 1, (int) $row->year)),
                'count' => $row->count,
            ])
            ->toArray();

        return ['archive' => $archive];
    }

    private function buildCategories(): array
    {
        return Category::with('subcategories:id,category_id,name,slug')
            ->orderBy('order')
            ->get(['id', 'name', 'slug', 'color'])
            ->toArray();
    }

    private function buildFooter(): array
    {
        $popular = Article::published()
            ->with('subcategory.category')
            ->orderByDesc('views')
            ->limit(6)
            ->get();

        return [
            'popular_posts' => $popular->map(fn($a) => [
                'title' => $a->title,
                'slug' => $a->slug,
                'image' => $a->image,
                'date' => $a->published_at?->toDateString(),
                'category_name' => $a->subcategory->category->name,
                'category_slug' => $a->subcategory->category->slug,
                'subcategory_slug' => $a->subcategory->slug,
            ]),
        ];
    }
}
