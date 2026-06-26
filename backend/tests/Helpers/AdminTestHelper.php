<?php

namespace Tests\Helpers;

use App\Models\Admin\AdminRules;
use App\Models\Admin\AdminUsers;

trait AdminTestHelper
{
    protected function createAdminWithAccesses(array $accesses): AdminUsers
    {
        $rule = new AdminRules();
        $rule->name = 'Rule_' . uniqid();
        $rule->accesses_id = $accesses;
        $rule->save();

        return AdminUsers::create([
            'rule_id' => $rule->id,
            'name' => 'Test Admin',
            'email' => 'admin_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
        ]);
    }

    protected function adminWithView(string $module): AdminUsers
    {
        return $this->createAdminWithAccesses([
            $module => ['view' => true, 'edit' => false],
        ]);
    }

    protected function adminWithFull(string $module): AdminUsers
    {
        return $this->createAdminWithAccesses([
            $module => ['view' => true, 'edit' => true],
        ]);
    }

    protected function adminWithNoAccess(): AdminUsers
    {
        return $this->createAdminWithAccesses([]);
    }
}
