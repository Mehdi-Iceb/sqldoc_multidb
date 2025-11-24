<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenancyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Récupérer le nom de domaine de la requête
        $host = $request->getHost();
        Log::info('TenancyMiddleware: Processing request for host: ' . $host);

        // Les domaines "centraux" ne sont pas des locataires
        if (in_array($host, config('tenancy.central_domains'))) {
            return $next($request);
        }

        // 2. Rechercher le domaine dans la base de données centrale
        $domain = Domain::where('domain', $host)->first();

        // Si le domaine est introuvable, retourner une erreur 404
        if (!$domain) {
            Log::warning('TenancyMiddleware: Domain not found for host ' . $host);

            if ($request->header('X-Inertia')) {
                return back()->withErrors(['message' => 'Tenant introuvable']);
            }

            return response()->view('errors.404', [], 404);
        }

        // 3. Configurer la connexion à la base de données du locataire
        $tenant = $domain->tenant;
        Log::info('TenancyMiddleware: Tenant found. ID: ' . $tenant->id);

        $tenantDbName = 'tenant_' . strtolower(Str::slug($tenant->slug, '_'));
        $connectionName = 'tenant_' . str_replace('-', '_', $tenant->id);

        $tenantConnectionConfig = [
            'driver' => 'sqlsrv',
            'host' => config('database.connections.sqlsrv.host'),
            'port' => config('database.connections.sqlsrv.port'),
            'database' => $tenantDbName,
            'username' => config('database.connections.sqlsrv.username'),
            'password' => config('database.connections.sqlsrv.password'),
            'charset' => config('database.connections.sqlsrv.charset'),
            'prefix' => '',
            'prefix_indexes' => true,
        ];

        Config::set("database.connections.{$connectionName}", $tenantConnectionConfig);
        Config::set('database.default', $connectionName);

        try {
            DB::connection($connectionName)->getPdo();
            Log::info('TenancyMiddleware: Switched to tenant database successfully.', ['database' => $tenantDbName]);
        }catch (\Exception $e) {
            Log::error('TenancyMiddleware: Failed to switch to tenant database.', [
                'error' => $e->getMessage()
            ]);

            if ($request->header('X-Inertia')) {
                return back()->withErrors(['message' => 'Database tenant error']);
            }

            return response()->view('errors.500', [
                'message' => 'Cannot connect to tenant database.'
            ], 500);
        }
        
        // Stocker le locataire dans l'application pour un accès facile
        app()->instance('tenant', $tenant);

        // 4. Continuer la requête avec la nouvelle connexion
        return $next($request);
    }
}
