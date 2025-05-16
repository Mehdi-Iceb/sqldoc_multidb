<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Models\TableStructure;
use App\Models\TableDescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class ReleaseApiController extends Controller
{
    /**
     * Renvoie la liste des versions pour l'interface Vue.js
     */
    public function index()
    {
        try {
            // Récupérer toutes les versions avec leur table structure associée
            $releases = Release::with(['tableStructure' => function ($query) {
                $query->with('tableDescription');
            }])
            ->orderBy('version_number', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($release) {
                // Récupérer le nom de la table si disponible
                $tableName = null;
                $columnName = null;
                $dataType = null;
                
                if ($release->tableStructure) {
                    $columnName = $release->tableStructure->column;
                    $dataType = $release->tableStructure->type;
                    
                    if ($release->tableStructure->tableDescription) {
                        $tableName = $release->tableStructure->tableDescription->tablename;
                    }
                }
                
                return [
                    'id' => $release->id,
                    'version_number' => $release->version_number,
                    'id_table_structure' => $release->id_table_structure,
                    'table_name' => $tableName,
                    'column_name' => $columnName,
                    'data_type' => $dataType,
                    'created_at' => $release->created_at->format('d/m/Y H:i'),
                    'updated_at' => $release->updated_at->format('d/m/Y H:i')
                ];
            });

            // Obtenir les versions uniques pour le filtre
            $uniqueVersions = Release::distinct()
                ->orderBy('version_number', 'desc')
                ->pluck('version_number');

            return response()->json([
                'releases' => $releases,
                'uniqueVersions' => $uniqueVersions
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
     * Renvoie la liste des structures de table disponibles
     */
    public function getTableStructures()
    {
        try {
            // Récupérer la liste des colonnes disponibles pour ajouter une version
            $tableStructures = TableStructure::with(['tableDescription' => function ($query) {
                    $query->select('id', 'tablename');
                }])
                ->get()
                ->map(function ($structure) {
                    return [
                        'id' => $structure->id,
                        'column' => $structure->column,
                        'table_name' => $structure->tableDescription ? $structure->tableDescription->tablename : 'N/A',
                        'type' => $structure->type
                    ];
                });

            return response()->json($tableStructures);
        } catch (\Exception $e) {
            Log::error('Erreur dans ReleaseApiController::getTableStructures', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Erreur lors du chargement des structures de table: ' . $e->getMessage()], 500);
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
                'id_table_structure' => 'required|exists:table_structure,id',
                'version_number' => 'required|string|max:20'
            ]);

            // Vérifier si cette colonne a déjà une version identique
            $existingRelease = Release::where('id_table_structure', $validated['id_table_structure'])
                ->where('version_number', $validated['version_number'])
                ->first();

            if ($existingRelease) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cette colonne possède déjà cette version.'
                ], 400);
            }

            // Créer la nouvelle version
            $release = Release::create($validated);

            // Mettre à jour le champ release de la table_structure si nécessaire
            if (Schema::hasColumn('table_structure', 'release')) {
                $tableStructure = TableStructure::find($validated['id_table_structure']);
                if ($tableStructure) {
                    $tableStructure->release = $validated['version_number'];
                    $tableStructure->save();
                }
            }

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
                'id_table_structure' => 'required|exists:table_structure,id',
                'version_number' => 'required|string|max:20'
            ]);

            // Récupérer la version existante
            $release = Release::findOrFail($id);

            // Vérifier si la combinaison existe déjà (hors cette version)
            $existingRelease = Release::where('id_table_structure', $validated['id_table_structure'])
                ->where('version_number', $validated['version_number'])
                ->where('id', '!=', $id)
                ->first();

            if ($existingRelease) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cette colonne possède déjà cette version.'
                ], 400);
            }

            // Mettre à jour l'ancien table_structure si release a changé
            if ($release->id_table_structure != $validated['id_table_structure'] && Schema::hasColumn('table_structure', 'release')) {
                $oldTableStructure = TableStructure::find($release->id_table_structure);
                if ($oldTableStructure && $oldTableStructure->release == $release->version_number) {
                    $oldTableStructure->release = null;
                    $oldTableStructure->save();
                }
            }

            // Mettre à jour la version
            $release->update($validated);

            // Mettre à jour le nouveau table_structure si nécessaire
            if (Schema::hasColumn('table_structure', 'release')) {
                $tableStructure = TableStructure::find($validated['id_table_structure']);
                if ($tableStructure) {
                    $tableStructure->release = $validated['version_number'];
                    $tableStructure->save();
                }
            }

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

            // Mettre à jour le table_structure si nécessaire
            if (Schema::hasColumn('table_structure', 'release')) {
                $tableStructure = TableStructure::find($release->id_table_structure);
                if ($tableStructure && $tableStructure->release == $release->version_number) {
                    $tableStructure->release = null;
                    $tableStructure->save();
                }
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
     * Récupère les statistiques pour une version spécifique
     */
    public function getVersionStats($versionNumber)
    {
        try {
            // Compter le nombre de colonnes par type de données pour cette version
            $dataTypesStats = Release::where('version_number', $versionNumber)
                ->join('table_structure', 'releases.id_table_structure', '=', 'table_structure.id')
                ->select('table_structure.type', DB::raw('count(*) as count'))
                ->groupBy('table_structure.type')
                ->get();

            // Compter le nombre de colonnes par table pour cette version
            $tablesStats = Release::where('version_number', $versionNumber)
                ->join('table_structure', 'releases.id_table_structure', '=', 'table_structure.id')
                ->join('table_description', 'table_structure.id_table', '=', 'table_description.id')
                ->select('table_description.tablename', DB::raw('count(*) as count'))
                ->groupBy('table_description.tablename')
                ->get();

            return response()->json([
                'dataTypes' => $dataTypesStats,
                'tables' => $tablesStats
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur dans ReleaseApiController::getVersionStats', [
                'version' => $versionNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ], 500);
        }
    }
}