<?php

use App\Http\Controllers\ClapController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicProfileController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

Route::get('/', function () {
    return view('welcome');
});

/**
 * @OA\Get(
 *   path="/debug/session",
 *   tags={"Debug"},
 *   summary="Inspect session configuration and ID",
 *   description="Returns the current session ID and session/redis configuration.",
 *   @OA\Response(response=200, description="Successful response")
 * )
 */
// Debug route to inspect session ID and configuration
Route::get('/debug/session', function (Request $request) {
    // Touch the session to ensure it's created and persisted
    $request->session()->put('debug_ping', now()->toISOString());

    return response()->json([
        'session_id' => $request->session()->getId(),
        'driver' => config('session.driver'),
        'connection' => config('session.connection'),
        'store' => config('session.store'),
        'redis_prefix' => data_get(config('database.redis.options'), 'prefix'),
        'redis_host' => config('database.redis.default.host'),
        'redis_port' => config('database.redis.default.port'),
    ]);
});

/**
 * @OA\Get(
 *   path="/debug/auth-cache",
 *   tags={"Debug"},
 *   summary="Check DB query count for auth user",
 *   description="Returns the authenticated user's ID, current session ID, and number of DB queries executed when resolving the user.",
 *   @OA\Response(response=200, description="Successful response")
 * )
 */
Route::middleware('auth')->get('/debug/auth-cache', function (Request $request) {
    DB::flushQueryLog();
    DB::enableQueryLog();

    // Access the authenticated user; should be served from cache after first hit.
    $user = $request->user();

    $queries = DB::getQueryLog();

    return response()->json([
        'username' => optional($user)->username,
        'user_id' => optional($user)->id,
        'session_id' => $request->session()->getId(),
        'DB_query_count' => count($queries),
        'queries' => $queries,
    ]);
});

Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.token');

Route::get('/@{user:username}', [PublicProfileController::class, 'show'])
    ->name('profile.show');

Route::get('/', [PostController::class, 'index'])
    ->name('dashboard');

Route::get('/@{username}/{post:slug}', [PostController::class, 'show'])
    ->name('post.show');

Route::get('/category/{category}', [PostController::class, 'category'])
    ->name('post.byCategory');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/post/create', [PostController::class, 'create'])
        ->name('post.create');

    Route::post('/post/create', [PostController::class, 'store'])
        ->name('post.store');

    Route::get('/post/{post:slug}', [PostController::class, 'edit'])
        ->name('post.edit');

    Route::put('/post/{post}', [PostController::class, 'update'])
        ->name('post.update');

    Route::delete('/post/{post}', [PostController::class, 'destroy'])
        ->name('post.destroy');

    Route::get('/my-posts', [PostController::class, 'myPosts'])
        ->name('myPosts');

    Route::post('/follow/{user}', [FollowerController::class, 'followUnfollow'])
        ->name('follow');

    Route::post('/clap/{post}', [ClapController::class, 'clap'])
        ->name('clap');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
