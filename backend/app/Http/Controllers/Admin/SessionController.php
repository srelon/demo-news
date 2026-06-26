<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Session\SetModeratorRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function getModerator(): JsonResponse
    {
        $user_id = session('moderator_user_id');

        if (!$user_id) {
            return $this->respondWithJson(['user' => null]);
        }

        $user = User::select(['id', 'name', 'username', 'img'])->find($user_id);

        return $this->respondWithJson(['user' => $user]);
    }

    public function setModerator(SetModeratorRequest $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        $user_id = (int) $request->input('user_id');

        $has_access = $admin->moderatorAccounts()->where('users.id', $user_id)->exists();

        if (!$has_access) {
            return $this->respondWithError('Access denied to this account', 403);
        }

        session(['moderator_user_id' => $user_id]);

        $user = User::select(['id', 'name', 'username', 'img'])->find($user_id);

        return $this->respondWithJson(['user' => $user]);
    }

    public function clearModerator(): JsonResponse
    {
        session()->forget('moderator_user_id');

        return $this->respondWithJson(['ok' => true]);
    }

    public function moderatorAccounts(): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        $users = $admin->moderatorAccounts()
            ->select(['users.id', 'users.name', 'users.username', 'users.img'])
            ->get();

        return $this->respondWithJson(['users' => $users]);
    }
}
