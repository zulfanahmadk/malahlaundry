<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\WorkspaceController;
use App\Http\Controllers\Web\NotaPublicController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreSettingsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route(auth()->user()?->isAdmin() ? 'admin.dashboard' : 'dashboard');
});

// Authentication Web
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/store/logo', [StoreSettingsController::class, 'logo'])->name('store.logo');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth:web', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/audit', [\App\Http\Controllers\Web\AdminAuditController::class, 'index'])->name('audit.index');
    Route::get('/apk', [\App\Http\Controllers\Web\ApkReleaseController::class, 'index'])->name('apk.index');
    Route::post('/apk', [\App\Http\Controllers\Web\ApkReleaseController::class, 'upload'])->name('apk.upload');
    Route::post('/password', [\App\Http\Controllers\Web\ApkReleaseController::class, 'password'])->name('password');
});

// Public Paperless Digital Receipt (Tanpa Auth, UUIDv4)
Route::get('/n/{uuid}', [NotaPublicController::class, 'show'])
    ->where('uuid', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-4[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}')
    ->name('nota.public');

// Owner store operations (Protected)
Route::middleware(['auth:web', 'active', 'owner', 'branch'])->group(function () {
    Route::get('/apk', [\App\Http\Controllers\Web\ApkReleaseController::class, 'ownerIndex'])->name('apk.index');
    Route::get('/apk/{id}/download', [\App\Http\Controllers\Web\ApkReleaseController::class, 'ownerDownload'])->whereNumber('id')->name('apk.download');
    Route::post('/branches/select', [DashboardController::class, 'selectBranch'])->name('branches.select');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings', [StoreSettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [StoreSettingsController::class, 'update'])->name('settings.update');

    // Master Services
    Route::get('/services', [DashboardController::class, 'services'])->name('services.index');
    Route::post('/services', [DashboardController::class, 'storeService'])->name('services.store');
    Route::post('/services/{uuid}', [DashboardController::class, 'updateService'])->name('services.update');

    // Management Users / Kasir
    Route::get('/users', [DashboardController::class, 'users'])->name('users.index');
    Route::post('/users', [DashboardController::class, 'storeUser'])->name('users.store');
    Route::post('/users/{id}/toggle', [DashboardController::class, 'toggleUser'])->name('users.toggle');
    Route::get('/users/{user}/edit', [DashboardController::class, 'editUser'])->name('users.edit');
    Route::post('/users/{user}', [DashboardController::class, 'updateUser'])->name('users.update');

    // Transactions History
    Route::get('/transactions', [DashboardController::class, 'transactions'])->name('transactions.index');
    Route::get('/transactions/{uuid}', [WorkspaceController::class, 'transaction'])->name('transactions.show');
    Route::get('/customers', [WorkspaceController::class, 'customers'])->name('customers.index');
    Route::get('/customers/export', [WorkspaceController::class, 'exportCustomers'])->name('customers.export');
    Route::get('/customers/{uuid}', [WorkspaceController::class, 'customer'])->name('customers.show');
    Route::get('/reports', [WorkspaceController::class, 'reports'])->name('reports.index');
    Route::get('/branches', [WorkspaceController::class, 'branches'])->name('branches.index');
    Route::get('/branches/create', [WorkspaceController::class, 'branchForm'])->name('branches.create');
    Route::post('/branches', [WorkspaceController::class, 'saveBranch'])->name('branches.store');
    Route::get('/branches/{branch}/edit', [WorkspaceController::class, 'branchForm'])->name('branches.edit');
    Route::post('/branches/{branch}', [WorkspaceController::class, 'saveBranch'])->name('branches.update');
    Route::get('/opening-hours', [WorkspaceController::class, 'hours'])->name('hours.edit');
    Route::post('/opening-hours', [WorkspaceController::class, 'saveHours'])->name('hours.update');
    Route::get('/message-templates', [WorkspaceController::class, 'templates'])->name('templates.edit');
    Route::post('/message-templates', [WorkspaceController::class, 'saveTemplates'])->name('templates.update');
    Route::get('/profile', [WorkspaceController::class, 'profile'])->name('profile.edit');
    Route::post('/profile', [WorkspaceController::class, 'saveProfile'])->name('profile.update');
    Route::get('/synchronization', [WorkspaceController::class, 'sync'])->name('sync.index');
    Route::get('/notifications', [WorkspaceController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/read', [WorkspaceController::class, 'readNotifications'])->name('notifications.read');

    // Attendances & Selfie Monitoring
    Route::get('/attendances', [DashboardController::class, 'attendances'])->name('attendances.index');
    Route::get('/attendances/export', [WorkspaceController::class, 'exportAttendances'])->name('attendances.export');
    Route::get('/attendances/{uuid}/photo/{type}', [DashboardController::class, 'attendancePhoto'])->whereIn('type', ['check_in', 'check_out'])->name('attendances.photo');

    // Web-Exclusive DLP Export (Excel/CSV)
    Route::get('/reports/export', [DashboardController::class, 'exportReports'])->name('reports.export');
});
