<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth:api')->get('/user-profile', function() {
    return auth()->user();
});
