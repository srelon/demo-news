<?php

namespace Database\Seeders;

use App\Models\RssSource;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class RssSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            // World
            [
                'name' => 'BBC World',
                'url' => 'https://feeds.bbci.co.uk/news/world/rss.xml',
                'subcategory' => 'world',
            ],
            [
                'name' => 'The Guardian World',
                'url' => 'https://www.theguardian.com/world/rss',
                'subcategory' => 'world',
            ],

            // Politics
            [
                'name' => 'BBC Politics',
                'url' => 'https://feeds.bbci.co.uk/news/politics/rss.xml',
                'subcategory' => 'politics',
            ],
            [
                'name' => 'The Guardian Politics',
                'url' => 'https://www.theguardian.com/politics/rss',
                'subcategory' => 'politics',
            ],

            // Technology
            [
                'name' => 'BBC Technology',
                'url' => 'https://feeds.bbci.co.uk/news/technology/rss.xml',
                'subcategory' => 'technology',
            ],
            [
                'name' => 'The Guardian Technology',
                'url' => 'https://www.theguardian.com/technology/rss',
                'subcategory' => 'technology',
            ],

            // Science
            [
                'name' => 'BBC Science',
                'url' => 'https://feeds.bbci.co.uk/news/science_and_environment/rss.xml',
                'subcategory' => 'science',
            ],
            [
                'name' => 'The Guardian Science',
                'url' => 'https://www.theguardian.com/science/rss',
                'subcategory' => 'science',
            ],

            // Health
            [
                'name' => 'BBC Health',
                'url' => 'https://feeds.bbci.co.uk/news/health/rss.xml',
                'subcategory' => 'health',
            ],
            [
                'name' => 'The Guardian Health',
                'url' => 'https://www.theguardian.com/society/health/rss',
                'subcategory' => 'health',
            ],

            // Sports
            [
                'name' => 'BBC Sport',
                'url' => 'https://feeds.bbci.co.uk/sport/rss.xml',
                'subcategory' => 'sports',
            ],
            [
                'name' => 'The Guardian Sport',
                'url' => 'https://www.theguardian.com/sport/rss',
                'subcategory' => 'sports',
            ],

            // Movies
            [
                'name' => 'The Guardian Film',
                'url' => 'https://www.theguardian.com/film/rss',
                'subcategory' => 'movies',
            ],

            // Music
            [
                'name' => 'The Guardian Music',
                'url' => 'https://www.theguardian.com/music/rss',
                'subcategory' => 'music',
            ],

            // Culture
            [
                'name' => 'BBC Entertainment & Arts',
                'url' => 'https://feeds.bbci.co.uk/news/entertainment_and_arts/rss.xml',
                'subcategory' => 'culture',
            ],
            [
                'name' => 'The Guardian Culture',
                'url' => 'https://www.theguardian.com/culture/rss',
                'subcategory' => 'culture',
            ],

            // Finance
            [
                'name' => 'BBC Business',
                'url' => 'https://feeds.bbci.co.uk/news/business/rss.xml',
                'subcategory' => 'finance',
            ],
            [
                'name' => 'The Guardian Business',
                'url' => 'https://www.theguardian.com/business/rss',
                'subcategory' => 'finance',
            ],

            // Markets
            [
                'name' => 'The Guardian Money',
                'url' => 'https://www.theguardian.com/money/rss',
                'subcategory' => 'markets',
            ],

            // Travel
            [
                'name' => 'The Guardian Travel',
                'url' => 'https://www.theguardian.com/travel/rss',
                'subcategory' => 'destinations',
            ],

            // Fashion
            [
                'name' => 'The Guardian Fashion',
                'url' => 'https://www.theguardian.com/fashion/rss',
                'subcategory' => 'fashion',
            ],

            // Food
            [
                'name' => 'The Guardian Food',
                'url' => 'https://www.theguardian.com/food/rss',
                'subcategory' => 'food',
            ],
        ];

        $subcategory_ids = Subcategory::pluck('id', 'slug');

        foreach ($sources as $source) {
            $subcategory_id = $subcategory_ids[$source['subcategory']] ?? null;

            if (!$subcategory_id) {
                continue;
            }

            RssSource::updateOrCreate(
                ['url' => $source['url']],
                [
                    'name' => $source['name'],
                    'subcategory_id' => $subcategory_id,
                    'active' => true,
                ]
            );
        }
    }
}
