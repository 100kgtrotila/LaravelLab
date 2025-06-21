<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Blog\PostController;
use App\Http\Controllers\Api\Blog\CategoryController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Blog Posts API
Route::prefix('blog')->group(function () {
    Route::apiResource('posts', PostController::class);
    Route::apiResource('categories', CategoryController::class);

    // Додаткові роути
    Route::get('categories/{slug}/posts', [CategoryController::class, 'posts']);
    Route::get('categories-all', [CategoryController::class, 'all']); // Для селектів
    Route::get('categories/{slug}/posts', [CategoryController::class, 'posts']); // Пости категорії

    // Categories - основні CRUD маршрути
    Route::apiResource('categories', CategoryController::class);
});
