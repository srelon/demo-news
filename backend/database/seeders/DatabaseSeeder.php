<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\AdminSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

//        User::factory()->create([
//            'name' => 'Test User',
//            'email' => 'test@example.com',
//        ]);
//

        $this->call(AccessesSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(UserSeeder::class);

        // Site content — order matters (FK dependencies)
        $this->call(CategorySeeder::class);
        $this->call(TagSeeder::class);
        $this->call(ArticleSeeder::class);
        $this->call(FeaturedBlockSeeder::class);
        $this->call(NavSeeder::class);
        $this->call(PageSeeder::class);
        $this->call(RssSourceSeeder::class);
    }
}
