<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModeratorAuth
{
    public function handle(Request $request, Closure $next)
    {
        $moderator_user_id = session('moderator_user_id');

        if (!$moderator_user_id) {
            return response()->json(['status' => 403, 'errors' => 'No moderator account selected'], 403);
        }

        $user = User::find($moderator_user_id);

        if (!$user) {
            return response()->json(['status' => 403, 'errors' => 'Moderator account not found'], 403);
        }

        // Set user for this request only — does not touch the session
        Auth::guard('web')->setUser($user);

        return $next($request);
    }
}
