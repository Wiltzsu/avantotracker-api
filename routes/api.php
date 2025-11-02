<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\AvantoController;
use App\Http\Controllers\StatsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
});

/**
 * Protected API routes (auth:sanctum).
 * All nested `v1/*` endpoints require a valid Sanctum token; unauthenticated requests get 401.
 * Registers RESTful `avanto` routes (index, store, show, update, destroy).
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    /**
     * Register all five REST routes:
     *
     * GET /api/v1/avanto -> list all
     * POST /api/v1/avanto -> store
     * GET /api/v1/avanto/{avanto} -> show
     * PUT /api/v1/avanto/{avanto} -> update
     * DELETE /api/v1/avanto/{avanto} -> destroy
     */
    Route::prefix('v1')->group(function () {
        Route::apiResource('avanto', AvantoController::class);
        // User stats page
        Route::get('/stats', [StatsController::class, 'stats']);
    });

});
