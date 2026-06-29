<?php

namespace App\Http\Middleware;

use App\Traits\RespondTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminAccess
{
    use RespondTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permissions): Response
    {

        $user = auth('admin')->user();

        if (empty($user)) {
            return $this->respondWithError([
                "message"=> __('Access denied')
            ], 403);
        }

        if ($user->status !== 1) {
            Auth::guard('admin')->logout();
            return $this->respondWithError([
                "message" => 'Account is not active.',
            ], 401);
        }

        $accesses = $user->rule->accesses_id ?? [];
        [$module, $action] = explode('.', $permissions);

        if (
            isset($accesses[$module][$action]) &&
            $accesses[$module][$action]
        ) {
            return $next($request);
        }

        return $this->respondWithError([
            "message"=> __('Access denied')
        ], 403);
    }
}
