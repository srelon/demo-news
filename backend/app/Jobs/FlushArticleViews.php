<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\ArticleView;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FlushArticleViews implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $ids = ArticleView::pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $counts = ArticleView::whereIn('id', $ids)
            ->selectRaw('article_id, COUNT(*) as total')
            ->groupBy('article_id')
            ->pluck('total', 'article_id');

        $deleted = ArticleView::whereIn('id', $ids)->delete();

        if (!$deleted) {
            return;
        }

        foreach ($counts as $article_id => $total) {
            Article::where('id', $article_id)->increment('views', $total);
        }
    }
}
