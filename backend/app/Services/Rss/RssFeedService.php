<?php

namespace App\Services\Rss;

use App\Models\Article;
use App\Models\RssItem;
use App\Models\RssSource;
use App\Models\Tag;
use App\Services\CacheService;
use App\Services\HtmlSanitizer;
use App\Services\ImageService;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\Readability;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laminas\Feed\Reader\Entry\EntryInterface;
use Laminas\Feed\Reader\Reader;

class RssFeedService
{
    public function __construct(
        private readonly ArticleAnalyzerInterface $analyzer,
        private readonly HtmlSanitizer $sanitizer,
        private readonly ImageService $image_service,
    ) {}

    /**
     * Fetch one source and import its new entries.
     * Returns the number of imported articles.
     * Throws on feed-level failures (network, invalid XML).
     */
    public function fetchSource(RssSource $source): int
    {
        $response = Http::timeout(config('rss.http_timeout'))
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; DashboardRssBot/1.0)'])
            ->get($source->url);

        if (!$response->successful()) {
            throw new \RuntimeException("Feed request failed: HTTP {$response->status()}");
        }

        $feed = Reader::importString($response->body());

        $imported = 0;
        $processed = 0;

        foreach ($feed as $entry) {
            if ($processed >= config('rss.max_items_per_run')) {
                break;
            }

            $guid = $entry->getId() ?: $entry->getLink();

            if (!$guid) {
                continue;
            }

            // BBC bumps the guid fragment on every story revision
            // (...c4gyx0x1d5ro#0 → #5) — same story, so hash without it
            $guid = preg_replace('/#.*$/', '', $guid);

            $guid_hash = md5($guid);

            if (RssItem::where('guid_hash', $guid_hash)->exists()) {
                continue;
            }

            $processed++;

            // Claim the entry in the journal before creating anything else:
            // the unique index on guid_hash guarantees that of two concurrent
            // runs only one proceeds, so duplicate articles are impossible.
            try {
                $item = RssItem::create([
                    'rss_source_id' => $source->id,
                    'guid_hash' => $guid_hash,
                    'guid' => Str::limit($guid, 250),
                    'link' => Str::limit((string) $entry->getLink(), 250) ?: null,
                    'title' => Str::limit((string) $entry->getTitle(), 250),
                    'status' => 'failed',
                    'reason' => 'Processing interrupted',
                    'reason_code' => 'error',
                ]);
            } catch (UniqueConstraintViolationException) {
                continue;
            }

            try {
                $imported += $this->processEntry($source, $entry, $item) ? 1 : 0;
            } catch (\Throwable $e) {
                $item->update([
                    'reason' => Str::limit($e->getMessage(), 250),
                    'reason_code' => 'error',
                ]);
            }
        }

        if ($imported > 0) {
            CacheService::flushOnArticleWrite();
        }

