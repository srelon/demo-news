<?php

namespace App\Services\Rss;

interface ArticleAnalyzerInterface
{
    /**
     * Decide whether an imported item is acceptable for publishing.
     * `code` is a machine-readable identifier of the failed check
     * (e.g. body_too_short, blacklist) — null when ok.
     *
     * @return array{ok: bool, reason: ?string, code: ?string}
     */
    public function analyze(string $title, string $body_text): array;

    /**
     * Rewrite the article body before publishing.
     * Heuristic implementation is a pass-through; an LLM-backed
     * implementation can return a rewritten version.
     */
    public function rewrite(string $title, string $body_html): string;
}
