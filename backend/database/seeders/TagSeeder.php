<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public static function tagNames(): array
    {
        return [
            // News types
            'Breaking News', 'Exclusive', 'Interview', 'Analysis', 'Opinion',
            'Investigation', 'Report', 'Feature', 'Commentary', 'Review',

            // Topics
            'Politics', 'Economy', 'Business', 'Finance', 'Markets',
            'Technology', 'AI', 'Cybersecurity', 'Startups', 'Innovation',
            'Science', 'Space', 'Climate', 'Environment', 'Energy',
            'Health', 'Medicine', 'Mental Health', 'Nutrition', 'Fitness',
            'Sports', 'Football', 'Basketball', 'Tennis', 'Olympics',
            'Culture', 'Art', 'Music', 'Cinema', 'Literature',
            'Travel', 'Lifestyle', 'Fashion', 'Food', 'Architecture',
            'Society', 'Education', 'Crime', 'Law', 'Human Rights',
            'War', 'Diplomacy', 'Sanctions', 'Elections', 'Government',
            'Cryptocurrency', 'Blockchain', 'NFT', 'Web3', 'Social Media',
        ];
    }

    public function run(): void
    {
        $now = now();

        Tag::insertOrIgnore(
            collect(static::tagNames())->map(fn($name) => [
                'name' => $name,
                'slug' => Str::slug($name),
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray()
        );
    }
}
