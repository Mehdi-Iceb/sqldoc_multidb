<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();
        
        $this->mapTenantRoutes();
    }
    
    protected function mapTenantRoutes(): void
    {
        Route::middleware(['web',
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            //\Illuminate\Cookie\Middleware\EncryptCookies::class,
            //\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
           // \App\Http\Middleware\InitializeTenantForced::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ])
        ->group(base_path('routes/tenant.php'));
    }
}