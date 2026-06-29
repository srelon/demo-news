<?php

use App\Http\Controllers\Site\CommentController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LayoutController;
use App\Http\Controllers\Site\NewsController;
use App\Http\Controllers\Site\NotificationController;
use App\Http\Controllers\Site\OgPreviewController;
use App\Http\Controllers\Site\ProfileController;
use App\Http\Controllers\Site\SubscribeController;
use Illuminate\Support\Facades\Route;

Route::get('/og-preview', OgPreviewController::class);

Route::get('/layout', [LayoutController::class, 'index']);
Route::get('/home', [HomeController::class, 'index']);

Route::prefix('news/{category}')->controller(NewsController::class)->group(function () {
    Route::get('/', 'category');
    Route::get('/{subcategory}', 'subcategoryNews');
    Route::get('/{subcategory}/articles', 'subcategoryArticles');
    Route::get('/{subcategory}/{slug}', 'article');
});

Route::get('/page/{slug}', [NewsController::class, 'page']);
Route::post('/subscribe', [SubscribeController::class, 'store']);
Route::get('/search', [NewsController::class, 'search']);
Route::get('/tags/{slug}', [NewsController::class, 'tag']);
Route::get('/archive/{year}/{month}', [NewsController::class, 'archive']);

Route::prefix('profile')->controller(ProfileController::class)->group(function () {
    Route::get('/', 'show')->name('profile');

    Route::middleware('user')->group(function () {
        Route::post('/update', 'update')->name('profile.update');
        Route::post('/password', 'updatePassword')->name('profile.password');
    });
});

Route::prefix('comments')->controller(CommentController::class)->group(function () {
    Route::get('/{article_id}', 'index');
    Route::get('/{comment_id}/replies', 'replies');

    Route::middleware('user')->group(function () {
        Route::post('/', 'store');
        Route::patch('/{comment_id}', 'update');
        Route::delete('/{comment_id}', 'destroy');
        Route::post('/{comment_id}/like', 'like');
        Route::post('/{comment_id}/report', 'report');
        Route::delete('/{comment_id}/moderate', 'moderatorDestroy');
        Route::patch('/{comment_id}/moderate', 'moderatorUpdate');
        Route::post('/{comment_id}/restore', 'moderatorRestore');
    });
});

Route::prefix('notifications')->controller(NotificationController::class)->middleware('user')->group(function () {
    Route::get('/', 'index');
    Route::get('/all', 'all');
    Route::get('/unread-count', 'unreadCount');
    Route::patch('/{id}/read', 'markRead');
});
