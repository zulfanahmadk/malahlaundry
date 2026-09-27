<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public Auth
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // Authenticated Sync Endpoints (Sanctum Bearer Token)
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('/auth/user', [AuthController::class, 'user']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Sync Endpoints
        Route::post('/sync/push', [SyncController::class, 'push']);
        Route::post('/sync/upload', [SyncController::class, 'upload']);
        Route::get('/sync/pull', [SyncController::class, 'pull']);
    });
});
