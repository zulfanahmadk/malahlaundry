<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\RecordsController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreSettingsController;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public Auth
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // Authenticated Sync Endpoints (Sanctum Bearer Token)
    Route::middleware(['auth:sanctum', 'active', 'branch'])->group(function () {
        Route::get('/auth/user', [AuthController::class, 'user']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::post('/settings', [StoreSettingsController::class, 'update']);
        Route::post('/users/{user}', [UserController::class, 'update']);

        Route::get('/branches', [\App\Http\Controllers\Api\BranchController::class, 'index']);
        Route::post('/branches', [\App\Http\Controllers\Api\BranchController::class, 'save']);
        Route::post('/branches/{branch}', [\App\Http\Controllers\Api\BranchController::class, 'save']);

        // Sync Endpoints
        Route::post('/sync/push', [SyncController::class, 'push']);
        Route::post('/sync/upload', [SyncController::class, 'upload']);
        Route::get('/sync/pull', [SyncController::class, 'pull']);
        Route::get('/sync/records', [RecordsController::class, 'index']);
        Route::post('/sync/device', [\App\Http\Controllers\Api\DeviceSyncController::class, 'report']);
    });
});
