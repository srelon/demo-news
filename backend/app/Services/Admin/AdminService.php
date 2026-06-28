<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminModeratorAccount;
use App\Models\Admin\AdminUsers;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class AdminService
{
    public function __construct(private readonly ImageService $image_service) {}

    public function list(int $per_page, ?string $search)
    {
        $query = AdminUsers::select([
            'admin_users.id',
            'admin_rules.name as rule',
            'admin_rules.id as rule_id',
            'admin_users.img',
            'admin_users.name',
            'admin_users.email',
            'admin_users.created_at',
        ])->join('admin_rules', 'admin_rules.id', '=', 'admin_users.rule_id');

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
        }

        return $query->paginate($per_page)->setPath(route('admin.admins'));
    }

    public function find(int $id): mixed
    {
        return AdminUsers::getProfile($id);
    }

    public function edit(int $id, array $data, ?UploadedFile $img): mixed
    {
        $admin = AdminUsers::find($id);

        if (!$admin) {
            return null;
        }

        if ($img) {
            $admin->img = $this->image_service->upload($img, 'admins');
        }

        if ($data['password'] ?? null) {
            $admin->password = Hash::make($data['password']);
        }

        if ($data['name'] ?? null) {
            $admin->name = $data['name'];
        }

        $admin->rule_id = $data['rule_id'];
        $admin->status = $data['status'];
        $admin->allowed_ip = $data['allowed_ip'] ?? null;
        $admin->save();

        return AdminUsers::getProfile($id);
    }

    public function searchUsers(string $q, int $admin_id): mixed
    {
        $existing_ids = AdminModeratorAccount::where('admin_user_id', $admin_id)
            ->pluck('user_id');

        return User::select(['id', 'name', 'username', 'img', 'email'])
            ->whereNotIn('id', $existing_ids)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get();
    }

    public function create(array $data, ?UploadedFile $img): mixed
    {
        $img_path = $img ? $this->image_service->upload($img, 'admins') : null;

        $admin = AdminUsers::create([
            'password' => Hash::make($data['password']),
            'name' => $data['name'],
            'email' => $data['email'],
            'img' => $img_path,
            'rule_id' => $data['rule_id'],
        ]);

        return AdminUsers::getProfile($admin->id);
    }
}
