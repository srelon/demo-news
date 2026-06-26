<?php

namespace App\Http\Middleware\Admin;

use App\Traits\RespondTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminGuestMiddleware
{
    use RespondTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Auth::guard('admin')->check()) {
            return $this->respondWithError([
                "message"=> __('Already Authenticated')
            ], 403);
        }


        return $next($request);
    }
}
