<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvantoController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::prefix('v1')->group(function () {
        Route::apiResource('avanto', AvantoController::class);
        Route::get('/stats', [StatsController::class, 'stats']);
    });
});
