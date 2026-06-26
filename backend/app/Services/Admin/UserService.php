<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(private readonly ImageService $image_service) {}

    public function list(int $per_page, ?string $search)
    {
        $query = User::select(['public_id as id', 'img', 'name', 'email', 'created_at']);

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
        }

        return $query->paginate($per_page)->setPath(route('admin.users'));
    }

    public function find(string $id): mixed
    {
        return User::getProfile($id) ?: null;
    }

    public function edit(string $id, array $data, ?UploadedFile $img): mixed
    {
        $user = User::where('public_id', $id)->first();

        if (!$user) {
            return null;
        }

        if ($img) {
            $user->img = $this->image_service->upload($img, 'users');
        }

        if ($data['password'] ?? null) {
            $user->password = Hash::make($data['password']);
        }

        if ($data['name'] ?? null) {
            $user->name = $data['name'];
        }

        $user->save();

        return User::getProfile($id);
    }
}
