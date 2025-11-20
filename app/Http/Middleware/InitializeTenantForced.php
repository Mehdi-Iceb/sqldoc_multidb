<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Domain;
use Illuminate\Support\Facades\Log;

class InitializeTenantForced
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        
        Log::info('InitializeTenantForced: Processing request', ['host' => $host]);
        
        // Ne pas traiter les domaines centraux
        if (in_array($host, config('tenancy.central_domains', []))) {
            Log::info('InitializeTenantForced: Central domain, skipping');
            return $next($request);
        }
        
        // Chercher le domaine
        $domain = Domain::where('domain', $host)->first();
        
        if (!$domain || !$domain->tenant) {
            Log::error('InitializeTenantForced: Domain or tenant not found', [
                'host' => $host,
                'domain_found' => $domain ? 'YES' : 'NO',
                'tenant_found' => $domain && $domain->tenant ? 'YES' : 'NO'
            ]);
            abort(404, 'Tenant not found');
        }
        
        // Initialiser le tenant MANUELLEMENT
        $tenant = $domain->tenant;
        
        // Configuration de la connexion tenant
        $tenantConnection = 'tenant_' . str_replace('-', '_', $tenant->id);
        
        config([
            "database.connections.{$tenantConnection}" => [
                'driver' => 'sqlsrv',
                'host' => config('database.connections.sqlsrv.host'),
                'port' => config('database.connections.sqlsrv.port'),
                'database' => $tenant->getDatabaseName(), 
                'username' => config('database.connections.sqlsrv.username'),
                'password' => config('database.connections.sqlsrv.password'),
                'charset' => config('database.connections.sqlsrv.charset'),
                'prefix' => '',
                'prefix_indexes' => true,
            ],
            'database.default' => $tenantConnection,
        ]);
        
        // Initialiser le tenant dans le contexte
        tenancy()->initialize($tenant);
        
        Log::info('InitializeTenantForced: Tenant initialized manually', [
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
            'database' => $tenant->getDatabaseName(),
            'connection' => $tenantConnection,
            'default_db' => config('database.default')
        ]);
        
        return $next($request);
    }
}