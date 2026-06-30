<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\Page;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Services\CacheService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\TagSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoResetJob
{
    public function handle(): void
    {
        if (!config('demo.admin_email')) {
            return;
        }

        $this->resetPages();
        $this->resetCategories();
        $this->resetArticles();
        $this->resetTags();

        CacheService::flushOnArticleWrite();
    }

    private function resetArticles(): void
    {
        Article::withTrashed()->where('demo_edited', true)->each(function (Article $article) {
            $snapshot = $article->demo_snapshot;
            $tag_ids = $snapshot['tag_ids'] ?? [];
            unset($snapshot['tag_ids']);

            $article->fill([
                ...$snapshot,
                'published_at' => $snapshot['published_at'] ? now()->parse($snapshot['published_at']) : null,
                'demo_edited' => false,
                'demo_snapshot' => null,
                'deleted_at' => null,
            ]);
            $article->save();

            $article->tags()->sync($tag_ids);
        });

        Article::withTrashed()->where('demo_created', true)->forceDelete();

        $valid_ids = Subcategory::pluck('id');
        Article::onlyTrashed()
            ->where('demo_created', false)
            ->whereIn('subcategory_id', $valid_ids)
            ->restore();
    }

    private function resetPages(): void
    {
        Page::withTrashed()->where('demo_edited', true)->each(function (Page $page) {
            $snapshot = $page->demo_snapshot;
            $page->fill([
                ...$snapshot,
                'demo_edited' => false,
                'demo_snapshot' => null,
                'deleted_at' => null,
            ]);
            $page->save();
        });

        Page::withTrashed()->where('demo_created', true)->forceDelete();
    }

    private function resetCategories(): void
    {
        $now = now();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('subcategories')->truncate();
        DB::table('categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        foreach (CategorySeeder::data() as $item) {
            DB::table('categories')->insert([
                'id' => $item['id'],
                'name' => $item['name'],
                'slug' => $item['slug'],
                'color' => $item['color'],
                'order' => $item['order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($item['subcategories'] as $sub_order => $sub) {
                DB::table('subcategories')->insert([
                    'id' => $sub['id'],
                    'category_id' => $item['id'],
                    'name' => $sub['name'],
                    'slug' => $sub['slug'],
                    'order' => $sub_order + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function resetTags(): void
    {
        $seeder_slugs = collect(TagSeeder::tagNames())
            ->map(fn($name) => Str::slug($name))
            ->toArray();

        Tag::whereNotIn('slug', $seeder_slugs)->get()->each(function (Tag $tag) {
            DB::table('article_tag')->where('tag_id', $tag->id)->delete();
            $tag->delete();
        });

        (new TagSeeder)->run();
    }
}
