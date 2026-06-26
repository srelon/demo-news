<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\FeaturedBlock;
use Illuminate\Database\Seeder;

class FeaturedBlockSeeder extends Seeder
{
    public function run(): void
    {
        // Home block — top 4 articles by views
        $top = Article::published()->orderByDesc('views')->limit(4)->pluck('id')->toArray();

        if (count($top) >= 4) {
            FeaturedBlock::create([
                'context' => 'home',
                'featured_id' => $top[0],
                'top_right_id' => $top[1],
                'bottom_left_id' => $top[2],
                'bottom_right_id' => $top[3],
            ]);
        }

        // One block per category
        $categories = Category::with('subcategories')->get();

        foreach ($categories as $category) {
            $subcategoryIds = $category->subcategories->pluck('id');

            $articles = Article::published()
                ->whereIn('subcategory_id', $subcategoryIds)
                ->orderByDesc('views')
                ->limit(4)
                ->pluck('id')
                ->toArray();

            if (count($articles) < 4) {
                continue;
            }

            FeaturedBlock::create([
                'context' => "category:{$category->slug}",
                'featured_id' => $articles[0],
                'top_right_id' => $articles[1],
                'bottom_left_id' => $articles[2],
                'bottom_right_id' => $articles[3],
            ]);
        }
    }
}
