<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvantoController;
use App\Http\Controllers\Api\AvantoExportController;
use App\Http\Controllers\Api\AvantoSelfieController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\RecordsController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::prefix('v1')->group(function () {
        Route::get('/avanto/export', [AvantoExportController::class, 'export']);
        Route::apiResource('avanto', AvantoController::class);
        Route::post('/avanto/{avanto}/selfie', [AvantoSelfieController::class, 'store']);
        Route::delete('/avanto/{avanto}/selfie', [AvantoSelfieController::class, 'destroy']);
        Route::get('/stats', [StatsController::class, 'stats']);
        Route::get('/dashboard', [DashboardController::class, 'show']);
        Route::get('/records', [RecordsController::class, 'show']);
    });
});
