<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(static function () {
    Route::group(['prefix' => 'auth'], static function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

});

Route::group(['prefix' => 'products'], static function () {
    Route::post('create', [ProductController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/search', [ProductController::class, 'search']);
});

Route::group(['prefix' => 'orders', 'middleware' => 'auth:sanctum'], static function () {
    Route::get('/{order}', [OrderController::class, 'show']);
    Route::post('create', [OrderController::class, 'store']);
});
