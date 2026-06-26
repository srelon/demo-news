<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\NavItem;
use App\Models\NavTab;
use Illuminate\Database\Seeder;

class NavSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::with('subcategories')->orderBy('order')->get();

        foreach ($categories as $category) {
            $navItem = NavItem::create([
                'category_id' => $category->id,
                'order' => $category->order,
            ]);

            foreach ($category->subcategories as $sub) {
                NavTab::create([
                    'nav_item_id' => $navItem->id,
                    'subcategory_id' => $sub->id,
                    'order' => $sub->order,
                ]);
            }
        }
    }
}
