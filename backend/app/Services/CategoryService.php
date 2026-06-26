<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    public function findBySlug(string $slug): Category
    {
        return Cache::tags([CacheService::TAG_CATEGORIES])->remember(
            "category.{$slug}",
            CacheService::TTL_ARTICLE,
            fn() => Category::where('slug', $slug)
                ->with('subcategories:id,category_id,name,slug')
                ->firstOrFail()
        );
    }

}
