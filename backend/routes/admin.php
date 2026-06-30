<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\AdminsController;
use App\Http\Controllers\Admin\AdminNotificationsController;
use App\Http\Controllers\Admin\DebugController;
use App\Http\Controllers\Admin\TestsController;
use App\Http\Controllers\Admin\CategoriesController;
use App\Http\Controllers\Admin\ArticlesController;
use App\Http\Controllers\Admin\TagsController;
use App\Http\Controllers\Admin\RssSourcesController;
use App\Http\Controllers\Admin\PagesController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\CommentsController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Site\CommentController as SiteCommentController;

Route::post('/login', [AuthController::class, 'login'])->name('admin.login')->middleware('admin.guest');
Route::post('/logout', [AuthController::class, 'destroy'])->name('admin.logout')->middleware('admin');

Route::get('/info', [DashboardController::class, 'admin']);
Route::get('/stats', [DashboardController::class, 'stats']);

Route::prefix('users')->controller(UsersController::class)->group(function () {
    Route::post('/', 'users')->name('admin.users')->middleware('access:users.view');
    Route::get('/{id}', 'info')->middleware('access:users.view');
    Route::post('/edit/{id}', 'edit')->middleware('access:users.edit');
    Route::post('/logs/{id}', 'logs')->middleware('access:users.view');
    Route::post('/login/{id}', 'loginAsUser')->middleware('access:users.edit');
});

Route::prefix('admins')->controller(AdminsController::class)->group(function () {
    Route::post('/', 'admins')->name('admin.admins')->middleware('access:admins.view');
    Route::get('/info/{id}', 'info')->middleware('access:admins.view');
    Route::post('/logs/{id}', 'logs')->middleware('access:admins.view');
    Route::get('/rules', 'rules')->middleware('access:admins.view');
    Route::post('/rules/create', 'rulesCreate')->middleware('access:admins.edit');
    Route::post('/create', 'create')->middleware('access:admins.edit');
    Route::post('/edit/{id}', 'edit')->middleware('access:admins.edit');
    Route::get('/accesses', 'accessesList')->middleware('access:admins.view');
    Route::get('/accesses/info/{id}', 'accesses')->middleware('access:admins.view');
    Route::post('/accesses/create', 'accessesCreate')->middleware('access:admins.edit');
    Route::post('/accesses/edit/{id}', 'accessesEdit')->middleware('access:admins.edit');
});

Route::prefix('categories')->controller(CategoriesController::class)->group(function () {
    Route::get('/all', 'all')->middleware('access:categories.view');
    Route::post('/', 'list')->middleware('access:categories.view');
    Route::post('/reorder', 'reorder')->middleware('access:categories.edit');
    Route::get('/{id}', 'info')->middleware('access:categories.view');
    Route::post('/create', 'create')->middleware('access:categories.edit');
    Route::post('/edit/{id}', 'edit')->middleware('access:categories.edit');
    Route::post('/delete/{id}', 'delete')->middleware('access:categories.edit');
    Route::post('/{category_id}/subcategory/create', 'subcategoryCreate')->middleware('access:categories.edit');
});


Route::prefix('subcategory')->controller(CategoriesController::class)->group(function () {
    Route::post('/edit/{id}', 'subcategoryEdit')->middleware('access:categories.edit');
    Route::post('/delete/{id}', 'subcategoryDelete')->middleware('access:categories.edit');
    Route::post('/reorder', 'subcategoryReorder')->middleware('access:categories.edit');
});

Route::prefix('articles')->controller(ArticlesController::class)->group(function () {
    Route::post('/', 'list')->middleware('access:articles.view');
    Route::get('/options', 'formOptions')->middleware('access:articles.view');
    Route::get('/source-options', 'sourceOptions')->middleware('access:articles.view');
    Route::get('/duplicates', 'duplicates')->middleware('access:articles.view');
    Route::post('/duplicates/clean', 'cleanDuplicates')->middleware('access:articles.edit');
    Route::get('/subcategory-options', 'subcategoryOptions')->middleware('access:articles.view');
});

Route::prefix('article')->controller(ArticlesController::class)->group(function () {
    Route::get('/{id}', 'info')->middleware('access:articles.view');
    Route::post('/create', 'create')->middleware('access:articles.edit');
    Route::post('/edit/{id}', 'edit')->middleware('access:articles.edit');
    Route::post('/refresh/{id}', 'refresh')->middleware('access:articles.edit');
    Route::post('/delete/{id}', 'delete')->middleware('access:articles.edit');
});

Route::prefix('tags')->controller(TagsController::class)->group(function () {
    Route::post('/', 'list')->middleware('access:tags.view');
});

Route::prefix('tag')->controller(TagsController::class)->group(function () {
    Route::get('/{id}', 'info')->middleware('access:tags.view');
    Route::post('/create', 'create')->middleware('access:tags.edit');
    Route::post('/edit/{id}', 'edit')->middleware('access:tags.edit');
    Route::post('/delete/{id}', 'delete')->middleware('access:tags.edit');
});

