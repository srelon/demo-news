<?php

use App\Http\Controllers\Site\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('{any}', SpaController::class)->where('any', '.*');
