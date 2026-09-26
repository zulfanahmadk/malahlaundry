<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\NotaPublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication Web
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public Paperless Digital Receipt (Tanpa Auth, UUIDv4)
Route::get('/n/{uuid}', [NotaPublicController::class, 'show'])->name('nota.public');

// Web Dashboard Owner & Admin (Protected)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Master Services
    Route::get('/services', [DashboardController::class, 'services'])->name('services.index');
    Route::post('/services', [DashboardController::class, 'storeService'])->name('services.store');
    Route::post('/services/{uuid}', [DashboardController::class, 'updateService'])->name('services.update');

    // Management Users / Kasir
    Route::get('/users', [DashboardController::class, 'users'])->name('users.index');
    Route::post('/users', [DashboardController::class, 'storeUser'])->name('users.store');
    Route::post('/users/{id}/toggle', [DashboardController::class, 'toggleUser'])->name('users.toggle');

    // Transactions History
    Route::get('/transactions', [DashboardController::class, 'transactions'])->name('transactions.index');

    // Attendances & Selfie Monitoring
    Route::get('/attendances', [DashboardController::class, 'attendances'])->name('attendances.index');

    // Web-Exclusive DLP Export (Excel/CSV)
    Route::get('/reports/export', [DashboardController::class, 'exportReports'])->name('reports.export');
});
