<?php

use App\Http\Middleware\Admin\AdminMiddleware;
use App\Http\Middleware\Admin\AdminGuestMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\ModeratorAuth;
use App\Http\Middleware\OptionalModeratorAuth;
use App\Http\Middleware\UserMiddleware;
use App\Http\Middleware\CheckAdminAccess;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/auth.php'));
            Route::middleware(['api'])
                ->prefix('api/admin')
                ->group(base_path('routes/admin.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        $middleware->statefulApi();
        $middleware->append(StartSession::class);

        $middleware->alias([
            'user' => UserMiddleware::class,
            'admin' => AdminMiddleware::class,
            'admin.guest' => AdminGuestMiddleware::class,
            'guest' => GuestMiddleware::class,
            'access' => CheckAdminAccess::class,
            'moderator.auth' => ModeratorAuth::class,
            'moderator.auth.optional' => OptionalModeratorAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 404, 'message' => 'Not found'], 404);
            }
        });
    })->create();
