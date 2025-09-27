<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\AvantoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::prefix('v1')->group(function () {
        Route::apiResource('avanto', AvantoController::class);
        Route::get('avanto/user/{user_id}', [AvantoController::class, 'getUserAvantos']);
    });
});
