<?php

namespace App\Services\Admin;

use App\Models\Article;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Services\CacheService;
use App\Services\HtmlSanitizer;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ArticleAdminService
{
    public function __construct(
        private readonly ImageService $image_service,
        private readonly HtmlSanitizer $sanitizer,
    ) {}

    public function list(int $per_page, ?string $search, array $filters = [])
    {
        $query = Article::with(['subcategory.category'])
            ->select('articles.*')
            ->withCount('comments');

        if ($search) {
            $query->where('title', 'LIKE', "%{$search}%");
        }

        if (!empty($filters['source_name'])) {
            $query->where('source_name', $filters['source_name']);
        }

        if (!empty($filters['subcategory_id'])) {
            $query->where('subcategory_id', $filters['subcategory_id']);
        } elseif (!empty($filters['category_id'])) {
            $query->whereHas('subcategory', fn($q) => $q->where('category_id', $filters['category_id']));
        }

        if (!empty($filters['rss_source_id'])) {
            $query->whereIn('id', \App\Models\RssItem::where('rss_source_id', $filters['rss_source_id'])
                ->whereNotNull('article_id')
                ->select('article_id'));
        }

        match ($filters['sort'] ?? 'date_desc') {
            'date_asc' => $query->orderBy('published_at'),
            'views_desc' => $query->orderByDesc('views'),
            'comments_desc' => $query->orderByDesc('comments_count'),
            default => $query->latest('published_at'),
        };

        return $query->paginate($per_page);
    }

    public function delete(int $id): void
    {
        Article::findOrFail($id)->delete();

        CacheService::flushOnArticleWrite();
    }

    /**
     * Groups of duplicate articles: same normalized source URL
     * (query/fragment stripped) or same normalized title.
     *
     * @return array<array{key: string, articles: array}>
     */
    public function duplicateGroups(): array
    {
        $articles = Article::with('subcategory.category')
            ->withCount('comments')
            ->get();

        $by_url = [];
        $by_title = [];

        foreach ($articles as $article) {
            if ($article->source_url) {
                $by_url[preg_replace('/[?#].*$/', '', $article->source_url)][] = $article;
            }

            $title = Str::lower(trim($article->title));

            if ($title !== '') {
                $by_title[$title][] = $article;
            }
        }

        $groups = [];

        foreach ([$by_url, $by_title] as $index) {
            foreach ($index as $key => $group) {
                if (count($group) < 2) {
                    continue;
                }

                $ids = collect($group)->pluck('id')->sort()->implode(',');

                // the same pair often matches by both url and title — keep once
                $groups[$ids] = [
                    'key' => (string) $key,
                    'articles' => collect($group)
                        ->sortBy('id')
                        ->map(fn($a) => [
                            'id' => $a->id,
                            'title' => $a->title,
                            'image' => $a->image,
                            'status' => $a->status,
                            'source_name' => $a->source_name,
                            'subcategory' => $a->subcategory?->name,
                            'category' => $a->subcategory?->category?->name,
                            'published_at' => $a->published_at?->toDateTimeString(),
                            'views' => $a->views,
                            'comments_count' => $a->comments_count,
                        ])
                        ->values()
                        ->toArray(),
                ];
            }
        }

        return array_values($groups);
    }

    /**
     * Delete all duplicates keeping the oldest article of each group.
     * Returns the number of deleted articles.
     */
    public function cleanDuplicates(): int
    {
        $deleted_ids = [];

        foreach ($this->duplicateGroups() as $group) {
            $ids = array_column($group['articles'], 'id');
            sort($ids);
            array_shift($ids);

            $deleted_ids = array_merge($deleted_ids, $ids);
        }

        $deleted_ids = array_unique($deleted_ids);

        foreach ($deleted_ids as $id) {
            Article::find($id)?->delete();
        }

        if ($deleted_ids) {
            CacheService::flushOnArticleWrite();
        }

        return count($deleted_ids);
    }

    public function sourceNameOptions(): array
    {
        return Article::whereNotNull('source_name')
            ->where('source_name', '!=', '')
            ->distinct()
            ->orderBy('source_name')
            ->pluck('source_name')
            ->toArray();
    }

    public function find(int $id): Article
    {
        return Article::with(['subcategory.category', 'tags:id,name'])
            ->findOrFail($id);
    }

    public function create(array $data, ?UploadedFile $image): Article
    {
        $image_path = $image ? $this->image_service->upload($image, 'articles') : null;

        $status = $data['status'] ?? 'draft';
        $published_at = $data['published_at'] ?? null;
        if ($status === 'published' && !$published_at) {
            $published_at = now();
        }

        $article = Article::create([
            'title' => $data['title'],
            'slug' => ($data['slug'] ?? '') ?: Str::slug($data['title']),
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $this->sanitizeHtml($data['body'] ?? ''),
            'image' => $image_path,
            'subcategory_id' => $data['subcategory_id'],
            'status' => $status,
            'published_at' => $published_at,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'seo_keywords' => $data['seo_keywords'] ?? null,
        ]);

        $this->syncTags($article, $data['tags'] ?? '');

        if ($this->isDemoAdmin()) {
            $article->update(['demo_created' => true]);
        }

        CacheService::flushOnArticleWrite();

        return $this->find($article->id);
    }

    public function update(int $id, array $data, ?UploadedFile $image): Article
    {
        $article = Article::with('tags')->findOrFail($id);

        if ($this->isDemoAdmin() && !$article->demo_snapshot) {
            $article->update([
                'demo_edited' => true,
                'demo_snapshot' => [
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'excerpt' => $article->excerpt,
                    'body' => $article->body,
                    'image' => $article->image,
                    'subcategory_id' => $article->subcategory_id,
                    'status' => $article->status,
                    'published_at' => $article->published_at?->toDateTimeString(),
                    'seo_title' => $article->seo_title,
                    'seo_description' => $article->seo_description,
                    'seo_keywords' => $article->seo_keywords,
                    'tag_ids' => $article->tags->pluck('id')->toArray(),
                ],
            ]);
        }

        if ($image) {
            $data['image'] = $this->image_service->upload($image, 'articles');
        }

        $status = $data['status'] ?? $article->status;
        $published_at = $data['published_at'] ?? $article->published_at;
        if ($status === 'published' && !$published_at) {
            $published_at = now();
        }

        $article->update([
            'title' => $data['title'],
            'slug' => ($data['slug'] ?? '') ?: Str::slug($data['title']),
            'excerpt' => $data['excerpt'] ?? $article->excerpt,
            'body' => $this->sanitizeHtml($data['body'] ?? $article->body),
            'image' => $data['image'] ?? $article->image,
            'subcategory_id' => $data['subcategory_id'],
            'status' => $status,
            'published_at' => $published_at,
            'seo_title' => $data['seo_title'] ?? $article->seo_title,
            'seo_description' => $data['seo_description'] ?? $article->seo_description,
            'seo_keywords' => $data['seo_keywords'] ?? $article->seo_keywords,
        ]);

        $this->syncTags($article, $data['tags'] ?? '');

        CacheService::flushOnArticleWrite();

        return $this->find($id);
    }

    public function subcategoryOptions(): array
    {
        return Subcategory::select('id', 'name', 'category_id')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function categoryOptions(): array
    {
        return \App\Models\Category::select('id', 'name')->orderBy('name')->get()->toArray();
    }

    private function isDemoAdmin(): bool
    {
        $email = config('demo.admin_email');
        return $email && Auth::guard('admin')->user()?->email === $email;
    }

    private function sanitizeHtml(string $html): string
    {
        return $this->sanitizer->sanitize($html);
    }

    private function syncTags(Article $article, string $tags_input): void
    {
        if (trim($tags_input) === '') {
            $article->tags()->sync([]);
            return;
        }

        $names = array_filter(array_map('trim', explode(',', $tags_input)));
        $ids = collect($names)->map(function ($name) {
            return Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            )->id;
        });

        $article->tags()->sync($ids);
    }
}
