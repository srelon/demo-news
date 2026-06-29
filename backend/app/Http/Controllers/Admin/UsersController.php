<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\User\UserEditRequest;
use App\Models\User;
use App\Models\UserLog;
use App\Services\Admin\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UsersController extends Controller
{
    public function __construct(private readonly UserService $user_service) {}

    public function users(Request $request): JsonResponse
    {
        $users = $this->user_service->list(
            $request->per_page ?? 30,
            $request->search ?? null,
        );

        return $this->respondWithJson($users);
    }

    public function info(string $id): JsonResponse
    {
        $user = $this->user_service->find($id);

        if (!$user) {
            return $this->respondWithError(['message' => __('User no found')], 404);
        }

        return $this->respondWithJson($user);
    }

    public function logs(int $id, Request $request): JsonResponse
    {
        $logs = UserLog::where('user_id', $id)
            ->select(['id', 'user_id', 'ip', 'browser', 'created_at'])
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return $this->respondWithJson($logs);
    }

    public function loginAsUser(string $id): JsonResponse
    {
        $user = User::where('public_id', $id)->first();

        if (!$user) {
            return $this->respondWithError(['message' => __('User not found')], 404);
        }

        Session::put(Auth::guard('web')->getName(), $user->id);

        return $this->respondWithJson(['ok' => true]);
    }

    public function edit(string $id, UserEditRequest $request): JsonResponse
    {
        $user = $this->user_service->edit($id, $request->validated(), $request->file('img'));

        if (!$user) {
            return $this->respondWithError(['message' => __('User no found')], 404);
        }

        return $this->respondWithJson($user);
    }
}
