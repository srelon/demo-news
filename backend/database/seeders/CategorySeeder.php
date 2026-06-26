<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'News',
                'slug' => 'news',
                'color' => '#e71d69',
                'order' => 1,
                'subcategories' => [
                    [
                        'name' => 'World',
                        'slug' => 'world',
                    ],
                    [
                        'name' => 'Politics',
                        'slug' => 'politics',
                    ],
                    [
                        'name' => 'Technology',
                        'slug' => 'technology',
                    ],
                    [
                        'name' => 'Science',
                        'slug' => 'science',
                    ],
                    [
                        'name' => 'Health',
                        'slug' => 'health',
                    ],
                    [
                        'name' => 'Sports',
                        'slug' => 'sports',
                    ],
                ],
            ],
            [
                'name' => 'Entertainment',
                'slug' => 'entertainment',
                'color' => '#15a752',
                'order' => 2,
                'subcategories' => [
                    [
                        'name' => 'Movies',
                        'slug' => 'movies',
                    ],
                    [
                        'name' => 'Music',
                        'slug' => 'music',
                    ],
                    [
                        'name' => 'Culture',
                        'slug' => 'culture',
                    ],
                ],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'color' => '#e3724a',
                'order' => 3,
                'subcategories' => [
                    [
                        'name' => 'Finance',
                        'slug' => 'finance',
                    ],
                    [
                        'name' => 'Markets',
                        'slug' => 'markets',
                    ],
                ],
            ],
            [
                'name' => 'Travel',
                'slug' => 'travel',
                'color' => '#00b5e9',
                'order' => 4,
                'subcategories' => [
                    [
                        'name' => 'Destinations',
                        'slug' => 'destinations',
                    ],
                    [
                        'name' => 'Tips',
                        'slug' => 'tips',
                    ],
                ],
            ],
            [
                'name' => 'Life Style',
                'slug' => 'life-style',
                'color' => '#9b59b6',
                'order' => 5,
                'subcategories' => [
                    [
                        'name' => 'Fashion',
                        'slug' => 'fashion',
                    ],
                    [
                        'name' => 'Food',
                        'slug' => 'food',
                    ],
                ],
            ],
            [
                'name' => 'Video',
                'slug' => 'video',
                'color' => '#2489b0',
                'order' => 6,
                'subcategories' => [
                    [
                        'name' => 'Highlights',
                        'slug' => 'highlights',
                    ],
                ],
            ],
        ];

        foreach ($data as $order => $item) {
            $subs = $item['subcategories'];
            unset($item['subcategories']);

            $category = Category::create($item);

            foreach ($subs as $subOrder => $sub) {
                Subcategory::create([
                    ...$sub,
                    'category_id' => $category->id,
                    'order' => $subOrder + 1,
                ]);
            }
        }
    }
}
