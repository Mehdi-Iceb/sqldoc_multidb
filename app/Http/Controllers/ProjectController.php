<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasProjectPermissions;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\DbDescription;
use App\Services\DatabaseStructureService;
use App\Models\UserProjectAccess;
use Illuminate\Support\Facades\Cache;

class ProjectController extends Controller
{
    use HasProjectPermissions;

    public function index()
    {
        $userId = auth()->id();
        
        // Récupérer les projets appartenant à l'utilisateur
        $ownedProjects = Project::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'db_type' => $project->db_type,
                    'is_owner' => true,
                    'access_level' => 'admin', // Le propriétaire a tous les droits
                    'owner_name' => auth()->user()->name,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at
                ];
            });
        
        // Récupérer les projets partagés avec l'utilisateur
        $sharedProjects = collect();
        
        $userProjectAccesses = \App\Models\UserProjectAccess::where('user_id', $userId)
            ->with(['project' => function($query) {
                $query->whereNull('deleted_at')->with('user:id,name');
            }])
            ->get();
        
        foreach ($userProjectAccesses as $access) {
            if ($access->project) { // Vérifier que le projet existe encore
                $sharedProjects->push([
                    'id' => $access->project->id,
                    'name' => $access->project->name,
                    'description' => $access->project->description,
                    'db_type' => $access->project->db_type,
                    'is_owner' => false,
                    'access_level' => $access->access_level,
                    'owner_name' => $access->project->user->name,
                    'created_at' => $access->project->created_at,
                    'updated_at' => $access->project->updated_at,
                    'shared_at' => $access->created_at
                ]);
            }
        }
        
        // Combiner les deux collections
        $allProjects = $ownedProjects->concat($sharedProjects)->sortBy('name');
        
        Log::info('Projects récupérés pour l\'utilisateur', [
            'user_id' => $userId,
            'owned_count' => $ownedProjects->count(),
            'shared_count' => $sharedProjects->count(),
            'total_count' => $allProjects->count()
        ]);
        
        return Inertia::render('Projects/Index', [
            'projects' => $allProjects->values(), // Réindexer la collection
            'stats' => [
                'owned' => $ownedProjects->count(),
                'shared' => $sharedProjects->count(),
                'total' => $allProjects->count()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'db_type' => 'required|in:sqlserver,mysql,pgsql',
            'description' => 'nullable|string|max:1000'
            
        ]);

        $project = $request->user()->projects()->create($validated);

        return redirect()->route('projects.connect', $project->id);
    }

    public function create()
    {
        return Inertia::render('Projects/Create', [
            'dbTypes' => [
                'mysql' => 'MySQL',
                'sqlserver' => 'SQL Server',
                'pgsql' => 'PostgreSQL' 
            ]
        ]);
    }

    public function connect(Project $project)
    {
        $userCanAccess = $this->checkUserProjectAccess($project);
        
        if (!$userCanAccess['allowed'] || !$userCanAccess['is_owner']) {
            return redirect()->route('projects.index')
                ->with('error', 'Only the project owner can configure database connections.');
        }

        return Inertia::render('Projects/Connect', [
            'project' => $project
        ]);
    }

    public function handleConnect(Request $request, Project $project)
    {

        //forcage augmentation temps de chargement des datas
        set_time_limit(5600);
        
        Log::info('=== DÉBUT handleConnect ===', [
            'project_id' => $project->id,
            'user_id' => auth()->id()
        ]);

        $userCanAccess = $this->checkUserProjectAccess($project);
        
        if (!$userCanAccess['allowed'] || !$userCanAccess['is_owner']) {
            return redirect()->back()->with('error', 'Only the project owner can configure database connections.');
        }
        
        try {
            // Validation
            $rules = [
                'server' => 'required|string',
                'database' => 'required|string',
                'description' => 'nullable|string|max:1000',
                'username' => 'nullable|string',
                'password' => 'nullable|string',
            ];

            if ($project->db_type === 'sqlserver') {
                $rules['authMode'] = 'required|in:windows,sql';
                $rules['username'] = 'required_if:authMode,sql';
                $rules['password'] = 'required_if:authMode,sql';
            } else {
                $rules['port'] = 'required|numeric|min:1|max:65535';
                $rules['username'] = 'required|string';
                $rules['password'] = 'required|string';
            }

            $validated = $request->validate($rules);

            Log::info('Validation réussie pour handleConnect', [
                'project_id' => $project->id,
                'db_type' => $project->db_type,
                'server' => $validated['server'],
                'database' => $validated['database'],
                'authMode' => $validated['authMode'] ?? 'N/A'
            ]);

            // Configuration de connexion
            $driver = $this->getDriverFromDbType($project->db_type);
            $config = [
                'driver' => $driver,
                'host' => $validated['server'],
                'database' => $validated['database'],
            ];

            if ($project->db_type === 'sqlserver') {
                if ($validated['authMode'] === 'windows') {
                    $config['trust_connection'] = true;
                    Log::info('Configuration SQL Server avec authentification Windows');
                } else {
                    $config['username'] = $validated['username'];
                    $config['password'] = $validated['password'];
                    Log::info('Configuration SQL Server avec authentification SQL', [
                        'username' => $validated['username']
                    ]);
                }
            } else {
                $config['port'] = $validated['port'];
                $config['username'] = $validated['username'];
                $config['password'] = $validated['password'];
                
                if ($project->db_type === 'pgsql') {
                    $config['charset'] = 'utf8';
                    $config['prefix'] = '';
                    $config['prefix_indexes'] = true;
                    $config['schema'] = 'public';
                    $config['sslmode'] = 'prefer';
                }
            }

            // Test de connexion
            $connectionName = "project_{$project->id}";
            Config::set("database.connections.{$connectionName}", $config);
            
            Log::info('Tentative de connexion à la base de données', [
                'connection_name' => $connectionName,
                'driver' => $driver
            ]);
            
            try {
                // Tenter la connexion
                $pdo = DB::connection($connectionName)->getPdo();
                Log::info('Connexion PDO établie avec succès');
                
                // Test de la base de données
                $testQuery = $this->getTestQuery($project->db_type, $validated['database']);
                $result = DB::connection($connectionName)->select($testQuery);
                
                if (empty($result)) {
                    DB::disconnect($connectionName);
                    config()->forget("database.connections.{$connectionName}");
                    throw new \Exception("Database '{$validated['database']}' does not exist or is not accessible.");
                }
                
                Log::info('Connexion réussie et base de données vérifiée');

            } catch (\PDOException $e) {
                // Nettoyer la connexion
                try {
                    DB::disconnect($connectionName);
                    config()->forget("database.connections.{$connectionName}");
                } catch (\Exception $cleanupException) {
                    Log::warning('Erreur lors du nettoyage de la connexion', [
                        'cleanup_error' => $cleanupException->getMessage()
                    ]);
                }
                
                $errorMessage = $this->analyzePDOException($e, $project->db_type, $validated);
                
                Log::error('Erreur de connexion PDO dans handleConnect', [
                    'project_id' => $project->id,
                    'error_code' => $e->getCode(),
                    'error_message' => $e->getMessage(),
                    'analyzed_message' => $errorMessage
                ]);
                
                Log::info('=== RETOUR AVEC ERREUR PDO ===');
                
                // IMPORTANT: Pour Inertia, rester sur la même page avec l'erreur
                return \Inertia\Inertia::render('Projects/Connect', [
                    'project' => $project,
                    'flash' => [
                        'error' => $errorMessage
                    ]
                ])->withViewData([
                    'flash' => [
                        'error' => $errorMessage
                    ]
                ]);
                    
            } catch (\Exception $e) {
                // Nettoyer la connexion
                try {
                    DB::disconnect($connectionName);
                    config()->forget("database.connections.{$connectionName}");
                } catch (\Exception $cleanupException) {
                    Log::warning('Erreur lors du nettoyage de la connexion', [
                        'cleanup_error' => $cleanupException->getMessage()
                    ]);
                }
                
                Log::error('Erreur générale de connexion dans handleConnect', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage()
                ]);
                
                $errorMessage = 'Connection failed: ' . $e->getMessage();
                
                Log::info('=== RETOUR AVEC ERREUR GÉNÉRALE ===');
                
                // IMPORTANT: Pour Inertia, rester sur la même page avec l'erreur
                return \Inertia\Inertia::render('Projects/Connect', [
                    'project' => $project,
                    'flash' => [
                        'error' => $errorMessage
                    ]
                ])->withViewData([
                    'flash' => [
                        'error' => $errorMessage
                    ]
                ]);
            }

            // *** SUCCÈS - CONTINUER LE TRAITEMENT ***
            Log::info('=== CONNEXION RÉUSSIE - TRAITEMENT POST-CONNEXION ===');

            // Sauvegarde de la base de données
            try {
                $dbDescription = DbDescription::updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'user_id' => auth()->id()
                    ],
                    [
                        'dbname' => $validated['database'],
                        'description' => $validated['description'] ?? null,
                    ]
                );
                
                session(['current_db_id' => $dbDescription->id]);
                
                // Extraction de la structure
                try {
                    $databaseStructureService = new DatabaseStructureService();
                    $result = $databaseStructureService->extractAndSaveAllStructures($connectionName, $dbDescription->id);
                    Log::info('Structure extraite avec succès');
                } catch (\Exception $structureException) {
                    Log::error('Erreur extraction structure', [
                        'error' => $structureException->getMessage()
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::warning('Erreur sauvegarde DbDescription', [
                    'error' => $e->getMessage()
                ]);
            }

            // Sauvegarde des infos de connexion
            try {
                $project->update(['connection_info' => json_encode($config)]);
            } catch (\Exception $e) {
                Log::warning('Erreur sauvegarde connection_info', [
                    'error' => $e->getMessage()
                ]);
            }

            // Session
            session([
                'current_project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'connection' => $config,
                    'db_type' => $project->db_type
                ]
            ]);
            
            Log::info('=== REDIRECTION VERS DASHBOARD ===');
            return redirect()->route('dashboard')->with('success', 'Connection successful to ' . $validated['database']);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Erreurs de validation dans handleConnect', [
                'errors' => $e->errors()
            ]);
            
            // Pour les erreurs de validation, utiliser la méthode standard
            return redirect()->back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            Log::error('Erreur générale dans handleConnect', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = 'An unexpected error occurred: ' . $e->getMessage();
            
            Log::info('=== RETOUR AVEC ERREUR EXCEPTION GÉNÉRALE ===');
            
            // IMPORTANT: Pour Inertia, rester sur la même page avec l'erreur
            return \Inertia\Inertia::render('Projects/Connect', [
                'project' => $project,
                'flash' => [
                    'error' => $errorMessage
                ]
            ])->withViewData([
                'flash' => [
                    'error' => $errorMessage
                ]
            ]);
        }
    }

    /**
     * Analyser les exceptions PDO pour fournir des messages d'erreur clairs
     */
    private function analyzePDOException(\PDOException $e, $dbType, $validated)
    {
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();
        
        // Messages d'erreur selon le type de base de données
        switch ($dbType) {
            case 'mysql':
                return $this->analyzeMySQLError($errorCode, $errorMessage, $validated);
            case 'pgsql':
                return $this->analyzePostgreSQLError($errorCode, $errorMessage, $validated);
            case 'sqlserver':
                return $this->analyzeSQLServerError($errorCode, $errorMessage, $validated);
            default:
                return "Database connection failed: " . $errorMessage;
        }
    }

    /**
     * Analyser les erreurs MySQL
     */
    private function analyzeMySQLError($errorCode, $errorMessage, $validated)
    {
        if (strpos($errorMessage, 'Unknown database') !== false) {
            return "Database '{$validated['database']}' does not exist on the MySQL server. Please check the database name or create the database first.";
        }
        
        if (strpos($errorMessage, 'Access denied') !== false) {
            return "Access denied. Please check your username and password, or verify that the user has permissions to access the '{$validated['database']}' database.";
        }
        
        if (strpos($errorMessage, 'Connection refused') !== false || strpos($errorMessage, 'Can\'t connect') !== false) {
            return "Cannot connect to MySQL server at '{$validated['server']}:{$validated['port']}'. Please check that the server is running and accessible.";
        }
        
        if (strpos($errorMessage, 'timeout') !== false) {
            return "Connection timeout to MySQL server. The server may be overloaded or the network connection is slow.";
        }
        
        return "MySQL connection failed: " . $errorMessage;
    }

    /**
     * Analyser les erreurs PostgreSQL
     */
    private function analyzePostgreSQLError($errorCode, $errorMessage, $validated)
    {
        if (strpos($errorMessage, 'database') !== false && strpos($errorMessage, 'does not exist') !== false) {
            return "Database '{$validated['database']}' does not exist on the PostgreSQL server. Please check the database name or create the database first.";
        }
        
        if (strpos($errorMessage, 'password authentication failed') !== false) {
            return "Password authentication failed for user '{$validated['username']}'. Please check your credentials.";
        }
        
        if (strpos($errorMessage, 'role') !== false && strpos($errorMessage, 'does not exist') !== false) {
            return "User '{$validated['username']}' does not exist on the PostgreSQL server. Please check the username.";
        }
        
        if (strpos($errorMessage, 'Connection refused') !== false || strpos($errorMessage, 'could not connect') !== false) {
            return "Cannot connect to PostgreSQL server at '{$validated['server']}:{$validated['port']}'. Please check that the server is running and accessible.";
        }
        
        if (strpos($errorMessage, 'timeout') !== false) {
            return "Connection timeout to PostgreSQL server. The server may be overloaded or the network connection is slow.";
        }
        
        if (strpos($errorMessage, 'permission denied') !== false) {
            return "Permission denied to access database '{$validated['database']}'. Please check that user '{$validated['username']}' has the necessary permissions.";
        }
        
        return "PostgreSQL connection failed: " . $errorMessage;
    }

    /**
     * Analyser les erreurs SQL Server
     */
    private function analyzeSQLServerError($errorCode, $errorMessage, $validated)
    {
        // Problème spécifique avec l'authentification Windows 
        if (strpos($errorMessage, 'Login failed for user') !== false && 
            (strpos($errorMessage, 'HEADOFFICE\\') !== false || strpos($errorMessage, '\\') !== false)) {
            
            // Extraire le nom d'utilisateur Windows du message d'erreur
            preg_match('/Login failed for user \'(.+?)\'/', $errorMessage, $matches);
            $windowsUser = $matches[1] ?? 'Windows user';
            
            return "Windows Authentication failed for '{$windowsUser}'. This Windows account does not have login permissions on SQL Server. Please either: 1) Switch to 'SQL Server Authentication' and use a valid SQL Server login, or 2) Contact your database administrator to grant SQL Server access to your Windows account.";
        }
        
        if (strpos($errorMessage, 'Cannot open database') !== false) {
            return "Database '{$validated['database']}' cannot be opened or does not exist on the SQL Server. Please check the database name.";
        }
        
        if (strpos($errorMessage, 'Login failed') !== false) {
            // Pour l'authentification SQL Server
            $username = $validated['username'] ?? 'unknown user';
            return "Login failed for SQL Server user '{$username}'. Please check your username and password, and ensure this account has access to SQL Server.";
        }
        
        if (strpos($errorMessage, 'server was not found') !== false || strpos($errorMessage, 'network path was not found') !== false) {
            return "SQL Server '{$validated['server']}' was not found or is not accessible. Please check the server name and network connectivity.";
        }
        
        if (strpos($errorMessage, 'timeout') !== false) {
            return "Connection timeout to SQL Server. The server may be overloaded or the network connection is slow.";
        }
        
        if (strpos($errorMessage, 'The user is not associated with a trusted SQL Server connection') !== false) {
            return "Windows Authentication failed. Please check that your Windows account has access to SQL Server or use SQL Server Authentication.";
        }
        
        if (strpos($errorMessage, 'permission denied') !== false) {
            return "Permission denied to access database '{$validated['database']}'. Please check user permissions.";
        }
        
        return "SQL Server connection failed: " . $errorMessage;
    }

    /**
     * Obtenir une requête de test selon le type de base de données
     */
    private function getTestQuery($dbType, $databaseName)
    {
        switch ($dbType) {
            case 'mysql':
                return "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . addslashes($databaseName) . "'";
            case 'pgsql':
                return "SELECT datname FROM pg_database WHERE datname = '" . addslashes($databaseName) . "'";
            case 'sqlserver':
                return "SELECT name FROM sys.databases WHERE name = '" . addslashes($databaseName) . "'";
            default:
                return "SELECT 1";
        }
    }

    /**
     * Méthode helper pour convertir le type de base de données en driver Laravel
     */
    private function getDriverFromDbType($dbType)
    {
        switch ($dbType) {
            case 'sqlserver':
                return 'sqlsrv';
            case 'mysql':
                return 'mysql';
            case 'pgsql':
                return 'pgsql';
            default:
                return $dbType;
        }
    }

    public function open($id)
{
    // ✅ 1. Cache des vérifications coûteuses
    $cacheKey = "project_quick_access_{$id}_" . auth()->id();
    
    $projectData = Cache::remember($cacheKey, 60, function() use ($id) {
        return [
            'project' => Project::with('user:id,name')->findOrFail($id),
            'access' => $this->checkUserProjectAccessOptimized(Project::findOrFail($id)),
            'db_description' => DbDescription::where('project_id', $id)->first()
        ];
    });
    
    extract($projectData);
    
    // ✅ 2. Vérifications rapides
    if ($project->trashed()) {
        return redirect()->route('projects.index')
            ->with('error', 'Ce projet a été supprimé.');
    }
    
    if (!$access['allowed']) {
        return redirect()->route('projects.index')
            ->with('error', $access['message']);
    }
    
    if (!$db_description) {
        if ($access['is_owner']) {
            return redirect()->route('projects.connect', $project->id)
                ->with('info', "Project needs database connection.");
        } else {
            return redirect()->route('projects.index')
                ->with('warning', "Project not configured yet.");
        }
    }
    
    // ✅ 3. Session immédiate (pas de vérification DB)
    $connectionInfo = $this->prepareConnectionInfoOptimized($project, $db_description);
    
    session([
        'current_project' => [
            'id' => $project->id,
            'name' => $project->name,
            'db_type' => $project->db_type,
            'connection' => $connectionInfo,
            'access_level' => $access['access_level'],
            'is_owner' => $access['is_owner']
        ],
        'current_db_id' => $db_description->id
    ]);
    
    // ✅ 4. Redirection immédiate sans vérification DB
    return redirect()->route('dashboard')
        ->with('success', "Project '{$project->name}' opened successfully.");
}


    private function checkUserProjectAccessOptimized($project)
    {
        $userId = auth()->id();
        
        // Le propriétaire a toujours accès
        if ($project->user_id == $userId) {
            return [
                'allowed' => true,
                'access_level' => 'owner',
                'is_owner' => true,
                'message' => 'Owner access'
            ];
        }
        
        // Une seule requête pour vérifier les accès partagés
        $projectAccess = UserProjectAccess::where('user_id', $userId)
            ->where('project_id', $project->id)
            ->first(['access_level']);
        
        if ($projectAccess) {
            return [
                'allowed' => true,
                'access_level' => $projectAccess->access_level,
                'is_owner' => false,
                'message' => 'Shared access: ' . $projectAccess->access_level
            ];
        }
        
        return [
            'allowed' => false,
            'access_level' => null,
            'is_owner' => false,
            'message' => "You don't have permission to access this project."
        ];
    }

    private function prepareConnectionInfoOptimized($project, $dbDescription)
    {
        if (isset($project->connection_info) && !empty($project->connection_info)) {
            $connectionInfo = is_string($project->connection_info) 
                ? json_decode($project->connection_info, true) 
                : $project->connection_info;
        } else {
            $connectionInfo = [
                'driver' => $this->getDriverFromDbType($project->db_type),
                'host' => 'localhost',
                'database' => $dbDescription->dbname,
                'username' => '',
                'password' => ''
            ];
        }
        
        if (!isset($connectionInfo['driver'])) {
            $connectionInfo['driver'] = $this->getDriverFromDbType($project->db_type);
        }
        
        return $connectionInfo;
    }

    private function quickDatabaseCheck($project, $dbDescription, $connectionInfo)
    {
        // Message par défaut de succès
        $messages = ['success' => "Project '{$project->name}' opened successfully."];
        
        try {
            // Vérification ultra-rapide avec timeout court
            $connectionName = "project_quick_check_{$project->id}";
            
            // Configurer un timeout très court pour cette vérification
            $quickConfig = $connectionInfo;
            $quickConfig['options'] = [
                \PDO::ATTR_TIMEOUT => 2, // 2 secondes max
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ];
            
            Config::set("database.connections.{$connectionName}", $quickConfig);
            
            // Test de connexion ultra-rapide
            $pdo = DB::connection($connectionName)->getPdo();
            
            // Test simple d'existence de la DB (requête très rapide)
            $testQuery = "SELECT 1";
            switch ($project->db_type) {
                case 'mysql':
                    $testQuery = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ? LIMIT 1";
                    break;
                case 'pgsql':
                    $testQuery = "SELECT 1 FROM pg_database WHERE datname = ? LIMIT 1";
                    break;
                case 'sqlserver':
                    $testQuery = "SELECT 1 FROM sys.databases WHERE name = ?";
                    break;
            }
            
            $result = DB::connection($connectionName)->select($testQuery, [$dbDescription->dbname]);
            
            // Nettoyer immédiatement
            DB::disconnect($connectionName);
            Config::forget("database.connections.{$connectionName}");
            
            if (empty($result) && $project->db_type !== 'general') {
                $messages = [
                    'warning' => "Project '{$project->name}' opened, but database '{$dbDescription->dbname}' seems inaccessible. You may need to check the connection settings."
                ];
            }
            
        } catch (\Exception $e) {
            Log::warning('Quick database check failed (non-blocking)', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
            
            // Ne pas bloquer l'ouverture, juste avertir
            $messages = [
                'info' => "Project '{$project->name}' opened. Database connectivity will be verified when needed."
            ];
            
            // Nettoyer en cas d'erreur
            try {
                $connectionName = "project_quick_check_{$project->id}";
                DB::disconnect($connectionName);
                Config::forget("database.connections.{$connectionName}");
            } catch (\Exception $cleanupException) {
                // Ignorer les erreurs de nettoyage
            }
        }
        
        return $messages;
    }

    private function prepareConnectionInfo($project, $dbDescription)
    {
        $connectionInfo = null;
        
        if (isset($project->connection_info) && !empty($project->connection_info)) {
            if (is_string($project->connection_info)) {
                $connectionInfo = json_decode($project->connection_info, true);
            } else {
                $connectionInfo = $project->connection_info;
            }
        } else {
            // Utiliser les informations de base si pas de connection_info
            $connectionInfo = [
                'driver' => $this->getDriverFromDbType($project->db_type),
                'host' => 'localhost',
                'database' => $dbDescription->dbname,
                'username' => '',
                'password' => ''
            ];
        }
        
        if (!isset($connectionInfo['driver'])) {
            $connectionInfo['driver'] = $this->getDriverFromDbType($project->db_type);
        }
        
        return $connectionInfo;
    }

    private function checkUserProjectAccess($project)
    {
        $userId = auth()->id();
        
        Log::info('Vérification accès utilisateur', [
            'user_id' => $userId,
            'project_id' => $project->id,
            'project_owner_id' => $project->user_id
        ]);
        
        // Le propriétaire a toujours accès
        if ($project->user_id == $userId) {
            Log::info('Utilisateur identifié comme propriétaire');
            return [
                'allowed' => true,
                'access_level' => 'owner',
                'is_owner' => true,
                'message' => 'Owner access'
            ];
        }
        
        // Vérifier les accès partagés
        $projectAccess = UserProjectAccess::where('user_id', $userId)
            ->where('project_id', $project->id)
            ->first();
        
        if ($projectAccess) {
            Log::info('Accès partagé trouvé', ['access_level' => $projectAccess->access_level]);
            return [
                'allowed' => true,
                'access_level' => $projectAccess->access_level,
                'is_owner' => false,
                'message' => 'Shared access: ' . $projectAccess->access_level
            ];
        }
        
        // Aucun accès
        Log::info('Aucun accès trouvé pour cet utilisateur');
        return [
            'allowed' => false,
            'access_level' => null,
            'is_owner' => false,
            'message' => "You don't have permission to access this project. Contact the project owner or an administrator."
        ];
    }

    private function checkDatabaseAndPrepareMessages($project, $dbDescription, $connectionInfo)
    {
        $messages = ['success' => "Project '{$project->name}' opened successfully."];
        
        try {
            Log::info('Début vérification contenu base de données');
            
            $connectionName = "project_temp_check_{$project->id}";
            Config::set("database.connections.{$connectionName}", $connectionInfo);
            
            // Test de connexion
            $pdo = DB::connection($connectionName)->getPdo();
            Log::info('Connexion PDO réussie');
            
            // Vérifier le contenu de la base de données
            $databaseStats = $this->checkDatabaseContent($connectionName, $project->db_type, $dbDescription->dbname);
            Log::info('Statistiques de la base de données', $databaseStats);
            
            // Nettoyer la connexion temporaire
            DB::disconnect($connectionName);
            Config::forget("database.connections.{$connectionName}");
            Log::info('Connexion temporaire nettoyée');
            
            // Analyser les statistiques et préparer les messages
            $messages = $this->analyzeDatabaseStats($databaseStats, $project->name);
            Log::info('Messages générés', $messages);
            
        } catch (\PDOException $e) {
            Log::error('Erreur PDO lors de la vérification du contenu', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            
            $errorMessage = $this->analyzePDOException($e, $project->db_type, [
                'server' => $connectionInfo['host'],
                'database' => $connectionInfo['database'],
                'username' => $connectionInfo['username'] ?? '',
                'port' => $connectionInfo['port'] ?? null
            ]);
            
            $messages = [
                'error' => "Cannot connect to database for project '{$project->name}': {$errorMessage}. Please check your connection settings."
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur générale lors de la vérification du contenu', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
            
            $messages = [
                'warning' => "Project '{$project->name}' opened, but unable to verify database content. Error: " . $e->getMessage()
            ];
        }
        
        return $messages;
    }

    private function redirectWithMessage($messages, $projectName)
    {
        if (isset($messages['error'])) {
            Log::info('Redirection avec erreur', ['message' => $messages['error']]);
            return redirect()->route('projects.index')->with('error', $messages['error']);
        }
        
        $redirectResponse = redirect()->route('dashboard');
        
        if (isset($messages['warning'])) {
            Log::info('Redirection avec warning', ['message' => $messages['warning']]);
            return $redirectResponse->with('warning', $messages['warning']);
        } elseif (isset($messages['info'])) {
            Log::info('Redirection avec info', ['message' => $messages['info']]);
            return $redirectResponse->with('info', $messages['info']);
        } else {
            Log::info('Redirection avec succès', ['message' => $messages['success']]);
            return $redirectResponse->with('success', $messages['success']);
        }
    }

    /**
    * Vérifier le contenu de la base de données
    */
    private function checkDatabaseContent($connectionName, $dbType, $databaseName)
    {
        $stats = [
            'tables_count' => 0,
            'views_count' => 0,
            'total_records' => 0,
            'user_tables' => [],
            'system_tables_only' => false,
            'connection_error' => false
        ];
        
        try {
            switch ($dbType) {
                case 'mysql':
                    $stats = $this->checkMySQLContent($connectionName, $databaseName);
                    break;
                case 'pgsql':
                    $stats = $this->checkPostgreSQLContent($connectionName);
                    break;
                case 'sqlserver':
                    $stats = $this->checkSQLServerContent($connectionName);
                    break;
                default:
                    Log::warning('Type de base de données non supporté pour la vérification de contenu', [
                        'db_type' => $dbType
                    ]);
                    $stats['connection_error'] = true;
            }
        } catch (\Exception $e) {
            Log::warning('Erreur lors de la vérification du contenu de la BD', [
                'db_type' => $dbType,
                'error' => $e->getMessage()
            ]);
            $stats['connection_error'] = true;
        }
        
        return $stats;
    }

    /**
     * Vérifier le contenu MySQL
     */
    private function checkMySQLContent($connectionName, $databaseName)
    {
        $stats = ['tables_count' => 0, 'views_count' => 0, 'total_records' => 0, 'user_tables' => []];
        
        // Compter les tables utilisateur
        $tables = DB::connection($connectionName)->select("
            SELECT TABLE_NAME, TABLE_ROWS 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_TYPE = 'BASE TABLE'
            AND TABLE_NAME NOT LIKE 'mysql_%'
            AND TABLE_NAME NOT LIKE 'sys_%'
            AND TABLE_NAME NOT LIKE 'performance_schema%'
            AND TABLE_NAME NOT LIKE 'information_schema%'
        ", [$databaseName]);
        
        $stats['tables_count'] = count($tables);
        
        foreach ($tables as $table) {
            $stats['user_tables'][] = $table->TABLE_NAME;
            $stats['total_records'] += (int)$table->TABLE_ROWS;
        }
        
        // Compter les vues
        $views = DB::connection($connectionName)->select("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.VIEWS 
            WHERE TABLE_SCHEMA = ?
        ", [$databaseName]);
        
        $stats['views_count'] = $views[0]->count ?? 0;
        
        return $stats;
    }

    /**
     * Vérifier le contenu PostgreSQL
     */
    private function checkPostgreSQLContent($connectionName)
    {
        $stats = ['tables_count' => 0, 'views_count' => 0, 'total_records' => 0, 'user_tables' => []];
        
        // Compter les tables utilisateur
        $tables = DB::connection($connectionName)->select("
            SELECT 
                schemaname, 
                tablename,
                (SELECT reltuples::bigint AS estimate FROM pg_class WHERE relname = tablename) as row_estimate
            FROM pg_tables 
            WHERE schemaname = 'public'
        ");
        
        $stats['tables_count'] = count($tables);
        
        foreach ($tables as $table) {
            $stats['user_tables'][] = $table->tablename;
            $stats['total_records'] += (int)($table->row_estimate ?? 0);
        }
        
        // Compter les vues
        $views = DB::connection($connectionName)->select("
            SELECT COUNT(*) as count 
            FROM information_schema.views 
            WHERE table_schema = 'public'
        ");
        
        $stats['views_count'] = $views[0]->count ?? 0;
        
        return $stats;
    }

    /**
     * Vérifier le contenu SQL Server
     */
    private function checkSQLServerContent($connectionName)
    {
        $stats = ['tables_count' => 0, 'views_count' => 0, 'total_records' => 0, 'user_tables' => []];
        
        // Compter les tables utilisateur
        $tables = DB::connection($connectionName)->select("
            SELECT 
                t.name as table_name,
                ISNULL(p.rows, 0) as row_count
            FROM sys.tables t
            LEFT JOIN sys.partitions p ON t.object_id = p.object_id 
            WHERE p.index_id IN (0,1)
            AND t.is_ms_shipped = 0
        ");
        
        $stats['tables_count'] = count($tables);
        
        foreach ($tables as $table) {
            $stats['user_tables'][] = $table->table_name;
            $stats['total_records'] += (int)$table->row_count;
        }
        
        // Compter les vues
        $views = DB::connection($connectionName)->select("
            SELECT COUNT(*) as count 
            FROM sys.views 
            WHERE is_ms_shipped = 0
        ");
        
        $stats['views_count'] = $views[0]->count ?? 0;
        
        return $stats;
    }

    /**
     * Analyser les statistiques et générer les messages appropriés
     */
    private function analyzeDatabaseStats($stats, $projectName)
    {
        $messages = [];
        
        // Base de données complètement vide (aucune table, aucune vue)
        if ($stats['tables_count'] === 0 && $stats['views_count'] === 0) {
            $messages['warning'] = "Project '{$projectName}' opened successfully, but the database is completely empty. No tables or views found. You need to create tables or import data to start working with this project.";
            return $messages;
        }
        
        // Seulement des vues, pas de tables utilisateur
        if ($stats['tables_count'] === 0 && $stats['views_count'] > 0) {
            $viewText = $stats['views_count'] === 1 ? 'view' : 'views';
            $messages['info'] = "Project '{$projectName}' opened successfully. The database contains {$stats['views_count']} {$viewText} but no user tables. Consider creating tables or importing data.";
            return $messages;
        }
        
        // Tables présentes mais vides (aucune donnée)
        if ($stats['tables_count'] > 0 && $stats['total_records'] === 0) {
            $tableText = $stats['tables_count'] === 1 ? 'table' : 'tables';
            $tableList = implode(', ', array_slice($stats['user_tables'], 0, 3));
            if (count($stats['user_tables']) > 3) {
                $tableList .= '...';
            }
            
            $messages['warning'] = "Project '{$projectName}' opened successfully. Found {$stats['tables_count']} {$tableText} ({$tableList}) but they contain no data. Import data or add records to start analyzing your database.";
            return $messages;
        }
        
        // Très peu de données (moins de 10 enregistrements au total)
        if ($stats['tables_count'] > 0 && $stats['total_records'] > 0 && $stats['total_records'] < 10) {
            $tableText = $stats['tables_count'] === 1 ? 'table' : 'tables';
            $recordText = $stats['total_records'] === 1 ? 'record' : 'records';
            
            $messages['info'] = "Project '{$projectName}' opened successfully. Database contains {$stats['tables_count']} {$tableText} with only {$stats['total_records']} {$recordText}. Consider adding more data for better analysis.";
            return $messages;
        }
        
        // Base de données avec du contenu normal
        if ($stats['tables_count'] > 0 && $stats['total_records'] >= 10) {
            $tableText = $stats['tables_count'] === 1 ? 'table' : 'tables';
            $summary = "{$stats['tables_count']} {$tableText}";
            
            if ($stats['views_count'] > 0) {
                $viewText = $stats['views_count'] === 1 ? 'view' : 'views';
                $summary .= " and {$stats['views_count']} {$viewText}";
            }
            
            $summary .= " with approximately " . number_format($stats['total_records']) . " records";
            
            $messages['success'] = "Project '{$projectName}' opened successfully. Database contains {$summary}. Ready for analysis!";
            return $messages;
        }
        
        // Cas par défaut (ne devrait pas arriver)
        $messages['info'] = "Project '{$projectName}' opened successfully.";
        return $messages;
    }

    

    public function disconnect(Request $request)
    {
        try {
            // Récupérer les informations du projet en cours depuis la session
            $currentProject = session('current_project');
            
            if ($currentProject) {
                // Récupération de l'ID du projet et du nom de connexion
                $projectId = $currentProject['id'];
                $connectionName = "project_{$projectId}";
                
                // Log pour le débogage
                Log::info("Tentative de déconnexion", [
                    'connection' => $connectionName,
                    'db_type' => $currentProject['db_type'] ?? 'non défini'
                ]);
                
                // Fermer la connexion de base de données, quel que soit le type
                DB::disconnect($connectionName);
                
                // Supprimer la connexion de la configuration
                Config::set("database.connections.{$connectionName}", null);
                
                // Supprimer les informations de connexion de la session
                session()->forget('current_project');
                session()->save();
                
                // Message flash pour l'utilisateur
                session()->flash('success', 'Déconnecté de la base de données avec succès');
            } else {
                Log::warning("Tentative de déconnexion sans connexion active en session");
            }
            
            // Pour les requêtes AJAX, retourner une réponse JSON
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true]);
            }
            
            // Sinon, rediriger vers la liste des projets
            return redirect()->route('projects.index');
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la déconnexion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            
            return redirect()->back()->with('error', 'Erreur lors de la déconnexion: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete d'un projet
     */
    public function softDelete($id)
    {
        try {
            Log::info('Tentative de soft delete', [
                'project_id' => $id,
                'user_id' => auth()->id()
            ]);

            $project = Project::where('user_id', auth()->id())
                ->where('id', $id)
                ->first();

            if (!$project) {
                return redirect()->back()->with('error', 'Project not found');
            }

            if ($project->trashed()) {
                return redirect()->back()->with('error', 'This project has already been deleted');
            }

            
            $result = DB::statement("
                UPDATE projects 
                SET deleted_at = GETDATE(), 
                    updated_at = GETDATE() 
                WHERE id = ? AND user_id = ?
            ", [$id, auth()->id()]);

            if ($result) {
                Log::info('Projet supprimé avec succès', ['project_id' => $id]);
                return redirect()->back()->with('success', 'Project deleted with success');

            } else {
                throw new \Exception('Échec de la mise à jour');
            }

        } catch (\Exception $e) {
            Log::error('Erreur soft delete:', [
                'error' => $e->getMessage(),
                'project_id' => $id
            ]);

            return redirect()->back()->with('error', 'Erreur serveur: ' . $e->getMessage());
        }
    }

    /**
     * Restaure un projet supprimé
     */
    public function restore($id)
    {
        try {
            // Vérifier si l'utilisateur est admin
            if (!auth()->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé. Seuls les administrateurs peuvent restaurer des projets.'
                ], 403);
            }

            $project = Project::withTrashed()
                ->where('user_id', auth()->id())
                ->findOrFail($id);
            
            if (!$project->trashed()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ce projet n\'est pas supprimé'
                ], 400);
            }

            $project->restore();

            Log::info('Projet restauré par admin', [
                'project_id' => $id,
                'project_name' => $project->name,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Projet restauré avec succès'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur dans ProjectController::restore', [
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la restauration du projet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Suppression définitive d'un projet
     */
    public function forceDeleteProject($id)
    {
        try {
            // Log pour débugger
            Log::info('🚨 ProjectController::forceDeleteProject appelée', [
                'project_id' => $id,
                'user_id' => auth()->id()
            ]);

            // Vérifier les permissions (ajustez selon votre logique)
            if (!auth()->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé. Seuls les administrateurs peuvent restaurer des projets.'
                ], 403);
            }

            $project = Project::withTrashed()->findOrFail($id);
            $projectName = $project->name;
            $projectOwner = $project->user->name ?? 'Utilisateur supprimé';

            Log::info('🗑️ Début de suppression forcée du projet', [
                'project_id' => $id,
                'project_name' => $projectName,
                'admin_id' => auth()->id()
            ]);

            // Analyser toutes les dépendances
            $dependencies = $this->analyzeProjectDependencies($project->id);
            
            Log::info('📊 Dépendances trouvées', [
                'project_id' => $id,
                'dependencies' => $dependencies
            ]);

            // Supprimer toutes les dépendances dans une transaction
            DB::transaction(function () use ($project, $dependencies) {
                $this->deleteProjectDependencies($project->id, $dependencies);
                
                // ✅ IMPORTANT: Utiliser forceDelete() sur l'instance Eloquent
                $project->forceDelete();
            });

            Log::warning('✅ Projet supprimé définitivement avec succès', [
                'project_id' => $id,
                'project_name' => $projectName,
                'project_owner' => $projectOwner,
                'dependencies_deleted' => $dependencies,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Projet et toutes ses dépendances supprimés définitivement',
                'details' => [
                    'project_name' => $projectName,
                    'dependencies_removed' => $dependencies
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Erreur dans ProjectController::forceDeleteProject', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la suppression définitive: ' . $e->getMessage()
            ], 500);
        }
    }

    private function analyzeProjectDependencies($projectId)
    {
        $dependencies = [];

        try {
            Log::info('📊 Analyse des dépendances pour le projet', ['project_id' => $projectId]);

            // 1. Bases de données
            $dbDescriptions = DbDescription::where('project_id', $projectId)->get();
            $dependencies['databases'] = $dbDescriptions->count();

            if ($dependencies['databases'] > 0) {
                $dbIds = $dbDescriptions->pluck('id');
                Log::info('📁 Bases de données trouvées', ['count' => $dependencies['databases'], 'db_ids' => $dbIds->toArray()]);
                
                // 2. Tables
                $dependencies['tables'] = DB::table('table_description')
                    ->whereIn('dbid', $dbIds)
                    ->count();
                    
                // 3. Colonnes
                $tableIds = DB::table('table_description')
                    ->whereIn('dbid', $dbIds)
                    ->pluck('id');
                    
                if ($tableIds->isNotEmpty()) {
                    $dependencies['columns'] = DB::table('table_structure')
                        ->whereIn('id_table', $tableIds)
                        ->count();
                        
                    $dependencies['indexes'] = DB::table('table_index')
                        ->whereIn('id_table', $tableIds)
                        ->count();
                        
                    $dependencies['relations'] = DB::table('table_relations')
                        ->whereIn('id_table', $tableIds)
                        ->count();
                }
                
                // 4. Triggers
                $dependencies['triggers'] = DB::table('trigger_description')
                    ->whereIn('dbid', $dbIds)
                    ->count();
            }

            // 5. Releases
            $dependencies['releases'] = DB::table('release')
                ->where('project_id', $projectId)
                ->count();
                
            // 6. Permissions utilisateur
            $dependencies['user_permissions'] = DB::table('user_project_permission')
                ->where('project_id', $projectId)
                ->count();

            Log::info('📊 Analyse terminée', ['dependencies' => $dependencies]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de l\'analyse des dépendances', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return array_filter($dependencies, function($count) {
            return $count > 0;
        });
    }

    private function deleteProjectDependencies($projectId, $dependencies)
    {
        Log::info('🗑️ Début suppression des dépendances', [
            'project_id' => $projectId,
            'dependencies' => $dependencies
        ]);

        try {
            $dbIds = DbDescription::where('project_id', $projectId)->pluck('id');
            
            if ($dbIds->isNotEmpty()) {
                Log::info('🔍 IDs des bases de données à traiter', ['db_ids' => $dbIds->toArray()]);
                
                $tableIds = DB::table('table_description')
                    ->whereIn('dbid', $dbIds)
                    ->pluck('id');
                    
                if ($tableIds->isNotEmpty()) {
                    Log::info('🔍 IDs des tables à traiter', ['table_ids' => $tableIds->toArray()]);
                    
                    // Supprimer dans l'ordre: enfants d'abord
                    
                    if (isset($dependencies['columns'])) {
                        $deleted = DB::table('table_structure')->whereIn('id_table', $tableIds)->delete();
                        Log::info("✅ Colonnes supprimées: {$deleted}");
                    }
                    
                    if (isset($dependencies['indexes'])) {
                        $deleted = DB::table('table_index')->whereIn('id_table', $tableIds)->delete();
                        Log::info("✅ Index supprimés: {$deleted}");
                    }
                    
                    if (isset($dependencies['relations'])) {
                        $deleted = DB::table('table_relations')->whereIn('id_table', $tableIds)->delete();
                        Log::info("✅ Relations supprimées: {$deleted}");
                    }
                }
                
                if (isset($dependencies['triggers'])) {
                    $triggerIds = DB::table('trigger_description')->whereIn('dbid', $dbIds)->pluck('id');
                    if ($triggerIds->isNotEmpty()) {
                        $deleted = DB::table('trigger_information')->whereIn('id_trigger', $triggerIds)->delete();
                        Log::info("✅ Informations de triggers supprimées: {$deleted}");
                    }
                    $deleted = DB::table('trigger_description')->whereIn('dbid', $dbIds)->delete();
                    Log::info("✅ Triggers supprimés: {$deleted}");
                }
                
                if (isset($dependencies['tables'])) {
                    $deleted = DB::table('table_description')->whereIn('dbid', $dbIds)->delete();
                    Log::info("✅ Tables supprimées: {$deleted}");
                }
                
                if (isset($dependencies['databases'])) {
                    $deleted = DbDescription::where('project_id', $projectId)->delete();
                    Log::info("✅ Bases de données supprimées: {$deleted}");
                }
            }
            
            if (isset($dependencies['releases'])) {
                $deleted = DB::table('release')->where('project_id', $projectId)->delete();
                Log::info("✅ Releases supprimées: {$deleted}");
            }
            
            if (isset($dependencies['user_permissions'])) {
                $deleted = DB::table('user_project_permission')->where('project_id', $projectId)->delete();
                Log::info("✅ Permissions supprimées: {$deleted}");
            }

            Log::info('✅ Suppression des dépendances terminée');

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la suppression des dépendances', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }


    /**
     * Affiche les projets supprimés pour l'utilisateur connecté
     */
    public function deleted()
    {
        try {
            // Vérifier si l'utilisateur est admin
            if (!auth()->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé. Seuls les administrateurs peuvent voir les projets supprimés.'
                ], 403);
            }

            $deletedProjects = Project::onlyTrashed()
                ->where('user_id', auth()->id())
                ->orderBy('deleted_at', 'desc')
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'description' => $project->description,
                        'db_type' => $project->db_type,
                        'deleted_at' => $project->deleted_at ? $project->deleted_at->toISOString() : null,
                        'created_at' => $project->created_at ? $project->created_at->format('Y-m-d H:i:s') : null
                        //'updated_at' => $project->updated_at ? $project->updated_at->format('Y-m-d H:i:s') : null
                    ];
                });

            return response()->json([
                'success' => true,
                'projects' => $deletedProjects
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur dans ProjectController::deleted', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du chargement des projets supprimés: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mise a jour projet
     */
    public function update(Request $request, $id)
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($id);
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'db_type' => 'required|in:sqlserver,mysql,pgsql' // Changé 'postgres' en 'pgsql'
            ]);

            $project->update($validated);

            Log::info('Projet mis à jour', [
                'project_id' => $id,
                'project_name' => $project->name,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'project' => $project,
                'message' => 'Projet mis à jour avec succès'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur dans ProjectController::update', [
                'id' => $id,
                'request' => $request->all(),
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la mise à jour du projet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retourne tous les projets actifs pour les API
     */
    public function apiIndex()
    {
        try {
            $projects = Project::where('user_id', auth()->id())
                ->whereNull('deleted_at')
                ->select('id', 'name', 'description', 'db_type')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'projects' => $projects
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur dans ProjectController::apiIndex', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du chargement des projets: ' . $e->getMessage()
            ], 500);
        }
    }

    public function testConnection(Request $request, Project $project)
    {
        try {
            Log::info('Début test de connexion', [
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'request_data' => $request->except(['password']) // Exclure le mot de passe des logs
            ]);

            // Vérifier les permissions
            $userCanAccess = $this->checkUserProjectAccess($project);
            
            if (!$userCanAccess['allowed'] || !$userCanAccess['is_owner']) {
                Log::warning('Accès refusé pour test de connexion', [
                    'project_id' => $project->id,
                    'user_id' => auth()->id(),
                    'access_check' => $userCanAccess
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Only the project owner can test database connections.'
                ], 403);
            }

            // Validation des données
            $rules = [
                'server' => 'required|string',
                'database' => 'required|string',
                'username' => 'nullable|string',
                'password' => 'nullable|string',
            ];

            // Ajouter la validation du port selon le type de DB
            if ($project->db_type !== 'sqlserver') {
                $rules['port'] = 'required|numeric|min:1|max:65535';
            } else {
                $rules['port'] = 'nullable|numeric|min:1|max:65535';
                $rules['authMode'] = 'required|in:windows,sql';
            }

            $validated = $request->validate($rules);

            Log::info('Validation réussie', [
                'project_id' => $project->id,
                'db_type' => $project->db_type,
                'server' => $validated['server'],
                'database' => $validated['database']
            ]);

            // Préparation de la configuration de connexion
            $driver = $this->getDriverFromDbType($project->db_type);
            
            $config = [
                'driver' => $driver,
                'host' => $validated['server'],
                'database' => $validated['database'],
            ];

            // Configuration selon le type de base de données
            if ($project->db_type === 'sqlserver') {
                if (isset($validated['authMode']) && $validated['authMode'] === 'windows') {
                    $config['trusted_connection'] = true;
                    Log::info('Configuration SQL Server avec authentification Windows');
                } else {
                    $config['username'] = $validated['username'] ?? '';
                    $config['password'] = $validated['password'] ?? '';
                    Log::info('Configuration SQL Server avec authentification SQL');
                }
            } else {
                $config['port'] = $validated['port'];
                $config['username'] = $validated['username'] ?? '';
                $config['password'] = $validated['password'] ?? '';
                
                // Configuration spécifique pour PostgreSQL
                if ($project->db_type === 'pgsql') {
                    $config['charset'] = 'utf8';
                    $config['prefix'] = '';
                    $config['prefix_indexes'] = true;
                    $config['schema'] = 'public';
                    $config['sslmode'] = 'prefer';
                    Log::info('Configuration PostgreSQL appliquée');
                }
                
                Log::info('Configuration MySQL/PostgreSQL', [
                    'driver' => $driver,
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'database' => $config['database']
                ]);
            }

            // Test de connexion temporaire
            $testConnectionName = "test_connection_" . uniqid();
            
            Log::info('Tentative de connexion', [
                'connection_name' => $testConnectionName,
                'driver' => $driver
            ]);
            
            Config::set("database.connections.{$testConnectionName}", $config);
            
            try {
                // Tenter la connexion
                $pdo = DB::connection($testConnectionName)->getPdo();
                Log::info('Connexion PDO établie avec succès');
                
                // Test supplémentaire : vérifier que la base de données existe
                $testQuery = $this->getTestQuery($project->db_type, $validated['database']);
                Log::info('Exécution de la requête de test', ['query' => $testQuery]);
                
                $result = DB::connection($testConnectionName)->select($testQuery);
                Log::info('Requête de test exécutée avec succès', ['result_count' => count($result)]);
                
                // Vérifier que la base de données existe réellement
                if (empty($result)) {
                    throw new \Exception("Database '{$validated['database']}' does not exist or is not accessible.");
                }
                
                // Nettoyer la connexion temporaire
                DB::disconnect($testConnectionName);
                Config::forget("database.connections.{$testConnectionName}");
                
                Log::info('Test de connexion réussi', [
                    'project_id' => $project->id,
                    'database' => $validated['database'],
                    'driver' => $driver
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Connection test successful!'
                ]);
                
            } catch (\PDOException $e) {
                // Nettoyer la connexion en cas d'erreur
                try {
                    DB::disconnect($testConnectionName);
                    Config::forget("database.connections.{$testConnectionName}");
                } catch (\Exception $cleanupException) {
                    Log::warning('Erreur lors du nettoyage de la connexion', [
                        'cleanup_error' => $cleanupException->getMessage()
                    ]);
                }
                
                // Analyser l'erreur PDO
                $errorMessage = $this->analyzePDOException($e, $project->db_type, $validated);
                
                Log::warning('Test de connexion échoué (PDO)', [
                    'project_id' => $project->id,
                    'error_code' => $e->getCode(),
                    'error_message' => $e->getMessage(),
                    'analyzed_message' => $errorMessage
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => $errorMessage
                ]);
                
            } catch (\Exception $e) {
                // Nettoyer la connexion en cas d'erreur
                try {
                    DB::disconnect($testConnectionName);
                    Config::forget("database.connections.{$testConnectionName}");
                } catch (\Exception $cleanupException) {
                    Log::warning('Erreur lors du nettoyage de la connexion', [
                        'cleanup_error' => $cleanupException->getMessage()
                    ]);
                }
                
                Log::error('Erreur générale lors du test de connexion', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Connection test failed: ' . $e->getMessage()
                ]);
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Erreur de validation dans testConnection', [
                'project_id' => $project->id,
                'validation_errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . collect($e->errors())->flatten()->first()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Erreur générale dans testConnection', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'An unexpected error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    

}