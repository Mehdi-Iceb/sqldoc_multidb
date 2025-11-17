<?php

declare(strict_types=1);

namespace App\Database;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\TenantDatabaseManager;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Exceptions\NoConnectionSetException;

class SQLServerDatabaseManager implements TenantDatabaseManager
{
    /** @var string */
    protected $connection;

    protected function database(): Connection
    {
        if ($this->connection === null) {
            throw new NoConnectionSetException(static::class);
        }

        return DB::connection($this->connection);
    }

    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    public function createDatabase(TenantWithDatabase $tenant): bool
    {
        $database = $tenant->database()->getName();

        try {
            return $this->database()->statement("CREATE DATABASE [{$database}]");
        } catch (\Exception $e) {
            // Si la base existe déjà, considérer comme succès
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'existe déjà') !== false) {
                \Log::info('SQL Server database already exists', [
                    'database' => $database
                ]);
                return true;
            }
            
            Log::error('Failed to create SQL Server database', [
                'database' => $database,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    public function deleteDatabase(TenantWithDatabase $tenant): bool
    {
        $database = $tenant->database()->getName();

        try {
            // Forcer la fermeture des connexions actives pour SQL Server
            $this->database()->statement("ALTER DATABASE [{$database}] SET SINGLE_USER WITH ROLLBACK IMMEDIATE");
            
            return $this->database()->statement("DROP DATABASE [{$database}]");
        } catch (\Exception $e) {
            // Si la base n'existe pas, considérer comme succès
            if (strpos($e->getMessage(), 'does not exist') !== false || 
                strpos($e->getMessage(), 'n\'existe pas') !== false) {
                \Log::info('SQL Server database does not exist, deletion skipped', [
                    'database' => $database
                ]);
                return true;
            }
            
            Log::error('Failed to delete SQL Server database', [
                'database' => $database,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    public function databaseExists(string $name): bool
    {
        try {
            $result = $this->database()->select("SELECT name FROM master.sys.databases WHERE name = ?", [$name]);
            return !empty($result);
        } catch (\Exception $e) {
            Log::error('Error checking if SQL Server database exists', [
                'database' => $name,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    public function makeConnectionConfig(array $baseConfig, string $databaseName): array
    {
        $baseConfig['database'] = $databaseName;

        return $baseConfig;
    }
}