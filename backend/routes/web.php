<?php

use App\Http\Controllers\Site\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('{any}', SpaController::class)
    ->where('any', '.*')
    ->withoutMiddleware([
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    ]);