Route::prefix('rss')->controller(RssSourcesController::class)->group(function () {
    Route::get('/', 'list')->middleware('access:rss.view');
    Route::get('/options', 'options')->middleware('access:rss.view');
    Route::post('/items', 'items')->middleware('access:rss.view');
    Route::post('/retry/{id}', 'retry')->middleware('access:articles.edit');
    Route::post('/items/delete/{id}', 'deleteItem')->middleware('access:articles.edit');
    Route::post('/items/delete-rejected', 'deleteRejected')->middleware('access:articles.edit');
    Route::post('/refresh-articles', 'refreshArticles')->middleware('access:rss.edit');
    Route::post('/retry-all', 'retryAll')->middleware('access:articles.edit');
    Route::post('/fetch', 'fetch')->middleware('access:rss.edit');
    Route::post('/create', 'create')->middleware('access:rss.edit');
    Route::post('/edit/{id}', 'edit')->middleware('access:rss.edit');
    Route::post('/toggle/{id}', 'toggle')->middleware('access:rss.edit');
    Route::post('/delete/{id}', 'delete')->middleware('access:rss.edit');
});

Route::prefix('pages')->controller(PagesController::class)->group(function () {
    Route::get('/', 'list')->middleware('access:articles.view');
});

Route::prefix('page')->controller(PagesController::class)->group(function () {
    Route::get('/{id}', 'info')->middleware('access:articles.view');
    Route::post('/create', 'create')->middleware('access:articles.edit');
    Route::post('/edit/{id}', 'edit')->middleware('access:articles.edit');
});

// Comment lists are visible to every admin, mutations require moderator.edit
Route::prefix('comments')->group(function () {
    Route::get('/recent', [CommentsController::class, 'recent']);
    Route::post('/', [CommentsController::class, 'list']);
    Route::get('/article/{article_id}', [SiteCommentController::class, 'index'])->middleware('moderator.auth.optional');
    Route::get('/{comment_id}/replies', [SiteCommentController::class, 'replies'])->middleware('moderator.auth.optional');
});

Route::prefix('comment')->group(function () {
    Route::get('/{id}/reports', [CommentsController::class, 'reports']);
    Route::get('/{id}', [CommentsController::class, 'show']);
    Route::post('/{id}/like', [SiteCommentController::class, 'like'])->middleware(['access:moderator.view', 'moderator.auth']);
    Route::post('/delete/{id}', [CommentsController::class, 'delete'])->middleware('access:moderator.edit');
    Route::post('/restore/{id}', [CommentsController::class, 'restore'])->middleware('access:moderator.edit');
    Route::post('/{id}/edit', [CommentsController::class, 'edit'])->middleware('access:moderator.edit');
    Route::post('/reply', [SiteCommentController::class, 'store'])->middleware(['access:moderator.view', 'moderator.auth']);
});

Route::prefix('profile')->controller(ProfileController::class)->middleware('admin')->group(function () {
    Route::get('/', 'info');
    Route::post('/', 'update');
    Route::get('/users/search', 'userSearch')->middleware('access:moderator.view');
    Route::get('/moderators', 'moderators')->middleware('access:moderator.view');
    Route::post('/moderators', 'moderatorAdd')->middleware('access:moderator.edit');
    Route::delete('/moderators/{user_id}', 'moderatorRemove')->middleware('access:moderator.edit');
});

Route::prefix('session/moderator')->controller(SessionController::class)->group(function () {
    Route::get('/', 'getModerator');
    Route::post('/', 'setModerator')->middleware('admin');
    Route::delete('/', 'clearModerator')->middleware('admin');
    Route::get('/accounts', 'moderatorAccounts')->middleware('admin');
});

Route::prefix('upload')->controller(UploadController::class)->group(function () {
    Route::post('/image', 'image')->middleware('access:articles.edit');
});

Route::prefix('notifications')->controller(AdminNotificationsController::class)->group(function () {
    Route::get('/', 'recent')->middleware('access:moderator.view');
    Route::get('/unread-count', 'unreadCount')->middleware('access:moderator.view');
    Route::get('/all', 'all')->middleware('access:moderator.view');
    Route::post('/mark-read/{id}', 'markRead')->middleware('access:moderator.view');
});

Route::prefix('debug')->controller(DebugController::class)->group(function () {
    Route::post('/', 'debug')->middleware('access:debug.view');
    Route::get('/unread-count', 'unreadCount')->middleware('access:debug.view');
    Route::post('/mark-seen', 'markSeen')->middleware('access:debug.view');
});

Route::prefix('tests')->controller(TestsController::class)->group(function () {
    Route::get('/list', 'list')->middleware('access:debug.view');
    Route::post('/run', 'run')->middleware('access:debug.view');
});
