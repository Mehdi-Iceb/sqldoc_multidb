<?php

namespace App\Tenancy\Bootstrappers;

use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class DatabaseTenancyBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant)
    {
        $tenantConnection = $tenant->getDatabaseConnectionName();
        
        // Configurer la connexion tenant
        config([
            "database.connections.{$tenantConnection}" => $tenant->getDatabaseConnectionConfiguration($tenantConnection),
            'database.default' => $tenantConnection,
        ]);

        Log::info('Tenant database connection configured', [
            'tenant_id' => $tenant->getTenantKey(),
            'connection' => $tenantConnection,
            'database' => $tenant->getDatabaseName(),
        ]);
    }

    public function revert()
    {
        // Restaurer la connexion centrale
        config(['database.default' => config('tenancy.database.central_connection')]);
        
        Log::info('Reverted to central database connection');
    }
}