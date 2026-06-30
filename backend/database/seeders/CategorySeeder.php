<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public static function data(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'News',
                'slug' => 'news',
                'color' => '#e71d69',
                'order' => 1,
                'subcategories' => [
                    [
                        'id' => 1,
                        'name' => 'World',
                        'slug' => 'world',
                    ],
                    [
                        'id' => 2,
                        'name' => 'Politics',
                        'slug' => 'politics',
                    ],
                    [
                        'id' => 3,
                        'name' => 'Technology',
                        'slug' => 'technology',
                    ],
                    [
                        'id' => 4,
                        'name' => 'Science',
                        'slug' => 'science',
                    ],
                    [
                        'id' => 5,
                        'name' => 'Health',
                        'slug' => 'health',
                    ],
                    [
                        'id' => 6,
                        'name' => 'Sports',
                        'slug' => 'sports',
                    ],
                ],
            ],
            [
                'id' => 2,
                'name' => 'Entertainment',
                'slug' => 'entertainment',
                'color' => '#15a752',
                'order' => 2,
                'subcategories' => [
                    [
                        'id' => 7,
                        'name' => 'Movies',
                        'slug' => 'movies',
                    ],
                    [
                        'id' => 8,
                        'name' => 'Music',
                        'slug' => 'music',
                    ],
                    [
                        'id' => 9,
                        'name' => 'Culture',
                        'slug' => 'culture',
                    ],
                ],
            ],
            [
                'id' => 3,
                'name' => 'Business',
                'slug' => 'business',
                'color' => '#e3724a',
                'order' => 3,
                'subcategories' => [
                    [
                        'id' => 10,
                        'name' => 'Finance',
                        'slug' => 'finance',
                    ],
                    [
                        'id' => 11,
                        'name' => 'Markets',
                        'slug' => 'markets',
                    ],
                ],
            ],
            [
                'id' => 4,
                'name' => 'Travel',
                'slug' => 'travel',
                'color' => '#00b5e9',
                'order' => 4,
                'subcategories' => [
                    [
                        'id' => 12,
                        'name' => 'Destinations',
                        'slug' => 'destinations',
                    ],
                    [
                        'id' => 13,
                        'name' => 'Tips',
                        'slug' => 'tips',
                    ],
                ],
            ],
            [
                'id' => 5,
                'name' => 'Life Style',
                'slug' => 'life-style',
                'color' => '#9b59b6',
                'order' => 5,
                'subcategories' => [
                    [
                        'id' => 14,
                        'name' => 'Fashion',
                        'slug' => 'fashion',
                    ],
                    [
                        'id' => 15,
                        'name' => 'Food',
                        'slug' => 'food',
                    ],
                ],
            ],
            [
                'id' => 6,
                'name' => 'Video',
                'slug' => 'video',
                'color' => '#2489b0',
                'order' => 6,
                'subcategories' => [
                    [
                        'id' => 16,
                        'name' => 'Highlights',
                        'slug' => 'highlights',
                    ],
                ],
            ],
        ];
    }

    public function run(): void
    {
        foreach (static::data() as $item) {
            $category = Category::create([
                'id' => $item['id'],
                'name' => $item['name'],
                'slug' => $item['slug'],
                'color' => $item['color'],
                'order' => $item['order'],
            ]);

            foreach ($item['subcategories'] as $subOrder => $sub) {
                Subcategory::create([
                    'id' => $sub['id'],
                    'name' => $sub['name'],
                    'slug' => $sub['slug'],
                    'category_id' => $category->id,
                    'order' => $subOrder + 1,
                ]);
            }
        }
    }
}
