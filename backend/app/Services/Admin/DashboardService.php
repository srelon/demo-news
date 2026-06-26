<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminUsers;
use App\Models\Article;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getCurrentAdmin(Request $request): mixed
    {
        $user = AdminUsers::getProfile(Auth::guard('admin')->id());

        if ($user) {
            $user->createLogs($request);
        }

        return $user;
    }

    public function stats(): array
    {
        $now = Carbon::now();
        $start_of_month = $now->copy()->startOfMonth();
        $start_of_last_month = $now->copy()->subMonth()->startOfMonth();
        $end_of_last_month = $now->copy()->subMonth()->endOfMonth();

        // Users
        $users_total = User::count();
        $users_this_month = User::where('created_at', '>=', $start_of_month)->count();
        $users_last_month = User::whereBetween('created_at', [$start_of_last_month, $end_of_last_month])->count();

        // News (articles)
        $news_total = Article::count();
        $news_this_month = Article::where('created_at', '>=', $start_of_month)->count();
        $news_last_month = Article::whereBetween('created_at', [$start_of_last_month, $end_of_last_month])->count();

        // Daily comments for current month
        $daily_comments = Comment::select(
                DB::raw('DAY(created_at) as day'),
                DB::raw('COUNT(*) as count'),
            )
            ->where('created_at', '>=', $start_of_month)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day')
            ->toArray();

        $days_in_month = $now->daysInMonth;
        $daily_comments_series = array_map(
            fn($d) => $daily_comments[$d] ?? 0,
            range(1, $days_in_month),
        );

        // Monthly articles target
        $articles_this_month = Article::where('status', 'published')
            ->where('created_at', '>=', $start_of_month)
            ->count();
        $articles_last_month = Article::where('status', 'published')
            ->whereBetween('created_at', [$start_of_last_month, $end_of_last_month])
            ->count();
        $articles_today = Article::where('status', 'published')
            ->where('created_at', '>=', $now->copy()->startOfDay())
            ->count();
        $articles_percent = $articles_last_month > 0
            ? round(($articles_this_month / $articles_last_month) * 100, 1)
            : ($articles_this_month > 0 ? 100 : 0);

        // Statistics — last 12 months
        $months = collect(range(11, 0))->map(fn($i) => $now->copy()->subMonths($i));
        $stats_labels = $months->map(fn($m) => $m->format('M'))->values()->toArray();

        $stats_query = fn($model, $date_col = 'created_at') => $model::select(
                DB::raw("DATE_FORMAT({$date_col}, '%Y-%m') as month"),
                DB::raw('COUNT(*) as count'),
            )
            ->where($date_col, '>=', $months->first()->startOfMonth()->toDateTimeString())
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $comments_by_month = $stats_query(Comment::class);
        $users_by_month = $stats_query(User::class);
        $news_by_month = $stats_query(Article::class);

        $stats_series = fn($data) => $months->map(
            fn($m) => $data[$m->format('Y-m')] ?? 0,
        )->values()->toArray();

        // Recent comments
        $site_url = config('app.site_url', env('SITE_URL', ''));
        $recent_comments = Comment::with([
            'user:id,name,username,img',
            'article:id,title,image,slug,subcategory_id',
            'article.subcategory:id,slug,category_id',
            'article.subcategory.category:id,slug',
        ])
            ->withCount('reports')
            ->where('status', 1)
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($c) use ($site_url) {
                $article = $c->article;
                $url = null;
                if ($article && $article->slug && $article->subcategory?->slug && $article->subcategory?->category?->slug) {
                    $url = "{$site_url}/{$article->subcategory->category->slug}/{$article->subcategory->slug}/{$article->slug}#comment-{$c->id}";
                }

                return [
                    'id' => $c->id,
                    'body' => mb_substr(strip_tags($c->body), 0, 120),
                    'reports_count' => $c->reports_count ?? 0,
                    'user' => $c->user ? [
                        'id' => $c->user->id,
                        'name' => $c->user->name,
                        'img' => $c->user->img,
                    ] : null,
                    'article' => $article ? [
                        'id' => $article->id,
                        'title' => $article->title,
                        'image' => $article->image,
                        'url' => $url,
                    ] : null,
                    'created_at' => $c->created_at->toISOString(),
                ];
            });

        // Recent news
        $recent_news = Article::with('subcategory:id,name,category_id', 'subcategory.category:id,name')
            ->select(['id', 'title', 'status', 'image', 'subcategory_id', 'created_at'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'status' => $a->status,
                'image' => $a->image,
                'category' => $a->subcategory?->category?->name ?? '—',
                'subcategory' => $a->subcategory?->name ?? '—',
                'created_at' => $a->created_at->format('d.m.Y'),
            ]);

        return [
            'users' => [
                'total' => $users_total,
                'growth' => $this->growth($users_this_month, $users_last_month),
            ],
            'news' => [
                'total' => $news_total,
                'growth' => $this->growth($news_this_month, $news_last_month),
            ],
            'daily_comments' => [
                'days' => range(1, $days_in_month),
                'series' => $daily_comments_series,
            ],
            'monthly_articles' => [
                'this_month' => $articles_this_month,
                'last_month' => $articles_last_month,
                'today' => $articles_today,
                'percent' => min($articles_percent, 200),
            ],
            'statistics' => [
                'labels' => $stats_labels,
                'comments' => $stats_series($comments_by_month),
                'users' => $stats_series($users_by_month),
                'news' => $stats_series($news_by_month),
            ],
            'recent_news' => $recent_news,
            'recent_comments' => $recent_comments,
        ];
    }

    private function growth(int $current, int $previous): float
    {
        if ($previous === 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
