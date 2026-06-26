<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\CarbonInterval;
use Illuminate\Auth\Notifications\ResetPassword;
use Predis\Client as PredisClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Services\Rss\ArticleAnalyzerInterface::class,
            \App\Services\Rss\HeuristicArticleAnalyzer::class,
        );

        $this->app->singleton(PredisClient::class, fn() => new PredisClient([
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int) env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD') ?: null,
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        $this->loadMigrationsFrom([
            database_path('migrations'),
            database_path('migrations/admin'),
        ]);

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return rtrim(env('FRONTEND_URL', 'http://127.0.0.1:8880'), '/')
                . '/auth/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        });
    }
}
