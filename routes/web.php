<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Tenant;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

/*
|--------------------------------------------------------------------------
| Tamu: login & registrasi penyewa
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        // Garasi
        Route::get('/garages', [Admin\GarageController::class, 'index'])->name('garages.index');
        Route::get('/garages/create', [Admin\GarageController::class, 'create'])->name('garages.create');
        Route::post('/garages', [Admin\GarageController::class, 'store'])->name('garages.store');

        // Data sewa (termasuk bukti bayar)
        Route::get('/rentals', [Admin\RentalController::class, 'index'])->name('rentals.index');
        Route::get('/rentals/{rental}', [Admin\RentalController::class, 'show'])->name('rentals.show');

        // Verifikasi pendaftar
        Route::get('/tenants', [Admin\TenantVerificationController::class, 'index'])->name('tenants.index');
        Route::patch('/tenants/{tenant}/approve', [Admin\TenantVerificationController::class, 'approve'])->name('tenants.approve');
        Route::patch('/tenants/{tenant}/reject', [Admin\TenantVerificationController::class, 'reject'])->name('tenants.reject');

        // Perpanjang sewa
        Route::get('/extensions', [Admin\RentalExtensionController::class, 'index'])->name('extensions.index');
        Route::get('/extensions/{rental}/create', [Admin\RentalExtensionController::class, 'create'])->name('extensions.create');
        Route::get('/extensions/{rental}/preview', [Admin\RentalExtensionController::class, 'preview'])->name('extensions.preview');
        Route::post('/extensions/{rental}', [Admin\RentalExtensionController::class, 'store'])->name('extensions.store');

        // Laporan
        Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    });

/*
|--------------------------------------------------------------------------
| Penyewa
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:tenant'])
    ->prefix('tenant')
    ->name('tenant.')
    ->group(function () {
        Route::get('/', Tenant\DashboardController::class)->name('dashboard');

        // Sewa garasi
        Route::get('/rentals', [Tenant\RentalController::class, 'index'])->name('rentals.index');
        Route::get('/rentals/create', [Tenant\RentalController::class, 'create'])->name('rentals.create');
        Route::get('/rentals/available-garages', [Tenant\RentalController::class, 'availableGarages'])->name('rentals.available-garages');
        Route::post('/rentals', [Tenant\RentalController::class, 'store'])->name('rentals.store');
        Route::get('/rentals/{rental}', [Tenant\RentalController::class, 'show'])->name('rentals.show');
    });
