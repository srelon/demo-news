<?php

namespace App\Services\Rss;

use Illuminate\Support\Str;

class HeuristicArticleAnalyzer implements ArticleAnalyzerInterface
{
    public function analyze(string $title, string $body_text): array
    {
        if (mb_strlen(trim($body_text)) < config('rss.min_body_length')) {
            return [
                'ok' => false,
                'reason' => 'Body too short',
                'code' => 'body_too_short',
            ];
        }

        $haystack = Str::lower($title . ' ' . $body_text);

        foreach (config('rss.blacklist') as $word) {
            if (str_contains($haystack, Str::lower($word))) {
                return [
                    'ok' => false,
                    'reason' => "Blacklisted word: {$word}",
                    'code' => 'blacklist',
                ];
            }
        }

        return [
            'ok' => true,
            'reason' => null,
            'code' => null,
        ];
    }

    public function rewrite(string $title, string $body_html): string
    {
        return $body_html;
    }
}
