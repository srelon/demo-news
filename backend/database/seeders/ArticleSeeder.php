<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    private array $images = [];
    private array $tagIds = [];
    private array $authorIds = [];

    public function run(): void
    {
        $this->images = array_map(fn($i) => sprintf('/images/post-%02d.jpg', $i), range(1, 50));
        $this->tagIds = Tag::pluck('id')->toArray();
        $this->authorIds = User::pluck('id')->toArray();

        $subcategories = Subcategory::with('category')->get();
        $imageIndex = 0;

        foreach ($subcategories as $sub) {
            $count = match ($sub->slug) {
                'world', 'politics', 'technology' => 6,
                'movies', 'music' => 4,
                default => 3,
            };

            for ($i = 1; $i <= $count; $i++) {
                $image = $this->images[$imageIndex % count($this->images)];
                $imageIndex++;

                $title = $this->makeTitle($sub->name, $i);
                $slug = Str::slug($title) . '-' . Str::random(4);

                $article = Article::create([
                    'subcategory_id' => $sub->id,
                    'author_id' => $this->authorIds[array_rand($this->authorIds)] ?? null,
                    'title' => $title,
                    'slug' => $slug,
                    'excerpt' => $this->makeExcerpt($sub->name),
                    'body' => $this->makeBody($title),
                    'image' => $image,
                    'views' => rand(100, 9800),
                    'source_type' => 'manual',
                    'status' => 'published',
                    'published_at' => Carbon::now()->subDays(rand(1, 120))->subHours(rand(0, 23)),
                ]);

                $article->tags()->attach(
                    array_slice($this->tagIds, array_rand($this->tagIds), rand(2, 4))
                );
            }
        }
    }

    private function makeTitle(string $category, int $n): string
    {
        $templates = [
            "The Future of {cat}: What Experts Are Saying in 2025",
            "Breaking: Major Developments in {cat} Shake the Industry",
            "Top {n} Trends in {cat} You Shouldn't Miss This Year",
            "How {cat} Is Changing the Way We Think About the World",
            "Exclusive: Inside Look at the Latest {cat} Breakthroughs",
            "Why {cat} Matters More Than Ever in Today's Society",
        ];

        $template = $templates[($n - 1) % count($templates)];

        return str_replace(['{cat}', '{n}'], [$category, $n * 3], $template);
    }

    private function makeExcerpt(string $category): string
    {
        return "Discover the latest insights and developments in {$category}. "
            . "Our in-depth coverage brings you the most important stories, "
            . "expert analysis, and breaking news from around the world.";
    }

    private function makeBody(string $title): string
    {
        $paragraphs = [
            "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.",
            "Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
            "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt.",
            "At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.",
            "Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus.",
        ];

        return "<h2>{$title}</h2>\n\n"
            . implode("\n\n", array_map(fn($p) => "<p>{$p}</p>", $paragraphs));
    }
}
