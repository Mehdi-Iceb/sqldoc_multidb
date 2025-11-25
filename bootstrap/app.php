<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
        __DIR__.'/../routes/web.php',
        __DIR__.'/../routes/tenant.php', 
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'project.permissions' => \App\Http\Middleware\CheckProjectPermission::class,
            //'tenancy' => \App\Http\Middleware\InitializeTenantForced::class,
        ]);
        
        $middleware->web(append: [
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,  
            // \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
            // \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
            \App\Http\Middleware\MeasureLoadTime::class,
            
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function ($request, \Throwable $e) {
            // Si c'est une requête Inertia, ne PAS retourner de JSON
            if ($request->header('X-Inertia')) {
                return false;
            }
            
            // Pour les autres requêtes, comportement par défaut
            return $request->is('api/*') || $request->expectsJson();
        });
        
        // ✅ Logger les exceptions des requêtes Inertia
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->header('X-Inertia')) {
                Log::error('❌ EXCEPTION IN INERTIA REQUEST', [
                    'url' => $request->url(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace_preview' => array_slice(explode("\n", $e->getTraceAsString()), 0, 10),
                ]);
            }
        });
    })->create();
