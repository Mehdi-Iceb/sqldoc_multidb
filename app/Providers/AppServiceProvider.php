<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Services\DatabaseNavigationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Pas besoin d'enregistrer le service pour l'instant
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        
        Inertia::share([
            // Permissions existantes
            'permissions' => function () {
                return session('permissions', []);
            },
            
            // Configuration app existante
            'appName' => config('app.name'),
            'appVersion' => config('app.version'),
            
            // Données utilisateur
            'auth' => function () {
                return [
                    'user' => Auth::user() ? [
                        'id' => Auth::user()->id,
                        'name' => Auth::user()->name,
                        'email' => Auth::user()->email,
                        'role' => Auth::user()->role ?? 'user',
                    ] : null,
                ];
            },

            'currentProject' => function () {
                return session('current_project');
            },
            
            // Structure de navigation (SIMPLIFIÉE)
            'navigationData' => function () {
                // Vérifier si l'utilisateur est connecté
                if (!Auth::check()) {
                    return null;
                }
                
                // Vérifier si nous sommes sur une route qui a besoin de navigation
                if (!$this->shouldLoadNavigation()) {
                    return null;
                }
                
                // Utiliser DIRECTEMENT votre logique de session existante
                $dbId = session('current_db_id');
                
                if (!$dbId) {
                    // Retourner structure vide si pas de DB sélectionnée
                    return [
                        'tables' => [],
                        'views' => [],
                        'functions' => [],
                        'procedures' => [],
                        'triggers' => [],
                        'metadata' => [
                            'generated_at' => now()->toISOString(),
                            'execution_time_ms' => 0,
                            'total_objects' => 0,
                            'message' => 'Aucune base de données sélectionnée'
                        ]
                    ];
                }
                
                try {
                    // Cache simple basé sur user_id et db_id
                    $cacheKey = "simple_navigation_" . Auth::id() . "_{$dbId}";
                    
                    return Cache::remember($cacheKey, 1800, function () use ($dbId) {
                        Log::info('AppServiceProvider - Génération navigation depuis cache', [
                            'user_id' => Auth::id(),
                            'db_id' => $dbId
                        ]);
                        
                        return $this->buildSimpleNavigation($dbId);
                    });
                    
                } catch (\Exception $e) {
                    // En cas d'erreur, logger et retourner une structure vide
                    Log::warning('AppServiceProvider - Erreur lors du chargement de la navigation', [
                        'user_id' => Auth::id(),
                        'db_id' => $dbId,
                        'error' => $e->getMessage(),
                        'route' => request()->route() ? request()->route()->getName() : 'unknown'
                    ]);
                    
                    return [
                        'tables' => [],
                        'views' => [],
                        'functions' => [],
                        'procedures' => [],
                        'triggers' => [],
                        'metadata' => [
                            'generated_at' => now()->toISOString(),
                            'execution_time_ms' => 0,
                            'total_objects' => 0,
                            'error' => 'Erreur lors du chargement de la navigation'
                        ]
                    ];
                }
            },
            
            // Messages flash
            'flash' => function () {
                return [
                    'success' => session('success'),
                    'error' => session('error'),
                    'warning' => session('warning'),
                    'info' => session('info'),
                ];
            },
        ]);

        Carbon::serializeUsing(function ($carbon) {
            return $carbon->format('Y-m-d H:i:s');
        });

        Blueprint::macro('dateTime2', function ($column, $precision = 7) {
        /** @var \Illuminate\Database\Schema\Blueprint $this */
        return $this->addColumn('datetime2', $column, compact('precision'));
        });
        Schema::getConnection()->setSchemaGrammar(
        tap(DB::connection()->getSchemaGrammar(), function ($grammar) {
            if ($grammar instanceof SqlServerGrammar) {
                $grammar->macro('typeDatetime2', function ($column) {
                    $precision = $column->precision ?? 0;
                    return "datetime2($precision)";
                });
            }
        })
    );

    if (config('database.default') === 'sqlsrv' || str_starts_with(config('database.default'), 'tenant_')) {
            try {
                DB::statement("SET DATEFORMAT ymd");
            } catch (\Exception $e) {
                Log::warning('Could not set SQL Server date format on boot', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ NOUVEAU : Écouter l'initialisation du tenant pour configurer SQL Server
        if (class_exists(\Stancl\Tenancy\Tenancy::class)) {
            $this->configureTenantDatabaseOnInit();
        }
    }

    protected function configureTenantDatabaseOnInit(): void
    {
        tenancy()->hook('bootstrapped', function () {
            try {
                $connection = DB::connection();
                
                if ($connection->getDriverName() === 'sqlsrv') {
                    // Forcer le format de date YYYY-MM-DD
                    $connection->statement("SET DATEFORMAT ymd");
                    
                    // Options supplémentaires pour SQL Server
                    $connection->statement("SET ANSI_NULLS ON");
                    $connection->statement("SET QUOTED_IDENTIFIER ON");
                    
                    Log::info('SQL Server date format configured for tenant', [
                        'connection' => $connection->getName(),
                        'database' => $connection->getDatabaseName(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error configuring SQL Server for tenant', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });
    


    if (app()->environment('local')) {
        config(['session.domain' => '.'.config('app.domain', 'localhost')]);
    }

    }

    /**
     * Construction simple de la navigation (sans permissions pour l'instant)
     */
    private function buildSimpleNavigation($dbId)
    {
        $startTime = microtime(true);
        
        // Version simple qui récupère juste les noms
        $tables = DB::table('table_description')
            ->where('dbid', $dbId)
            ->select('id', 'tablename as name', 'description')
            ->orderBy('tablename')
            ->get();
            
        $views = DB::table('view_description')
            ->where('dbid', $dbId)
            ->select('id', 'viewname as name', 'description')
            ->orderBy('viewname')
            ->get();
            
        $functions = DB::table('function_description')
            ->where('dbid', $dbId)
            ->select('id', 'functionname as name', 'description')
            ->orderBy('functionname')
            ->get();
            
        $procedures = DB::table('ps_description')
            ->where('dbid', $dbId)
            ->select('id', 'psname as name', 'description')
            ->orderBy('psname')
            ->get();
            
        $triggers = DB::table('trigger_description')
            ->where('dbid', $dbId)
            ->select('id', 'triggername as name', 'description')
            ->orderBy('triggername')
            ->get();
            
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        return [
            'tables' => $tables,
            'views' => $views,
            'functions' => $functions,
            'procedures' => $procedures,
            'triggers' => $triggers,
            'metadata' => [
                'generated_at' => now()->toISOString(),
                'execution_time_ms' => round($executionTime, 2),
                'total_objects' => $tables->count() + $views->count() + $functions->count() + $procedures->count() + $triggers->count(),
                'db_id' => $dbId
            ]
        ];
    }

    /**
     * Détermine si la navigation doit être chargée pour la route actuelle
     */
    private function shouldLoadNavigation(): bool
    {
        $request = request();
        
        // Ne pas charger sur les routes API
        if ($request->is('api/*')) {
            return false;
        }
        
        // Ne pas charger sur les routes d'authentification
        if ($request->is('login*') || $request->is('register*') || $request->is('password/*') || $request->is('email/*')) {
            return false;
        }
        
        // Ne pas charger sur les routes publiques
        if ($request->is('/') && !Auth::check()) {
            return false;
        }
        
        // Ne pas charger sur les routes d'erreur
        if ($request->is('404') || $request->is('500')) {
            return false;
        }
        
        // Liste des routes spécifiques où ne pas charger la navigation
        $excludedRoutes = [
            'login',
            'register',
            'password.request',
            'password.email',
            'password.reset',
            'verification.notice',
            'verification.verify',
            'verification.send'
        ];
        
        $currentRoute = $request->route() ? $request->route()->getName() : null;
        if ($currentRoute && in_array($currentRoute, $excludedRoutes)) {
            return false;
        }
        
        // Charger sur toutes les autres routes pour les utilisateurs connectés
        return true;
    }
}