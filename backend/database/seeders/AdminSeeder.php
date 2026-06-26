<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Admin Rules
        $accesses_id = DB::table('admin_accesses')
            ->pluck('key')
            ->mapWithKeys(fn($key) => [$key => ['view' => true, 'edit' => true]])
            ->toArray();

        DB::table('admin_rules')->updateOrInsert(
            ['name' => 'Admin'],
            [
                'active' => 1,
                'accesses_id' => json_encode($accesses_id),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $rule_id = DB::table('admin_rules')->where('name', 'Admin')->value('id');

        // Admin User
        DB::table('admin_users')->updateOrInsert(
            ['email' => 'admin@gmail.com'],
            [
                'rule_id' => $rule_id,
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('123456789'),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
