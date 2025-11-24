<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'project.permissions' => \App\Http\Middleware\CheckProjectPermission::class,
            'tenancy' => \App\Http\Middleware\InitializeTenantForced::class,
        ]);
        
        // $middleware->web(append: [
        //     InitializeTenancyByDomain::class,
        //     PreventAccessFromCentralDomains::class,
        // ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,  

            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,

            \App\Http\Middleware\MeasureLoadTime::class,
            
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
