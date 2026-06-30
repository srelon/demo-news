<?php

namespace App\Jobs;

use App\Models\Admin\AdminUsers;
use Illuminate\Support\Facades\Hash;

class DemoAdminResetJob
{
    public function handle(): void
    {
        $email = config('demo.admin_email');

        if (!$email) {
            return;
        }

        AdminUsers::where('email', $email)->update([
            'name' => config('demo.admin_name'),
            'password' => Hash::make(config('demo.admin_password')),
            'img' => null,
            'allowed_ip' => null,
        ]);
    }
}
