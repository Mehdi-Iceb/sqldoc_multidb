<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\LandingTenantController;
use App\Http\Controllers\PricingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Domain;
use App\Models\Tenant;


Route::get('/', function () {
    return redirect('/landing');
});

Route::get('/landing', [LandingTenantController::class, 'create'])->name('landing');
//Route::post('/start', [TenantController::class, 'store'])->name('tenant.public.store');

Route::get('/registerTenant', [TenantController::class, 'register'])->name('register');

Route::post('/start', [TenantController::class, 'start'])->name('tenant.start');
Route::get('/start', [TenantController::class, 'create'])->name('tenant.create');

require __DIR__.'/auth.php';