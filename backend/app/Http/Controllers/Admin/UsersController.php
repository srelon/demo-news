<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\User\UserEditRequest;
use App\Services\Admin\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function edit(string $id, UserEditRequest $request): JsonResponse
    {
        $user = $this->user_service->edit($id, $request->validated(), $request->file('img'));

        if (!$user) {
            return $this->respondWithError(['message' => __('User no found')], 404);
        }

        return $this->respondWithJson($user);
    }
}
