<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// routes/web.php
Route::get('/', function () {
    try {
        DB::connection()->getPdo();
        $db = 'ok';
    } catch (\Exception $e) {
        $db = 'error';
    }

    return response()->json([
        'message' => 'AvantoTracker API',
        'status' => $db === 'ok' ? 'healthy' : 'degraded',
        'timestamp' => now()->toISOString()
    ]);
})->withoutMiddleware(['web']);
