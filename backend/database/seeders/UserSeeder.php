<?php

namespace Database\Seeders;

use App\Traits\GlobalTrait;
use App\Traits\RespondTrait;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    use RespondTrait, GlobalTrait;
    public function run(): void
    {
        $users = [
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ],
            [
                'name' => 'James Carter',
                'email' => 'james@example.com',
            ],
            [
                'name' => 'Sarah Mitchell',
                'email' => 'sarah@example.com',
            ],
            [
                'name' => 'David Chen',
                'email' => 'david@example.com',
            ],
            [
                'name' => 'Emily Rodriguez',
                'email' => 'emily@example.com',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'public_id' => $this->createPublicId('users'),
                    'password' => Hash::make('123456789'),
                ]
            )->createUserData();
        }
    }
}
