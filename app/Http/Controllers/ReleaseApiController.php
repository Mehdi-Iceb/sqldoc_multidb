<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ReleaseApiController extends Controller
{
    /**
     * Renvoie la liste des versions pour l'interface Vue.js
     */
    public function index()
    {
        try {
            // Récupérer toutes les versions avec leur projet associé
            $releases = Release::with('project')
                ->orderBy('version_number', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($release) {
                    return [
                        'id' => $release->id,
                        'version_number' => $release->version_number,
                        'project_id' => $release->project_id,
                        'project_name' => $release->project ? $release->project->name : 'N/A',
                        'description' => $release->description ?? '',
                        'created_at' => $release->created_at->format('d/m/Y H:i'),
                        'updated_at' => $release->updated_at->format('d/m/Y H:i')
                    ];
                });

            // Obtenir les versions uniques pour le filtre
            $uniqueVersions = Release::distinct()
                ->orderBy('version_number', 'desc')
                ->pluck('version_number');
                
            // Récupérer tous les projets
            $projects = Project::select('id', 'name')->orderBy('name')->get();

            return response()->json([
                'release' => $releases,
                'uniqueVersions' => $uniqueVersions,
                'projects' => $projects
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur dans ReleaseApiController::index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Erreur lors du chargement des versions: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Renvoie la liste de toutes les versions disponibles (pour les listes déroulantes)
     */
    public function getAllVersions()
    {
        try {
            $versions = Release::select('id', 'version_number', 'project_id')
                ->with('project:id,name')
                ->orderBy('version_number', 'desc')
                ->get()
                ->map(function ($release) {
                    return [
                        'id' => $release->id,
                        'version_number' => $release->version_number,
                        'project_name' => $release->project ? $release->project->name : null,
                        'display_name' => $release->project 
                            ? $release->version_number . ' (' . $release->project->name . ')'
                            : $release->version_number
                    ];
                });

            return response()->json($versions);
        } catch (\Exception $e) {
            Log::error('Erreur dans ReleaseApiController::getAllVersions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Erreur lors du chargement des versions: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Enregistre une nouvelle version
     */
    public function store(Request $request)
    {
        try {
            // Valider les données
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'version_number' => 'required|string|max:20',
                'description' => 'nullable|string'
            ]);

            // Vérifier si ce projet a déjà une version identique
            $existingRelease = Release::where('project_id', $validated['project_id'])
                ->where('version_number', $validated['version_number'])
                ->first();

            if ($existingRelease) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ce projet possède déjà cette version.'
                ], 400);
            }

            // Créer la nouvelle version
            $release = Release::create($validated);

            return response()->json([
                'success' => true,
                'release' => $release
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur dans ReleaseApiController::store', [
                'request' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la création de la version: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour une version existante
     */
    public function update(Request $request, $id)
    {
        try {
            // Valider les données
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'version_number' => 'required|string|max:20',
                'description' => 'nullable|string'
            ]);

            // Récupérer la version existante
            $release = Release::findOrFail($id);

            // Vérifier si la combinaison existe déjà (hors cette version)
            $existingRelease = Release::where('project_id', $validated['project_id'])
                ->where('version_number', $validated['version_number'])
                ->where('id', '!=', $id)
                ->first();

            if ($existingRelease) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ce projet possède déjà cette version.'
                ], 400);
            }

            // Mettre à jour la version
            $release->update($validated);

            return response()->json([
                'success' => true,
                'release' => $release
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur dans ReleaseApiController::update', [
                'id' => $id,
                'request' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la mise à jour de la version: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime une version
     */
    public function destroy($id)
    {
        try {
            // Récupérer la version
            $release = Release::findOrFail($id);

            // Vérifier si des tables/colonnes utilisent cette version
            $usageCount = DB::table('table_structure')
                ->where('release_id', $id)
                ->count();

            if ($usageCount > 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cette version est utilisée par ' . $usageCount . ' colonnes. Veuillez les mettre à jour avant de supprimer cette version.'
                ], 400);
            }

            // Supprimer la version
            $release->delete();

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur dans ReleaseApiController::destroy', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la suppression de la version: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Associe une version à une colonne spécifique
     */
    public function assignReleaseToColumn(Request $request)
    {
        try {

            Log::info('Début de assignReleaseToColumn', [
            'request_all' => $request->all()
            ]);

            // Valider les données
            $validated = $request->validate([
                'release_id' => 'required|exists:release,id',
                'table_id' => 'required|integer',
                'column_name' => 'required|string'
            ]);

            Log::info('Données validées', [
            'validated' => $validated
            ]);

            // Récupérer la structure de la table
            $column = DB::table('table_structure')
                ->where('id_table', $validated['table_id'])
                ->where('column', $validated['column_name'])
                ->first();

            if (!$column) {
            Log::error('Colonne non trouvée', [
                'table_id' => $validated['table_id'],
                'column_name' => $validated['column_name']
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Colonne non trouvée'
            ], 404);
            }

            Log::info('Colonne trouvée', [
                'column' => $column
            ]);

            // Mettre à jour la colonne avec l'ID de version
            $updateResult = DB::table('table_structure')
                ->where('id', $column->id)
                ->update(['release_id' => $validated['release_id']]);

            Log::info('Résultat de la mise à jour', [
                'updateResult' => $updateResult,
                'column_id' => $column->id,
                'release_id' => $validated['release_id']
            ]);

            // Vérifier après la mise à jour
            $updatedColumn = DB::table('table_structure')
                ->where('id', $column->id)
                ->first();

            Log::info('État de la colonne après mise à jour', [
                'release_id' => $updatedColumn->release_id
            ]);

            return response()->json([
                'success' => true,
                'update_result' => $updateResult,
                'column_id' => $column->id,
                'new_release_id' => $validated['release_id'],
                'updated_column' => $updatedColumn
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur dans ReleaseApiController::assignReleaseToColumn', [
                'request' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'association de la version à la colonne: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retire l'association d'une version à une colonne
     */
    public function removeReleaseFromColumn(Request $request)
    {
        try {
            // Valider les données
            $validated = $request->validate([
                'table_id' => 'required|integer',
                'column_name' => 'required|string'
            ]);

            // Récupérer la structure de la table
            $column = DB::table('table_structure')
                ->where('id_table', $validated['table_id'])
                ->where('column', $validated['column_name'])
                ->first();

            if (!$column) {
                return response()->json([
                    'success' => false,
                    'error' => 'Colonne non trouvée'
                ], 404);
            }

            // Retirer l'association
            DB::table('table_structure')
                ->where('id', $column->id)
                ->update(['release_id' => null]);

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur dans ReleaseApiController::removeReleaseFromColumn', [
                'request' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du retrait de l\'association: ' . $e->getMessage()
            ], 500);
        }
    }
}