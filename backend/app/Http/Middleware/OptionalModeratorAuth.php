<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OptionalModeratorAuth
{
    /**
     * Same as ModeratorAuth but does not block if no moderator account is selected.
     * Used for read-only endpoints where user_reaction should reflect the acting moderator
     * when one is selected, but the request should still succeed without one.
     */
    public function handle(Request $request, Closure $next)
    {
        $moderator_user_id = session('moderator_user_id');

        if ($moderator_user_id) {
            $user = User::find($moderator_user_id);
            if ($user) {
                Auth::guard('web')->setUser($user);
            }
        }

        return $next($request);
    }
}
