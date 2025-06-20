<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasProjectPermissions;
use Illuminate\Http\Request;
use App\Models\TableDescription;
use App\Models\ViewDescription;
use App\Models\PsDescription;
use App\Models\FunctionDescription;
use App\Models\TriggerDescription;
use Illuminate\Support\Facades\Log;
use App\Models\TableStructure;

class DatabaseStructureController extends Controller
{
    use HasProjectPermissions;

    public function index(Request $request)
    {
        try {
            

            // Récupérer les permissions pour les inclure dans la réponse
            $permissions = $request->get('user_project_permission');
            
            Log::info('DatabaseStructure - Début récupération', [
                'user_id' => auth()->id(),
                'permissions' => $permissions['level'] ?? 'none'
            ]);

            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            Log::info('Récupération de la structure pour dbId: ' . $dbId);
            
            if (!$dbId) {
                Log::info('Aucune base de données sélectionnée dans la session');
                
                // Vérifier si nous sommes sur une page qui nécessite une structure de DB
                $referer = $request->header('referer', '');
                $currentPath = parse_url($referer, PHP_URL_PATH) ?? '';
                
                $pathsWithoutDb = ['/projects', '/projects/create'];
                $isOnProjectsPage = false;
                
                foreach ($pathsWithoutDb as $path) {
                    if (str_starts_with($currentPath, $path)) {
                        $isOnProjectsPage = true;
                        break;
                    }
                }
                
                if ($isOnProjectsPage) {
                    Log::info('Page projets détectée, retour d\'une structure vide');
                    return response()->json([
                        'tables' => [],
                        'views' => [],
                        'procedures' => [],
                        'functions' => [],
                        'triggers' => [],
                        'permissions' => null,
                        'message' => 'Page projets - structure DB non nécessaire'
                    ]);
                }
                
                // Pour les autres pages, retourner une erreur mais avec plus d'informations
                return response()->json([
                    'error' => 'Aucune base de données sélectionnée',
                    'tables' => [],
                    'views' => [],
                    'procedures' => [],
                    'functions' => [],
                    'triggers' => [],
                    'permissions' => null,
                    'suggestion' => 'Veuillez sélectionner un projet depuis la page /projects'
                ], 400);
            }
            
            // Récupérer les différents objets de la base de données
            $tables = TableDescription::where('dbid', $dbId)
                ->select('id', 'tablename as name', 'description')
                ->get();
            Log::info('Tables trouvées: ' . $tables->count());

            $tables = $tables->map(function ($table) use ($permissions) {
                $columns = TableStructure::where('id_table', $table->id)
                    ->select('column as name', 'key', 'description')
                    ->get();
                    
                $table->columns = $columns;
                    
                // Créer un texte de recherche qui combine toutes les colonnes
                $columnsText = $columns->pluck('name')->join(' ');
                $table->searchable_columns = $columnsText;
                    
                // Indiquer si la table a une clé primaire ou étrangère
                $table->has_primary_key = $columns->contains(function ($column) {
                    return $column->key === 'PK';
                });
                    
                $table->has_foreign_key = $columns->contains(function ($column) {
                    return $column->key === 'FK';
                });

                // Ajouter des indicateurs de permissions pour cette table
                $table->can_edit = $permissions['can_write'] ?? false;
                $table->can_view_details = $permissions['can_read'] ?? false;
                    
                return $table;
            });
                
            $views = ViewDescription::where('dbid', $dbId)
                ->select('id', 'viewname as name', 'description')
                ->get()
                ->map(function ($view) use ($permissions) {
                    $view->can_edit = $permissions['can_write'] ?? false;
                    $view->can_view_details = $permissions['can_read'] ?? false;
                    return $view;
                });
            Log::info('Vues trouvées: ' . $views->count());
                
            $procedures = PsDescription::where('dbid', $dbId)
                ->select('id', 'psname as name', 'description')
                ->get()
                ->map(function ($procedure) use ($permissions) {
                    $procedure->can_edit = $permissions['can_write'] ?? false;
                    $procedure->can_view_details = $permissions['can_read'] ?? false;
                    return $procedure;
                });
            Log::info('Procédures trouvées: ' . $procedures->count());
                
            $functions = FunctionDescription::where('dbid', $dbId)
                ->select('id', 'functionname as name', 'description')
                ->get()
                ->map(function ($function) use ($permissions) {
                    $function->can_edit = $permissions['can_write'] ?? false;
                    $function->can_view_details = $permissions['can_read'] ?? false;
                    return $function;
                });
            Log::info('Fonctions trouvées: ' . $functions->count());
                
            $triggers = TriggerDescription::where('dbid', $dbId)
                ->select('id', 'triggername as name', 'description')
                ->get()
                ->map(function ($trigger) use ($permissions) {
                    $trigger->can_edit = $permissions['can_write'] ?? false;
                    $trigger->can_view_details = $permissions['can_read'] ?? false;
                    return $trigger;
                });
            Log::info('Triggers trouvés: ' . $triggers->count());

            Log::info('DatabaseStructure - Données récupérées avec succès', [
                'user_id' => auth()->id(),
                'tables_count' => $tables->count(),
                'permissions_level' => $permissions['level'] ?? 'none'
            ]);
            
            return response()->json([
                'tables' => $tables,
                'views' => $views,
                'procedures' => $procedures,
                'functions' => $functions,
                'triggers' => $triggers,
                'permissions' => $permissions,
                'user_access_level' => $permissions['level'] ?? 'none'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur dans DatabaseStructureController::index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'dbId' => session('current_db_id'),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'error' => 'Erreur lors de la récupération de la structure de la base de données',
                'tables' => [],
                'views' => [],
                'procedures' => [],
                'functions' => [],
                'triggers' => [],
                'permissions' => null,
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour la description d'une table
     * Nécessite des permissions d'écriture
     */
    public function updateTableDescription(Request $request, $tableId)
    {
        try {
            // Vérifier les permissions d'écriture
            if ($error = $this->requirePermission($request, 'write', 'You need write permissions to update table descriptions.')) {
                return $error;
            }

            $validated = $request->validate([
                'description' => 'nullable|string|max:1000'
            ]);

            $dbId = session('current_db_id');
            if (!$dbId) {
                return response()->json(['error' => 'No database selected'], 400);
            }

            $table = TableDescription::where('id', $tableId)
                ->where('dbid', $dbId)
                ->first();

            if (!$table) {
                return response()->json(['error' => 'Table not found'], 404);
            }

            $table->update([
                'description' => $validated['description']
            ]);

            Log::info('DatabaseStructure - Description de table mise à jour', [
                'user_id' => auth()->id(),
                'table_id' => $tableId,
                'table_name' => $table->tablename
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Table description updated successfully',
                'table' => $table
            ]);

        } catch (\Exception $e) {
            Log::error('DatabaseStructure - Erreur mise à jour description table', [
                'user_id' => auth()->id(),
                'table_id' => $tableId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error updating table description: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour la description d'une colonne
     * Nécessite des permissions d'écriture
     */
    public function updateColumnDescription(Request $request, $columnId)
    {
        try {
            // Vérifier les permissions d'écriture
            if ($error = $this->requirePermission($request, 'write', 'You need write permissions to update column descriptions.')) {
                return $error;
            }

            $validated = $request->validate([
                'description' => 'nullable|string|max:500'
            ]);

            $dbId = session('current_db_id');
            if (!$dbId) {
                return response()->json(['error' => 'No database selected'], 400);
            }

            // Vérifier que la colonne appartient à une table de la base de données actuelle
            $column = TableStructure::whereHas('table', function ($query) use ($dbId) {
                $query->where('dbid', $dbId);
            })->where('id', $columnId)->first();

            if (!$column) {
                return response()->json(['error' => 'Column not found'], 404);
            }

            $column->update([
                'description' => $validated['description']
            ]);

            Log::info('DatabaseStructure - Description de colonne mise à jour', [
                'user_id' => auth()->id(),
                'column_id' => $columnId,
                'column_name' => $column->column
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Column description updated successfully',
                'column' => $column
            ]);

        } catch (\Exception $e) {
            Log::error('DatabaseStructure - Erreur mise à jour description colonne', [
                'user_id' => auth()->id(),
                'column_id' => $columnId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error updating column description: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les détails d'une table avec ses colonnes
     * Nécessite au minimum des permissions de lecture
     */
    public function getTableDetails(Request $request, $tableId)
    {
        try {
            // Vérifier les permissions de lecture
            if ($error = $this->requirePermission($request, 'read', 'You need read access to view table details.')) {
                return $error;
            }

            $permissions = $request->get('user_project_permission');
            $dbId = session('current_db_id');
            
            if (!$dbId) {
                return response()->json(['error' => 'No database selected'], 400);
            }

            $table = TableDescription::where('id', $tableId)
                ->where('dbid', $dbId)
                ->first();

            if (!$table) {
                return response()->json(['error' => 'Table not found'], 404);
            }

            $columns = TableStructure::where('id_table', $tableId)
                ->select('id', 'column as name', 'type', 'key', 'nullable', 'default', 'description')
                ->get()
                ->map(function ($column) use ($permissions) {
                    $column->can_edit = $permissions['can_write'] ?? false;
                    return $column;
                });

            return response()->json([
                'table' => [
                    'id' => $table->id,
                    'name' => $table->tablename,
                    'description' => $table->description,
                    'can_edit' => $permissions['can_write'] ?? false
                ],
                'columns' => $columns,
                'permissions' => $permissions
            ]);

        } catch (\Exception $e) {
            Log::error('DatabaseStructure - Erreur récupération détails table', [
                'user_id' => auth()->id(),
                'table_id' => $tableId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error retrieving table details: ' . $e->getMessage()
            ], 500);
        }
    }
}