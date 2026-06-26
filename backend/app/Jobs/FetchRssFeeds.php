<?php

namespace App\Jobs;

use App\Models\RssSource;
use App\Services\Rss\RssFeedService;
use Illuminate\Support\Facades\Log;

class FetchRssFeeds
{
    public function handle(): void
    {
        $service = app(RssFeedService::class);

        foreach (RssSource::where('active', true)->get() as $source) {
            $service->fetchSource($source);

            try {
                $source->update([
                    'last_fetched_at' => now(),
                    'last_status' => 'ok',
                    'last_error' => null,
                ]);

            } catch (\Throwable $e) {
                $source->update([
                    'last_fetched_at' => now(),
                    'last_status' => 'error',
                    'last_error' => mb_substr($e->getMessage(), 0, 250),
                ]);

                Log::warning("RSS [{$source->name}]: {$e->getMessage()}");
            }
        }
    }
}
