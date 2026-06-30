<?php

use App\Jobs\DemoAdminResetJob;
use App\Jobs\DemoResetJob;
use App\Jobs\FetchRssFeeds;
use App\Jobs\FlushArticleViews;
use App\Jobs\UpdateCategoryFeaturedBlocks;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('rss:fetch', function () {
    (new FetchRssFeeds)->handle();
    $this->info('RSS fetch completed');
})->purpose('Fetch all active RSS sources and import new articles');

Schedule::call(fn() => (new UpdateCategoryFeaturedBlocks)->handle())->everyTenMinutes();
Schedule::call(fn() => (new FetchRssFeeds)->handle())->everyFiveMinutes()->name('rss-fetch')->withoutOverlapping();
Schedule::call(fn() => (new FlushArticleViews)->handle())->daily()->name('flush-article-views')->withoutOverlapping();
Schedule::call(fn() => (new DemoResetJob)->handle())->hourly()->name('demo-reset')->withoutOverlapping()->when(fn() => config('demo.admin_email'));
Schedule::call(fn() => (new DemoAdminResetJob)->handle())->everyFifteenMinutes()->name('demo-admin-reset')->withoutOverlapping()->when(fn() => config('demo.admin_email'));
