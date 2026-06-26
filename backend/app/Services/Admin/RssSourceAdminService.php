<?php

namespace App\Services\Admin;

use App\Models\Article;
use App\Models\RssItem;
use App\Models\RssSource;
use App\Services\Rss\RssFeedService;

class RssSourceAdminService
{
    public function __construct(private readonly RssFeedService $rss_feed_service) {}

    public function items(int $source_id, int $per_page, ?string $search)
    {
        return RssItem::where('rss_source_id', $source_id)
            ->whereNotIn('status', ['imported', 'ignored'])
            ->when($search, fn($q) => $q->where('title', 'LIKE', "%{$search}%"))
            ->latest('id')
            ->paginate($per_page);
    }

    public function deleteItem(int $item_id): void
    {
        RssItem::findOrFail($item_id)->update(['status' => 'ignored']);
    }

    public function deleteAllRejected(int $source_id): int
    {
        return RssItem::where('rss_source_id', $source_id)
            ->whereNotIn('status', ['imported', 'ignored'])
            ->update(['status' => 'ignored']);
    }

    /**
     * Retry a batch of rejected/failed items of a source. Cursor-based,
     * same pattern as refreshArticlesBatch.
     *
     * @return array{processed: int, imported: int, last_id: int, remaining: int}
     */
    public function retryItemsBatch(int $source_id, int $after_id, int $limit): array
    {
        $base_query = fn() => RssItem::where('rss_source_id', $source_id)
            ->whereNotIn('status', ['imported', 'ignored']);

        $items = $base_query()
            ->where('id', '>', $after_id)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $imported = 0;
        $last_id = $after_id;

        foreach ($items as $item) {
            try {
                $imported += $this->rss_feed_service->retryItem($item)['ok'] ? 1 : 0;
            } catch (\Throwable) {
                // keep the loop going, the item stays rejected/failed
            }

            $last_id = $item->id;
        }

        $remaining = $base_query()
            ->where('id', '>', $last_id)
            ->count();

        return [
            'processed' => $items->count(),
            'imported' => $imported,
            'last_id' => $last_id,
            'remaining' => $remaining,
        ];
    }

    public function retryItem(int $item_id, bool $force = false): array
    {
        return $this->rss_feed_service->retryItem(RssItem::findOrFail($item_id), $force);
    }

    /**
     * Refresh a batch of imported articles of a source (re-extract bodies).
     * Cursor-based so the frontend can loop without hitting request timeouts.
     *
     * @return array{processed: int, updated: int, last_id: int, remaining: int}
     */
    public function refreshArticlesBatch(int $source_id, int $after_id, int $limit): array
    {
        $article_ids = $source_id
            ? RssItem::where('rss_source_id', $source_id)->whereNotNull('article_id')->select('article_id')
            : RssItem::whereNotNull('article_id')->select('article_id');

        $articles = Article::whereIn('id', $article_ids)
            ->where('id', '>', $after_id)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $updated = 0;
        $last_id = $after_id;

        foreach ($articles as $article) {
            try {
                $updated += $this->rss_feed_service->refreshArticle($article) ? 1 : 0;
            } catch (\Throwable) {
                // skip unreachable pages, keep the loop going
            }

            $last_id = $article->id;
        }

        $remaining = Article::whereIn('id', $article_ids)
            ->where('id', '>', $last_id)
            ->count();

        return [
            'processed' => $articles->count(),
            'updated' => $updated,
            'last_id' => $last_id,
            'remaining' => $remaining,
        ];
    }

    public function list()
    {
        return RssSource::with('subcategory:id,name,category_id')
            ->withCount([
                'items as imported_count' => fn($q) => $q->where('status', 'imported'),
                'items as rejected_count' => fn($q) => $q->whereNotIn('status', ['imported', 'ignored']),
            ])
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): RssSource
    {
        return RssSource::create([
            'name' => $data['name'],
            'url' => $data['url'],
            'subcategory_id' => $data['subcategory_id'],
            'active' => $data['active'] ?? true,
        ]);
    }

    public function update(int $id, array $data): RssSource
    {
        $source = RssSource::findOrFail($id);

        $source->update([
            'name' => $data['name'],
            'url' => $data['url'],
            'subcategory_id' => $data['subcategory_id'],
            'active' => $data['active'] ?? $source->active,
        ]);

        return $source->fresh('subcategory');
    }

    public function toggle(int $id): RssSource
    {
        $source = RssSource::findOrFail($id);

        $source->update(['active' => !$source->active]);

        return $source;
    }

    public function delete(int $id): void
    {
        RssSource::findOrFail($id)->delete();
    }
}
