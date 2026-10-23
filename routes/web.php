<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\HistoryExportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicScanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

/* The public fish scanner works without an account: results are shown, not
   stored, and the endpoint is throttled because it accepts uploads from
   anonymous visitors. */
Route::view('/scan', 'scan.public')->name('scan.public');
Route::post('/scan/analyze', [PublicScanController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('scan.public.analyze');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
});

Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:6,1'])->name('login.submit');
Route::post('/register', [AuthController::class, 'register'])->middleware(['guest', 'throttle:10,1'])->name('register');
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('/scans', [ScanController::class, 'store'])->middleware('throttle:20,1')->name('scans.store');
    /* Exporting printable/CSV scan data is a vendor tool, so customers are
       rejected here as well as never seeing the buttons. */
    Route::get('/history/export/data', [HistoryExportController::class, 'data'])->middleware('role:vendor')->name('history.export.data');
    Route::get('/history/export/csv', [HistoryExportController::class, 'csv'])->middleware('role:vendor')->name('history.export.csv');
    Route::post('/batches', [BatchController::class, 'store'])->middleware('throttle:10,1')->name('batches.store');

    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('throttle:10,1')->name('profile.update');
    Route::patch('/password', [AuthController::class, 'changePassword'])->middleware('throttle:10,1')->name('password.change');

    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');
    });
    Route::get('/notifications/count', [NotificationController::class, 'counts'])->name('notifications.count');

    Route::prefix('inventory')->middleware(['role:vendor', 'throttle:30,1'])->group(function () {
        Route::post('/', [InventoryController::class, 'store'])->name('inventory.store');
        Route::patch('/{item}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/{item}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    });

    Route::prefix('suppliers')->middleware(['role:vendor', 'throttle:30,1'])->group(function () {
        Route::post('/', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::patch('/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::patch('/{supplier}/status', [SupplierController::class, 'updateStatus'])->name('suppliers.status');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });

    Route::prefix('deliveries')->middleware(['role:vendor', 'throttle:30,1'])->group(function () {
        Route::post('/', [DeliveryController::class, 'store'])->name('deliveries.store');
        Route::patch('/{delivery}', [DeliveryController::class, 'update'])->name('deliveries.update');
        Route::patch('/{delivery}/accept', [DeliveryController::class, 'accept'])->name('deliveries.accept');
        Route::patch('/{delivery}/reject', [DeliveryController::class, 'reject'])->name('deliveries.reject');
        Route::delete('/{delivery}', [DeliveryController::class, 'destroy'])->name('deliveries.destroy');
    });

    /* Exporting business reports is a vendor tool: customers never see the
       page and are rejected at the endpoints as well. */
    Route::prefix('reports')->middleware('role:vendor')->group(function () {
        Route::get('/data', [ReportController::class, 'data'])->name('reports.data');
        Route::get('/export/csv', [ReportController::class, 'csv'])->name('reports.export.csv');
    });
});
