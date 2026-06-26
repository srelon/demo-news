<?php

namespace App\Http\Middleware;

use App\Traits\RespondTrait;
use Closure;
use Illuminate\Http\Request;
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
