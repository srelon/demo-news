<?php

namespace App\Services;

use App\Models\Subscriber;

class SubscribeService
{
    public function subscribe(string $email): Subscriber
    {
        return Subscriber::firstOrCreate(
            ['email' => $email],
            ['status' => 'active']
        );
    }
}
