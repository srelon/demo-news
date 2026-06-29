<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\ArticleService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OgPreviewController extends Controller
{
    public function __construct(private ArticleService $articles) {}

    public function __invoke(Request $request): Response
    {
        $path = $request->query('path', '');
        $parts = array_values(array_filter(explode('/', trim($path, '/'))));

        $og = [
            'title' => config('app.name'),
            'description' => '',
            'image' => null,
            'body' => null,
            'type' => 'website',
            'url' => url('/'),
        ];

        // Article URL: /{category}/{subcategory}/{slug}
        if (count($parts) === 3) {
            $article_slug = $parts[2];

            try {
                $article = $this->articles->findBySlug($article_slug);

                $og['title'] = $article->seo_title ?: $article->title;
                $og['description'] = $article->seo_description ?: ($article->excerpt ?? '');
                $og['image'] = $this->imageUrl($article->image);
                $og['body'] = $article->body;
                $og['type'] = 'article';
                $og['url'] = url($path);
            } catch (\Throwable) {
                // article not found — fall back to site defaults
            }
        }

        return response($this->html($og), 200)
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    private function imageUrl(?string $image): ?string
    {
        if (!$image) return null;
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }
        return url($image);
    }

    private function html(array $og): string
    {
        $title = e($og['title']);
        $description = e($og['description']);
        $type = e($og['type']);
        $url = e($og['url']);
        $site_name = e(config('app.name'));

        $image_tags = '';
        if ($og['image']) {
            $image = e($og['image']);
            $image_tags = <<<HTML
            <meta property="og:image" content="{$image}">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:image" content="{$image}">
HTML;
        }

        $body_content = '';
        if (!empty($og['body'])) {
            $body_content = '<article>' . $og['body'] . '</article>';
        }

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>{$title}</title>
            <meta name="description" content="{$description}">
            <meta property="og:type" content="{$type}">
            <meta property="og:site_name" content="{$site_name}">
            <meta property="og:title" content="{$title}">
            <meta property="og:description" content="{$description}">
            <meta property="og:url" content="{$url}">
            {$image_tags}
            <meta http-equiv="refresh" content="0;url={$url}">
        </head>
        <body>
            <h1>{$title}</h1>
            {$body_content}
        </body>
        </html>
        HTML;
    }
}
