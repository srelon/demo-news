<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\Profile\UpdatePasswordRequest;
use App\Http\Requests\Site\Profile\UpdateProfileRequest;
use App\Models\User;
use App\Services\ImageService;
use App\Traits\RespondTrait;
use Illuminate\Http\JsonResponse;
use App\Models\Admin\AdminModeratorAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use RespondTrait;

    public function __construct(private readonly ImageService $image_service) {}

    public function show(): JsonResponse
    {
        if (!Auth::guard('web')->check()) {
            return $this->respondWithJson(['user' => null]);
        }

        $user = User::getProfile(Auth::user()->public_id);
        $is_moderator = $user && AdminModeratorAccount::where('user_id', $user->id)->exists();

        return $this->respondWithJson([
            'user' => array_merge($user->toArray(), ['is_moderator' => $is_moderator]),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = User::where('public_id', Auth::user()->public_id)->firstOrFail();

        $user->name = $request->input('name');

        if ($request->filled('username')) {
            $user->username = $request->input('username');
        }

        if ($request->hasFile('img')) {
            $user->img = $this->image_service->upload($request->file('img'), 'users');
        }

        $user->save();

        return $this->respondWithJson([
            'user' => User::getProfile($user->public_id),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = User::where('public_id', Auth::user()->public_id)->firstOrFail();

        if (!Hash::check($request->input('old_password'), $user->password)) {
            return $this->respondWithError(['old_password' => ['Current password is incorrect']], 422);
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        return $this->respondWithJson(['message' => 'Password updated']);
    }
}
