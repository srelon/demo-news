<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\ArticleService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SpaController extends Controller
{
    public function __construct(private ArticleService $articles) {}

    public function __invoke(Request $request): Response
    {
        $html = file_get_contents(base_path('../frontend/site/dist/index.html'));

        $parts = array_values(array_filter(explode('/', trim($request->path(), '/'))));

        if (count($parts) === 3) {
            $og = $this->resolveArticleOg($parts, $request);

            if ($og) {
                $html = str_replace('</head>', $this->buildOgTags($og) . '</head>', $html);
            }
        }

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    private function resolveArticleOg(array $parts, Request $request): ?array
    {
        try {
            $article = $this->articles->findBySlug($parts[2]);
        } catch (\Throwable) {
            return null;
        }

        return [
            'title' => $article->seo_title ?: $article->title,
            'description' => $article->seo_description ?: ($article->excerpt ?? ''),
            'image' => $this->imageUrl($article->image),
            'url' => $request->url(),
        ];
    }

    private function imageUrl(?string $image): ?string
    {
        if (!$image) return null;
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }
        return url($image);
    }

    private function buildOgTags(array $og): string
    {
        $title = e($og['title']);
        $description = e($og['description']);
        $url = e($og['url']);
        $site_name = e(config('app.name'));

        $tags = <<<HTML

    <meta property="og:type" content="article">
    <meta property="og:site_name" content="{$site_name}">
    <meta property="og:title" content="{$title}">
    <meta property="og:description" content="{$description}">
    <meta property="og:url" content="{$url}">
    <meta name="description" content="{$description}">
HTML;

        if ($og['image']) {
            $image = e($og['image']);
            $tags .= <<<HTML

    <meta property="og:image" content="{$image}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{$image}">
HTML;
        }

        return $tags;
    }
}
