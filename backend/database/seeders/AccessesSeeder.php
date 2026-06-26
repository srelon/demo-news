<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessesSeeder extends Seeder
{
    public function run(): void
    {
        $accesses = [
            [
                'key' => 'dashboard',
                'descriptions' => 'Dashboard',
            ],
            [
                'key' => 'admins',
                'descriptions' => 'Admins',
            ],
            [
                'key' => 'users',
                'descriptions' => 'Users',
            ],
            [
                'key' => 'categories',
                'descriptions' => 'Categories',
            ],
            [
                'key' => 'articles',
                'descriptions' => 'Articles',
            ],
            [
                'key' => 'tags',
                'descriptions' => 'Tags',
            ],
            [
                'key' => 'rss',
                'descriptions' => 'RSS Sources',
            ],
            [
                'key' => 'debug',
                'descriptions' => 'Debug',
            ],
            [
                'key' => 'moderator',
                'descriptions' => 'Moderator',
            ],
        ];

        // 'comments' was merged into 'moderator'
        DB::table('admin_accesses')->where('key', 'comments')->delete();

        foreach ($accesses as $access) {
            DB::table('admin_accesses')->updateOrInsert(
                ['key' => $access['key']],
                array_merge($access, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
