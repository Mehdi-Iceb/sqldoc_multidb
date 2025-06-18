<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\DbDescription;
use App\Services\DatabaseStructureService;
use App\Models\UserProjectAccess;


class ProjectController extends Controller
{

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
            'db_type' => 'required|in:sqlserver,mysql,pgsql', // Changé 'postgres' en 'pgsql'
            'description' => 'nullable|string|max:1000'
            //'release' => 'nullable|string|max:10' 
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
                'pgsql' => 'PostgreSQL' // Changé 'pgsql' au lieu de 'postgres'
            ]
        ]);
    }

    public function connect(Project $project)
    {
        return Inertia::render('Projects/Connect', [
            'project' => $project
        ]);
    }

    public function handleConnect(Request $request, Project $project)
    {
        try {
            $validated = $request->validate([
                'server' => 'required',
                'database' => 'required',
                'port' => $project->db_type !== 'sqlserver' ? 'required' : 'nullable',
                'authMode' => $project->db_type === 'sqlserver' ? 'required|in:windows,sql' : 'nullable',
                'username' => 'required_if:authMode,sql',
                'password' => 'required_if:authMode,sql',
                'description' => 'nullable|string|max:1000',
            ]);

            // Conversion du type pour le driver Laravel
            $driver = $this->getDriverFromDbType($project->db_type);

            $config = [
                'driver' => $driver,
                'host' => $validated['server'],
                'database' => $validated['database'],
            ];

            if ($project->db_type === 'sqlserver') {
                if ($validated['authMode'] === 'windows') {
                    $config['trusted_connection'] = true;
                } else {
                    $config['username'] = $validated['username'] ?? '';
                    $config['password'] = $validated['password'] ?? '';
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
                }
            }

            // Test de connexion avec gestion d'erreurs détaillée
            $connectionName = "project_{$project->id}";
            Config::set("database.connections.{$connectionName}", $config);
            
            try {
                // Tenter la connexion
                $pdo = DB::connection($connectionName)->getPdo();
                
                // Test supplémentaire : vérifier que la base de données existe vraiment
                $testQuery = $this->getTestQuery($project->db_type, $validated['database']);
                $result = DB::connection($connectionName)->select($testQuery);
                
                Log::info('Connexion réussie et base de données vérifiée', [
                    'project_id' => $project->id,
                    'database' => $validated['database'],
                    'driver' => $driver
                ]);

            } catch (\PDOException $e) {
                // Analyser le type d'erreur PDO
                $errorMessage = $this->analyzePDOException($e, $project->db_type, $validated);
                
                Log::error('Erreur de connexion PDO', [
                    'project_id' => $project->id,
                    'error_code' => $e->getCode(),
                    'error_message' => $e->getMessage(),
                    'analyzed_message' => $errorMessage
                ]);
                
                return back()->with('error', $errorMessage);
                
            } catch (\Exception $e) {
                Log::error('Erreur générale de connexion', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage()
                ]);
                
                return back()->with('error', 'Connection failed: ' . $e->getMessage());
            }

            // Enregistrement des informations de la base de données
            try {
                $dbDescription = DbDescription::create([
                    'user_id' => auth()->id(),
                    'dbname' => $validated['database'],
                    'project_id' => $project->id,
                    'description' => $validated['description'] ?? null,
                ]);
                
                // EXTRACTION ET SAUVEGARDE DE LA STRUCTURE COMPLETE
                try {
                    $databaseStructureService = new DatabaseStructureService();
                    $result = $databaseStructureService->extractAndSaveAllStructures($connectionName, $dbDescription->id);
                    
                    Log::info('Structure de la base de données extraite et sauvegardée avec succès', [
                        'success' => $result,
                        'project_id' => $project->id,
                        'database' => $validated['database']
                    ]);
                    
                } catch (\Exception $structureException) {
                    Log::error('Erreur lors de l\'extraction de la structure de la base de données', [
                        'error' => $structureException->getMessage(),
                        'trace' => $structureException->getTraceAsString()
                    ]);
                    // Continue même si l'extraction échoue
                }
                
                session(['current_db_id' => $dbDescription->id]);
                
            } catch (\Exception $e) {
                Log::warning('Impossible d\'enregistrer dans db_description', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Définir en tant que connexion active
            session([
                'current_project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'connection' => $config,
                    'db_type' => $project->db_type
                ]
            ]);
            
            Log::info('Connexion réussie', [
                'project_id' => $project->id,
                'db_type' => $project->db_type,
                'driver' => $driver,
                'database' => $validated['database']
            ]);

            if (!$request->wantsJson()) {
                return redirect()->route('dashboard')->with('success', 'Connection successful to ' . $validated['database']);
            }

            return response()->json([
                'success' => true,
                'redirect' => route('dashboard')
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Erreurs de validation
            return back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            Log::error('Erreur générale dans handleConnect', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            if (!$request->wantsJson()) {
                return back()->with('error', 'An unexpected error occurred: ' . $e->getMessage());
            }
            
            return response()->json(['error' => $e->getMessage()], 500);
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
        if (strpos($errorMessage, 'Cannot open database') !== false) {
            return "Database '{$validated['database']}' cannot be opened or does not exist on the SQL Server. Please check the database name.";
        }
        
        if (strpos($errorMessage, 'Login failed') !== false) {
            return "Login failed for user '{$validated['username']}'. Please check your credentials and ensure the user has access to SQL Server.";
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
        try {
            Log::info('=== DÉBUT OUVERTURE PROJET ===', ['project_id' => $id, 'user_id' => auth()->id()]);
            
            // Récupérer le projet
            $project = Project::withTrashed()->findOrFail($id);
            Log::info('Projet trouvé', ['project_name' => $project->name, 'db_type' => $project->db_type, 'owner_id' => $project->user_id]);
            
            // Vérifier si le projet est supprimé
            if ($project->trashed()) {
                return redirect()->route('projects.index')
                    ->with('error', 'Ce projet a été supprimé et ne peut pas être ouvert.');
            }
            
            // *** VÉRIFICATION DES PERMISSIONS D'ACCÈS ***
            $userCanAccess = $this->checkUserProjectAccess($project);
            
            if (!$userCanAccess['allowed']) {
                return redirect()->route('projects.index')
                    ->with('error', $userCanAccess['message']);
            }
            
            // Récupérer le niveau d'accès pour l'affichage
            $accessLevel = $userCanAccess['access_level'];
            $isOwner = $userCanAccess['is_owner'];
            Log::info('Accès autorisé', [
                'access_level' => $accessLevel, 
                'is_owner' => $isOwner,
                'user_id' => auth()->id(),
                'project_owner_id' => $project->user_id
            ]);
            
            // Rechercher la description de BD
            $dbDescription = DbDescription::where('project_id', $project->id)->first();
            Log::info('DbDescription', ['found' => $dbDescription ? 'oui' : 'non', 'dbname' => $dbDescription->dbname ?? 'N/A']);
            
            // *** CAS : Projet jamais connecté à une base de données ***
            if (!$dbDescription) {
                Log::info('Projet jamais connecté à une base de données', [
                    'project_id' => $project->id, 
                    'access_level' => $accessLevel,
                    'is_owner' => $isOwner
                ]);
                
                if ($isOwner) {
                    // Le propriétaire peut configurer la connexion
                    Log::info('Redirection vers connexion pour le propriétaire', ['project_id' => $project->id]);
                    return redirect()->route('projects.connect', $project->id)
                        ->with('info', "Project '{$project->name}' needs to be connected to a database. Please provide the database connection details below.");
                } else {
                    // Les utilisateurs avec accès partagé ne peuvent pas configurer
                    Log::info('Accès refusé pour utilisateur non-propriétaire', [
                        'project_id' => $project->id,
                        'user_id' => auth()->id(),
                        'owner_id' => $project->user_id
                    ]);
                    return redirect()->route('projects.index')
                        ->with('warning', "Project '{$project->name}' is not configured yet. Only the project owner can set up the database connection. Please contact the project owner to configure this project.");
                }
            }
            
            // Définir les informations de connexion (CODE ORIGINAL INCHANGÉ)
            $connectionInfo = null;
            if (isset($project->connection_info) && !empty($project->connection_info)) {
                if (is_string($project->connection_info)) {
                    $connectionInfo = json_decode($project->connection_info, true);
                } else {
                    $connectionInfo = $project->connection_info;
                }
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
            
            Log::info('Informations de connexion préparées', [
                'driver' => $connectionInfo['driver'],
                'host' => $connectionInfo['host'],
                'database' => $connectionInfo['database']
            ]);
            
            // VÉRIFICATION DU CONTENU DE LA BASE DE DONNÉES (CODE ORIGINAL INCHANGÉ)
            $messages = ['success' => "Project '{$project->name}' opened successfully."]; // Valeur par défaut
            
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
                
            } catch (\Exception $e) {
                Log::error('Erreur lors de la vérification du contenu de la base de données', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // En cas d'erreur de connexion, on continue quand même mais avec un avertissement
                $messages = [
                    'warning' => "Project '{$project->name}' opened, but unable to verify database content. The database may be inaccessible."
                ];
            }
            
            // Mettre à jour la session avec les informations d'accès
            session([
                'current_project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'db_type' => $project->db_type,
                    'connection' => $connectionInfo,
                    'access_level' => $accessLevel,
                    'is_owner' => $isOwner
                ],
                'current_db_id' => $dbDescription->id
            ]);
            
            Log::info('Session mise à jour');
            
            // Rediriger avec les messages appropriés (CODE ORIGINAL INCHANGÉ)
            $redirectResponse = redirect()->route('dashboard');
            
            if (isset($messages['error'])) {
                Log::info('Redirection avec erreur', ['message' => $messages['error']]);
                return $redirectResponse->with('error', $messages['error']);
            } elseif (isset($messages['warning'])) {
                Log::info('Redirection avec warning', ['message' => $messages['warning']]);
                return $redirectResponse->with('warning', $messages['warning']);
            } elseif (isset($messages['info'])) {
                Log::info('Redirection avec info', ['message' => $messages['info']]);
                return $redirectResponse->with('info', $messages['info']);
            } else {
                Log::info('Redirection avec succès', ['message' => $messages['success'] ?? 'Message par défaut']);
                return $redirectResponse->with('success', $messages['success'] ?? "Project '{$project->name}' opened successfully.");
            }
                    
        } catch (\Exception $e) {
            Log::error('=== ERREUR LORS DE L\'OUVERTURE DU PROJET ===', [
                'project_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('projects.index')
                ->with('error', 'Unable to open project: ' . $e->getMessage());
        }
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
                return response()->json([
                    'success' => false,
                    'error' => 'Projet non trouvé'
                ], 404);
            }

            if ($project->trashed()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ce projet est déjà supprimé'
                ], 400);
            }

            
            $result = DB::statement("
                UPDATE projects 
                SET deleted_at = GETDATE(), 
                    updated_at = GETDATE() 
                WHERE id = ? AND user_id = ?
            ", [$id, auth()->id()]);

            if ($result) {
                Log::info('Projet supprimé avec succès', ['project_id' => $id]);
                return response()->json([
                    'success' => true,
                    'message' => 'Projet supprimé avec succès'
                ]);
            } else {
                throw new \Exception('Échec de la mise à jour');
            }

        } catch (\Exception $e) {
            Log::error('Erreur soft delete:', [
                'error' => $e->getMessage(),
                'project_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
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
    public function forceDelete($id)
    {
        try {
            // Vérifier si l'utilisateur est admin
            if (!auth()->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé. Seuls les administrateurs peuvent supprimer définitivement des projets.'
                ], 403);
            }

            $project = Project::withTrashed()
                ->where('user_id', auth()->id())
                ->findOrFail($id);
            
            $dbDescriptionsCount = DbDescription::where('project_id', $project->id)->count();
            
            if ($dbDescriptionsCount > 0) {
                return response()->json([
                    'success' => false,
                    'error' => "Impossible de supprimer définitivement ce projet car il contient {$dbDescriptionsCount} base(s) de données associée(s)."
                ], 400);
            }

            $projectName = $project->name;
            $project->forceDelete();

            Log::info('Projet supprimé définitivement par admin', [
                'project_id' => $id,
                'project_name' => $projectName,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Projet supprimé définitivement'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur dans ProjectController::forceDelete', [
                'id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la suppression définitive: ' . $e->getMessage()
            ], 500);
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

}