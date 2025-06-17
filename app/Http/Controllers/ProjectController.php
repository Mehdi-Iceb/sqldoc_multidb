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


class ProjectController extends Controller
{

    public function index()
    {
        $projects = Project::where('user_id', auth()->id())
            ->whereNull('deleted_at') // Exclure les projets supprimés
            ->get();
        
        Log::info('Projects récupérés :', ['count' => $projects->count()]);
        
        return Inertia::render('Projects/Index', [
            'projects' => $projects
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
                
                // EXTRACTION ET SAUVEGARDE DE LA STRUCTURE COMPLÈTE
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
            // Récupérer le projet (en incluant les projets supprimés pour pouvoir les ouvrir si nécessaire)
            $project = Project::withTrashed()->findOrFail($id);
            
            // Vérifier si le projet est supprimé
            if ($project->trashed()) {
                return redirect()->route('projects.index')
                    ->with('error', 'Ce projet a été supprimé et ne peut pas être ouvert.');
            }
            
            // Rechercher la description de BD
            $dbDescription = DbDescription::where('project_id', $project->id)->first();
            
            if (!$dbDescription) {
                return redirect()->route('projects.index')
                    ->with('error', 'Aucune base de données trouvée pour ce projet.');
            }
            
            // Définir les informations de connexion
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
            
            // Mettre à jour la session
            session([
                'current_project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'db_type' => $project->db_type,
                    'connection' => $connectionInfo
                ],
                'current_db_id' => $dbDescription->id
            ]);
            
            return redirect()->route('dashboard')
                ->with('success', 'Projet "' . $project->name . '" ouvert avec succès.');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'ouverture du projet', [
                'project_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('projects.index')
                ->with('error', 'Impossible d\'ouvrir le projet: ' . $e->getMessage());
        }
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