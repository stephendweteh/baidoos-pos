<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BusinessTypeController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\SuperAdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DayClosingController;
use App\Http\Controllers\Pos\SaleController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Baidoos POS - Web Routes
|--------------------------------------------------------------------------
*/

// ─── Guest ────────────────────────────────────────────────────
Route::get('/', fn () => redirect()->route('dashboard'));
Auth::routes(['register' => false, 'reset' => false, 'verify' => false]);

// ─── Authenticated ─────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/sale', [SaleController::class, 'index'])->name('sale');
        Route::post('/sale', [SaleController::class, 'store'])->name('sale.store');
        Route::get('/receipt/{sale}', [SaleController::class, 'show'])->name('receipt');
    });

    // Day Closing
    Route::prefix('day-closing')->name('day-closing.')->group(function () {
        Route::get('/',        [DayClosingController::class, 'index'])->name('index');
        Route::get('/close',   [DayClosingController::class, 'close'])->name('close');
        Route::post('/close',  [DayClosingController::class, 'store'])->name('store');
        Route::get('/{dayClosing}', [DayClosingController::class, 'show'])->name('show');
    });

    // Admin — Owner Only
    Route::prefix('admin')->name('admin.')->middleware('role:owner')->group(function () {

        // Business Types / Categories
        Route::resource('business-types', BusinessTypeController::class)
            ->names('business-types')
            ->except(['show']);

        // Branches
        Route::resource('branches', BranchController::class)
            ->except(['show']);

        // Items / Services
        Route::resource('items', ItemController::class)
            ->except(['show']);

        // Users
        Route::resource('users', UserController::class)
            ->except(['show']);
    });

    // Super Admin Only
    Route::prefix('superadmin')->name('superadmin.')->middleware('role:superadmin')->group(function () {
        Route::post('/reset-sales', [SuperAdminController::class, 'resetSales'])->name('reset-sales');
        Route::get('/settings',     [SuperAdminController::class, 'settings'])->name('settings');
        Route::post('/settings',    [SuperAdminController::class, 'updateSettings'])->name('settings.update');
    });
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
