<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Profile\ModeratorAddRequest;
use App\Http\Requests\Admin\Profile\ProfileUpdateRequest;
use App\Models\Admin\AdminUsers;
use App\Models\User;
use App\Services\Admin\AdminService;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ImageService $image_service,
        private readonly AdminService $admin_service,
    ) {}

    public function info(): JsonResponse
    {
        $admin = AdminUsers::select(['id', 'rule_id', 'name', 'email', 'img', 'status', 'created_at', 'allowed_ip'])
            ->find(Auth::guard('admin')->id());

        return $this->respondWithJson(['admin' => $admin]);
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $admin = AdminUsers::find(Auth::guard('admin')->id());

        if ($request->file('img')) {
            $admin->img = $this->image_service->upload($request->file('img'), 'admins');
        }

        if ($request->validated()['password'] ?? null) {
            $admin->password = Hash::make($request->validated()['password']);
        }

        if ($request->validated()['name'] ?? null) {
            $admin->name = $request->validated()['name'];
        }

        if (array_key_exists('allowed_ip', $request->validated())) {
            $admin->allowed_ip = $request->validated()['allowed_ip'];
        }

        $admin->save();

        $updated = AdminUsers::select(['id', 'rule_id', 'name', 'email', 'img', 'status', 'created_at', 'allowed_ip'])
            ->find($admin->id);

        return $this->respondWithJson(['admin' => $updated]);
    }

    public function moderators(): JsonResponse
    {
        $admin = AdminUsers::find(Auth::guard('admin')->id());

        $accounts = $admin->moderatorAccounts()
            ->select(['users.id', 'users.name', 'users.username', 'users.img', 'users.email'])
            ->get();

        return $this->respondWithJson(['accounts' => $accounts]);
    }

    public function moderatorAdd(ModeratorAddRequest $request): JsonResponse
    {
        $user = User::findOrFail($request->user_id);

        if (!Hash::check($request->password, $user->password)) {
            return $this->respondWithError('Incorrect password', 422);
        }

        $admin = AdminUsers::find(Auth::guard('admin')->id());
        $admin->moderatorAccounts()->syncWithoutDetaching([$user->id]);

        $user_data = User::select(['id', 'name', 'username', 'img', 'email'])->find($user->id);

        return $this->respondWithJson(['user' => $user_data]);
    }

    public function moderatorRemove(int $user_id): JsonResponse
    {
        $admin = AdminUsers::find(Auth::guard('admin')->id());
        $admin->moderatorAccounts()->detach($user_id);

        return $this->respondWithJson(['ok' => true]);
    }

    public function userSearch(Request $request): JsonResponse
    {
        $q = trim($request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return $this->respondWithJson(['users' => []]);
        }

        $users = $this->admin_service->searchUsers($q, Auth::guard('admin')->id());

        return $this->respondWithJson(['users' => $users]);
    }
}
