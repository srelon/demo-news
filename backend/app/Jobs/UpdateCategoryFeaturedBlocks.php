<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\FeaturedBlock;
use Illuminate\Support\Facades\DB;

class UpdateCategoryFeaturedBlocks
{

    public function handle(): void
    {
        $categories = Category::withoutTrashed()->get();

        foreach ($categories as $category) {
            $this->processCategory($category);
        }

        $this->processHome();
    }

    private function processCategory(Category $category): void
    {
        $articles = $this->topArticles(
            fn($q) => $q->where('subcategories.category_id', $category->id)
        );

        $this->upsertBlock('category:' . $category->slug, $articles);
    }

    private function processHome(): void
    {
        $articles = $this->topArticles();

        $this->upsertBlock('home', $articles);
    }

    private function topArticles(?callable $filter = null): array
    {
        $since = now()->subHours(24);

        $recent_comments = DB::table('comments')
            ->select('article_id', DB::raw('COUNT(*) as comment_count'))
            ->where('created_at', '>=', $since)
            ->where(function ($q) {
                $q->where('status', '!=', 2)
                    ->orWhere('deleted_by', 1);
            })
            ->whereNull('deleted_at')
            ->groupBy('article_id');

        $query = DB::table('articles')
            ->join('subcategories', 'articles.subcategory_id', '=', 'subcategories.id')
            ->leftJoinSub($recent_comments, 'rc', 'rc.article_id', '=', 'articles.id')
            ->where('articles.status', 'published')
            ->whereNotNull('articles.published_at')
            ->where('articles.published_at', '<=', now())
            ->whereNull('articles.deleted_at')
            ->select('articles.id', DB::raw('COALESCE(rc.comment_count, 0) as comment_count'))
            ->orderByDesc('comment_count')
            ->orderByDesc('articles.views')
            ->orderByDesc('articles.published_at')
            ->limit(4);

        if ($filter) {
            $filter($query);
        }

        return $query->pluck('articles.id')->toArray();
    }

    private function upsertBlock(string $context, array $articles): void
    {
        if (empty($articles)) {
            return;
        }

        FeaturedBlock::updateOrCreate(
            ['context' => $context],
            [
                'featured_id' => $articles[0] ?? null,
                'top_right_id' => $articles[1] ?? null,
                'bottom_left_id' => $articles[2] ?? null,
                'bottom_right_id' => $articles[3] ?? null,
            ]
        );
    }
}