        return $imported;
    }

    private function processEntry(RssSource $source, EntryInterface $entry, RssItem $item): bool
    {
        $title = trim((string) $entry->getTitle());
        $link = (string) $entry->getLink();
        $feed_content = (string) ($entry->getContent() ?: $entry->getDescription());

        if ($pattern = $this->matchSkipPattern($link)) {
            $item->update([
                'status' => 'rejected',
                'reason' => "Skipped URL pattern: {$pattern}",
                'reason_code' => 'skip_url',
            ]);

            return false;
        }

        if ($duplicate = $this->findDuplicate($link, $title)) {
            // not a rejection — just a guard row so the guid blocks re-import
            $item->update([
                'status' => 'ignored',
                'reason' => "Duplicate of article #{$duplicate->id}",
                'reason_code' => 'duplicate',
            ]);

            return false;
        }

        $built = $this->buildBody($link ?: null, $feed_content, $this->entryImageUrl($entry));

        $verdict = $this->analyzer->analyze($title, $built['text']);

        if (!$verdict['ok']) {
            $item->update([
                'status' => 'rejected',
                'reason' => $verdict['reason'],
                'reason_code' => $verdict['code'],
            ]);

            return false;
        }

        $this->createArticle(
            $source,
            $item,
            $title,
            $link,
            Str::limit(trim(strip_tags($feed_content)), 300),
            $built['html'],
            $built['image'],
            $entry->getDateCreated() ?: now(),
        );

        return true;
    }

    /**
     * Re-extract body (and cover image when missing) for an already
     * imported article from its source page.
     */
    public function refreshArticle(Article $article): bool
    {
        if ($article->source_type !== 'rss' || !$article->source_url) {
            return false;
        }

        $built = $this->buildBody($article->source_url);

        if ($built['text'] === '') {
            return false;
        }

        $body_html = $this->analyzer->rewrite($article->title, $built['html']);
        $body_html = $this->localizeImages($body_html);

        $data = ['body' => $body_html];

        if (!$article->image && $built['image']) {
            $image_path = $this->image_service->uploadFromUrl($built['image'], 'articles');

            if ($image_path) {
                $data['image'] = $image_path;
            }
        }

        if (!$article->seo_title) {
            $data['seo_title'] = mb_substr($article->title, 0, 255);
        }

        if (!$article->seo_description) {
            $data['seo_description'] = mb_substr(trim(strip_tags($body_html)), 0, 500);
        }

        $article->update($data);

        // Tag only untagged articles — manually assigned tags are kept as is
        if ($article->tags()->count() === 0) {
            $article->load('subcategory.category');

            $tag_ids = $this->matchTags(
                $article->title,
                trim(strip_tags($body_html)),
                ($article->subcategory?->name ?? '') . ' ' . ($article->subcategory?->category?->name ?? ''),
            );

            if ($tag_ids) {
                $article->tags()->sync($tag_ids);
            }
        }

        if (!$article->seo_keywords) {
            $article->load('tags');
            $keywords = $article->tags->pluck('name')->implode(', ');

            if ($keywords) {
                $article->update(['seo_keywords' => mb_substr($keywords, 0, 500)]);
            }
        }

        CacheService::flushOnArticleWrite();

        return true;
    }

    /**
     * Re-run the import pipeline for a rejected/failed journal item.
     * With $force the analyzer verdict (blacklist, min length) is ignored —
     * manual override only; skip-url and duplicate checks still apply.
     *
     * @return array{ok: bool, reason: ?string}
     */
    public function retryItem(RssItem $item, bool $force = false): array
    {
        if ($item->status === 'imported') {
            return [
                'ok' => false,
                'reason' => 'Already imported',
            ];
        }

        $source = $item->source;
        $url = $item->link ?: (preg_match('/^https?:\/\//i', $item->guid) ? $item->guid : null);

        if (!$source || !$url) {
            return [
                'ok' => false,
                'reason' => 'No source URL stored for this item',
            ];
        }

        if ($pattern = $this->matchSkipPattern($url)) {
            $item->update([
                'status' => 'rejected',
                'reason' => "Skipped URL pattern: {$pattern}",
                'reason_code' => 'skip_url',
            ]);

            return [
                'ok' => false,
                'reason' => "Skipped URL pattern: {$pattern}",
            ];
        }

        $title = trim((string) $item->title);

        if ($duplicate = $this->findDuplicate($url, $title)) {
            $item->update([
                'status' => 'ignored',
                'reason' => "Duplicate of article #{$duplicate->id}",
                'reason_code' => 'duplicate',
            ]);

            return [
                'ok' => false,
                'reason' => "Duplicate of article #{$duplicate->id}",
            ];
        }
        $built = $this->buildBody($url);

        if ($force && $built['text'] === '') {
            return [
                'ok' => false,
                'reason' => 'Source page returned no content',
            ];
        }

        $verdict = $force
            ? ['ok' => true, 'reason' => null, 'code' => null]
            : $this->analyzer->analyze($title, $built['text']);

        if (!$verdict['ok']) {
            $item->update([
                'status' => 'rejected',
                'reason' => $verdict['reason'],
                'reason_code' => $verdict['code'],
            ]);

            return [
                'ok' => false,
                'reason' => $verdict['reason'],
            ];
        }

        $this->createArticle(
            $source,
            $item,
            $title,
            $url,
            Str::limit($built['text'], 300),
            $built['html'],
            $built['image'],
            now(),
        );

        CacheService::flushOnArticleWrite();

        return [
            'ok' => true,
            'reason' => null,
        ];
    }

    /**
     * Same story re-announced in a feed (e.g. BBC guid revisions) —
     * an article with this source URL or the exact same title already exists.
     */
    private function findDuplicate(?string $link, ?string $title = null): ?Article
    {
        if (!$link && !$title) {
            return null;
        }

        return Article::withTrashed()
            ->where(function ($q) use ($link, $title) {
                if ($link) {
                    $q->orWhere('source_url', Str::limit($link, 250));
                }

                if ($title) {
                    $q->orWhere('title', $title);
                }
            })
            ->first();
    }

    private function matchSkipPattern(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        foreach (config('rss.skip_url_patterns') as $pattern) {
            if (str_contains($url, $pattern)) {
                return $pattern;
            }
        }

        return null;
    }

    /**
     * Shared extraction pipeline: full text from the article page with
     * fallback to feed content, sanitizing and DOM cleanup.
     *
     * @return array{html: string, text: string, image: ?string}
     */
    private function buildBody(?string $url, string $fallback_html = '', ?string $cover_url = null): array
    {
        $extracted = $url ? $this->extractFullText($url) : null;
        $cover = $cover_url ?: ($extracted['image'] ?? null);

        $html = $extracted['content'] ?? $fallback_html;
        $html = $this->stripNoise($html);
        $html = $this->sanitizer->sanitize($html);
        $html = $this->cleanBody($html, $cover);

        return [
            'html' => $html,
            'text' => trim(strip_tags($html)),
            'image' => $cover,
        ];
    }

    private function createArticle(
        RssSource $source,
        RssItem $item,
        string $title,
        string $link,
        ?string $excerpt,
        string $body_html,
        ?string $image_url,
        \DateTimeInterface $published_at,
    ): Article {
        $body_html = $this->analyzer->rewrite($title, $body_html);
        $body_html = $this->localizeImages($body_html);

        $image_path = $image_url ? $this->image_service->uploadFromUrl($image_url, 'articles') : null;

        $article = Article::create([
            'title' => $title,
            'slug' => $this->uniqueSlug($title),
            'seo_title' => mb_substr($title, 0, 255),
            'seo_description' => $excerpt ? mb_substr($excerpt, 0, 500) : null,
            'excerpt' => $excerpt,
            'body' => $body_html,
            'image' => $image_path,
            'subcategory_id' => $source->subcategory_id,
            'source_type' => 'rss',
            'source_url' => Str::limit($link, 250),
            'source_name' => $source->name,
            'status' => 'published',
            'published_at' => $published_at,
        ]);

        $article->load('subcategory.category');

        $tag_ids = $this->matchTags(
            $title,
            trim(strip_tags($body_html)),
            ($article->subcategory?->name ?? '') . ' ' . ($article->subcategory?->category?->name ?? ''),
        );

        if ($tag_ids) {
            $article->tags()->sync($tag_ids);
            $keywords = Tag::whereIn('id', $tag_ids)->pluck('name')->implode(', ');
            $article->update(['seo_keywords' => mb_substr($keywords, 0, 500)]);
        }

        $item->update([
            'status' => 'imported',
            'reason' => null,
            'reason_code' => null,
            'article_id' => $article->id,
        ]);

        return $article;
    }

    /**
     * Match existing tags against the article: a hit in the title or in the
     * category/subcategory names scores 5, each body hit scores 1. Tags
     * below min_score are skipped, the top `max` by score are returned.
     *
     * @return array<int>
     */
    private function matchTags(string $title, string $body_text, string $context = ''): array
    {
        $title_lower = Str::lower($title . ' ' . $context);
        $body_lower = Str::lower($body_text);

        $scores = [];

        foreach (Tag::select('id', 'name')->get() as $tag) {
            $name = Str::lower(trim($tag->name));

            if (mb_strlen($name) < 3) {
                continue;
            }

            $pattern = '/\b' . preg_quote($name, '/') . '\b/u';

            $score = preg_match_all($pattern, $title_lower) * 5
                + preg_match_all($pattern, $body_lower);

            if ($score >= config('rss.auto_tags.min_score')) {
                $scores[$tag->id] = $score;
            }
        }

        arsort($scores);

        return array_slice(array_keys($scores), 0, config('rss.auto_tags.max'));
    }

    /**
     * @return array{content: ?string, image: ?string}|null
     */
    private function extractFullText(string $url): ?array
    {
        try {
            $response = Http::timeout(config('rss.http_timeout'))
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; DashboardRssBot/1.0)'])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $readability = new Readability(new Configuration());
            $readability->parse($response->body());

            $content = (string) $readability->getContent();

            // Readability sometimes picks a single block of a multi-block page
            // (BBC splits the body into sibling divs) — fall back to the
            // semantic <article> element when it holds substantially more text
            $article_html = $this->largestArticleTag($response->body());

            if ($article_html !== null) {
                $content_len = mb_strlen(trim(strip_tags($content)));
                $article_len = mb_strlen(trim(strip_tags($article_html)));

                if ($article_len > $content_len * 2) {
                    $content = preg_replace(
                        '#<(figure|h1|header|footer|nav|form|button)\b[^>]*>.*?</\1>#si',
                        '',
                        $article_html
                    );
                }
            }

            return [
                'content' => $content,
                'image' => $readability->getImage(),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function largestArticleTag(string $html): ?string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML($html, LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $best = null;
        $best_len = 0;

        foreach ($doc->getElementsByTagName('article') as $article) {
            $len = mb_strlen(trim($article->textContent));

            if ($len > $best_len) {
                $best = $article;
                $best_len = $len;
            }
        }

        if (!$best) {
            return null;
        }

        $inner = '';
        foreach ($best->childNodes as $node) {
            $inner .= $doc->saveHTML($node);
        }

        return $inner;
    }

    /**
     * Drop elements that survive readability but are noise in an article body:
     * photo captions, "related" asides and decorative images (icons, author
     * headshots, tracking pixels) that have no usable src.
     */
    private function stripNoise(string $html): string
    {
        $html = preg_replace('#<figcaption\b[^>]*>.*?</figcaption>#si', '', $html);
        $html = preg_replace('#<aside\b[^>]*>.*?</aside>#si', '', $html);

        return $html;
    }

    /**
     * DOM-level cleanup of the sanitized body:
     * - images without a usable src (lazy-load placeholders) and images
     *   duplicating the cover image are dropped,
     * - timestamp / photo-agency credit lines are dropped,
     * - elements left empty after sanitizing (bullet lists from site nav) are dropped,
     * - leading byline shards and a lead image duplicating the cover are dropped.
     */
    public function cleanBody(string $html, ?string $main_image_url = null): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>'
            . $html . '</body></html>',
            LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        $body = $doc->getElementsByTagName('body')->item(0);

        // Broken / duplicate images
        $main_file = $main_image_url
            ? pathinfo((string) parse_url($main_image_url, PHP_URL_PATH), PATHINFO_FILENAME)
            : null;

        foreach (iterator_to_array($xpath->query('//img')) as $img) {
            $src = $img->getAttribute('src');
            $file = $src ? pathinfo((string) parse_url($src, PHP_URL_PATH), PATHINFO_FILENAME) : '';

            $is_junk = $src && preg_match('/placeholder|spacer|sprite|tracking|pixel|blank|1x1/i', $src);

            $width = (int) $img->getAttribute('width');
            $height = (int) $img->getAttribute('height');
            $is_tiny = ($width > 0 && $width <= 50) || ($height > 0 && $height <= 50);

            if (!$src || $is_junk || $is_tiny || ($main_file && $file === $main_file)) {
                $img->parentNode->removeChild($img);
            }
        }

        // Links pointing to the source site's internal pages (contact forms,
        // "read more" anchors) — relative hrefs are useless on our domain.
        // A link inside a sentence is unwrapped to keep the text; a link
        // standing on its own line (CTA button) is removed entirely.
        foreach (iterator_to_array($xpath->query('//a')) as $a) {
            $href = $a->getAttribute('href');

            if (preg_match('/^https?:\/\//i', $href)) {
                continue;
            }

            $parent = $a->parentNode;
            $sibling_text = str_replace($a->textContent, '', $parent->textContent);
            $standalone = $parent->nodeName === 'body' || $this->isBlankText($sibling_text);

            if ($standalone && $xpath->query('.//img', $a)->length === 0) {
                $parent->removeChild($a);
                continue;
            }

            while ($a->firstChild) {
                $parent->insertBefore($a->firstChild, $a);
            }
            $parent->removeChild($a);
        }

        // Timestamps, photo credits and embedded CTA lines
        foreach (iterator_to_array($xpath->query('//p | //li | //h2 | //h3 | //h4')) as $node) {
            $text = trim($node->textContent);

            if ($text === '') {
                continue;
            }

            foreach (config('rss.junk_lines') as $pattern) {
                if (preg_match($pattern, $text)) {
                    $node->parentNode->removeChild($node);
                    break;
                }
            }
        }

        // Elements that became empty after sanitizing, repeated until stable
        // so emptied parents (ul without li) are removed too
        do {
            $changed = false;

            foreach (iterator_to_array($xpath->query('//p | //li | //ul | //ol | //blockquote | //strong | //em | //a')) as $node) {
                if ($this->isBlankText($node->textContent) && $xpath->query('.//img', $node)->length === 0) {
                    $node->parentNode->removeChild($node);
                    $changed = true;
                }
            }
        } while ($changed);

        // Leading junk: a lead image duplicating the cover and short
        // byline shards before the first real paragraph or heading
        $removed = 0;

        while ($removed < 5 && ($first = $this->firstElement($body))) {
            $text = $this->isBlankText($first->textContent) ? '' : trim($first->textContent);
            $has_img = $first->nodeName === 'img' || $xpath->query('.//img', $first)->length > 0;

            if ($has_img && $text === '') {
                $body->removeChild($first);
                $removed++;
                continue;
            }

            if (
                !$has_img
                && $text !== ''
                && mb_strlen($text) < 60
                && !in_array($first->nodeName, ['h2', 'h3', 'h4'], true)
            ) {
                $body->removeChild($first);
                $removed++;
                continue;
            }

            break;
        }

        $result = '';
        foreach ($body->childNodes as $node) {
            $result .= $doc->saveHTML($node);
        }

        return trim($result);
    }

    /**
     * Whitespace check that also treats non-breaking spaces and
     * zero-width characters as blank — regular trim() does not.
     */
    private function isBlankText(string $text): bool
    {
        return trim(preg_replace('/[\x{00A0}\x{200B}-\x{200D}\x{FEFF}]/u', ' ', $text)) === '';
    }

    /**
     * Download remote body images to local storage (webp via ImageService)
     * and rewrite their src to this site. Unreachable images keep the
     * original remote src.
     */
    private function localizeImages(string $html): string
    {
        if (trim($html) === '' || !str_contains($html, '<img')) {
            return $html;
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>'
            . $html . '</body></html>',
            LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $base_url = rtrim(config('app.url'), '/');
        $downloaded = [];

        foreach ($doc->getElementsByTagName('img') as $img) {
            $src = $img->getAttribute('src');

            if (!preg_match('/^https?:\/\//i', $src)) {
                continue;
            }

            if (!array_key_exists($src, $downloaded)) {
                $downloaded[$src] = $this->image_service->uploadFromUrl($src, 'articles');
            }

            if ($downloaded[$src]) {
                $img->setAttribute('src', "{$base_url}/{$downloaded[$src]}");
            }
        }

        $body = $doc->getElementsByTagName('body')->item(0);
        $result = '';
        foreach ($body->childNodes as $node) {
            $result .= $doc->saveHTML($node);
        }

        return trim($result);
    }

    private function firstElement(\DOMNode $body): ?\DOMElement
    {
        foreach ($body->childNodes as $node) {
            if ($node instanceof \DOMElement) {
                return $node;
            }

            if ($node instanceof \DOMText && trim($node->textContent) !== '') {
                return null;
            }
        }

        return null;
    }

    private function entryImageUrl(EntryInterface $entry): ?string
    {
        $enclosure = $entry->getEnclosure();

        if ($enclosure && !empty($enclosure->url) && str_starts_with((string) ($enclosure->type ?? 'image/'), 'image/')) {
            return $enclosure->url;
        }

        return null;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: Str::random(8);
        $slug = $base;
        $suffix = 2;

        while (Article::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
