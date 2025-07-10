<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasProjectPermissions;
use Illuminate\Http\Request;
use App\Models\PsDescription;
use App\Models\PsInformation;
use App\Models\PsParameter;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProcedureController extends Controller
{
    use HasProjectPermissions;

    /**
     * Récupère les détails d'une procédure stockée
     */
    public function apiDetails(Request $request, $procedureName)
{
    try {

        if ($error = $this->requirePermission($request, 'read')) {
            return $error;
        }

        // Obtenir l'ID de la base de données actuelle depuis la session
        $dbId = session('current_db_id');
        Log::info('API - Récupération des détails pour procedureName: ' . $procedureName . ', dbId: ' . $dbId);
        
        if (!$dbId) {
            return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
        }

        // Récupérer la description de la procédure
        $procedureDesc = PsDescription::where('dbid', $dbId)
            ->where('psname', $procedureName)
            ->first();

        if (!$procedureDesc) {
            return response()->json(['error' => 'Procédure stockée non trouvée'], 404);
        }

        // Récupérer les informations de la procédure
        $procedureInfo = PsInformation::where('id_ps', $procedureDesc->id)->first();
        
        // Récupérer les paramètres de la procédure
        $parameters = PsParameter::where('id_ps', $procedureDesc->id)
            ->get()
            ->map(function ($param) {
                return [
                    'parameter_name' => $param->name,
                    'data_type' => $param->type,
                    'is_output' => $param->output === 'OUTPUT',
                    'default_value' => $param->default_value,
                    'description' => $param->description
                ];
            });

        // Construire la réponse
        return response()->json([
            'name' => $procedureDesc->psname,
            'description' => $procedureDesc->description,
            'schema' => $procedureInfo ? $procedureInfo->schema : null,
            'create_date' => $procedureInfo ? $procedureInfo->creation_date : null,
            'modify_date' => $procedureInfo ? $procedureInfo->last_change_date : null,
            'parameters' => $parameters,
            'definition' => $procedureInfo ? $procedureInfo->definition : null
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur dans ProcedureController::apiDetails', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'error' => 'Erreur lors de la récupération des détails de la procédure stockée: ' . $e->getMessage()
        ], 500);
    }
}

// méthode pour le rendu Inertia
    public function details(Request $request, $procedureName)
    {
        try {

            if ($error = $this->requirePermission($request, 'read')) {
            return $error;
            }

            $permissions = $request->get('user_project_permission');

            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            Log::info('Récupération des détails pour procedureName: ' . $procedureName . ', dbId: ' . $dbId);
            
            if (!$dbId) {
                return Inertia::render('ProcedureDetails', [
                    'procedureName' => $procedureName,
                    'procedureDetails' => [
                        'name' => $procedureName,
                        'description' => '',
                        'schema' => null,
                        'create_date' => null,
                        'modify_date' => null,
                        'parameters' => [],
                        'definition' => null
                    ],
                    'error' => 'Aucune base de données sélectionnée'
                ]);
            }

            // Récupérer la description de la procédure
            $procedureDesc = PsDescription::where('dbid', $dbId)
                ->where('psname', $procedureName)
                ->first();

            if (!$procedureDesc) {
                return Inertia::render('ProcedureDetails', [
                    'procedureName' => $procedureName,
                    'procedureDetails' => [
                        'name' => $procedureName,
                        'description' => '',
                        'schema' => null,
                        'create_date' => null,
                        'modify_date' => null,
                        'parameters' => [],
                        'definition' => null
                    ],
                    'error' => 'Procédure stockée non trouvée'
                ]);
            }

            // Récupérer les informations de la procédure
            $procedureInfo = PsInformation::where('id_ps', $procedureDesc->id)->first();
            
            // Récupérer les paramètres de la procédure
            $parameters = PsParameter::where('id_ps', $procedureDesc->id)
                ->get()
                ->map(function ($param) {
                    return [
                        'parameter_name' => $param->name,
                        'data_type' => $param->type ?? null,
                        'is_output' => $param->output === 'OUTPUT',
                        'description' => $param->description ?? null,
                        'default_value' => $param->default_value ?? null,
                    ];
                });

            return Inertia::render('ProcedureDetails', [
                'procedureName' => $procedureName,
                'procedureDetails' => [
                    'name' => $procedureDesc->psname,
                    'description' => $procedureDesc->description,
                    'schema' => $procedureInfo ? $procedureInfo->schema : null,
                    'create_date' => $procedureInfo ? $procedureInfo->creation_date : null,
                    'modify_date' => $procedureInfo ? $procedureInfo->last_change_date : null,
                    'parameters' => $parameters,
                    'definition' => $procedureInfo ? $procedureInfo->definition : null
                ],
                'permissions' => $permissions, // Ajoutez les permissions
                'error' => null // Ajoutez cette ligne pour les cas de succès
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans ProcedureController::details', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Inertia::render('ProcedureDetails', [
            'procedureName' => $procedureName,
            'procedureDetails' => [
                'name' => $procedureName,
                'description' => '',
                'schema' => null,
                'create_date' => null,
                'modify_date' => null,
                'parameters' => [],
                'definition' => null
            ],
            'permissions' => $request->get('user_project_permission', []),
            'error' => 'Erreur lors de la récupération des détails de la procédure stockée: ' . $e->getMessage()
        ]);
        }
    }

    /**
     * Sauvegarde la description d'une procédure stockée
     */
    public function saveDescription(Request $request, $procedureName)
    {
        try {

            if ($error = $this->requirePermission($request, 'write', 'You need write permissions to update procedures.')) {
            return $error;
            }

            // Valider les données
            $validated = $request->validate([
                'description' => 'nullable|string'
            ]);

            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            if (!$dbId) {
                return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
            }

            // Récupérer la description de la procédure
            $procedureDesc = PsDescription::where('dbid', $dbId)
                ->where('psname', $procedureName)
                ->first();

            if (!$procedureDesc) {
                return response()->json(['error' => 'Procédure stockée non trouvée'], 404);
            }

            // Mettre à jour la description
            $procedureDesc->description = $validated['description'];
            $procedureDesc->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la sauvegarde de la description: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sauvegarde la description d'un paramètre de procédure
     */
    public function saveParameterDescription(Request $request, $parameterId)
    {
        try {

            if ($error = $this->requirePermission($request, 'write', 'You need write permissions to update procedures.')) {
            return $error;
            }

            // Valider les données
            $validated = $request->validate([
                'description' => 'nullable|string'
            ]);

            // Récupérer le paramètre
            $parameter = PsParameter::find($parameterId);

            if (!$parameter) {
                return response()->json(['error' => 'Paramètre non trouvé'], 404);
            }

            // Mettre à jour la description
            $parameter->description = $validated['description'];
            $parameter->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la sauvegarde de la description du paramètre: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateColumnPossibleValues(Request $request, $procedureName, $columnName)
    {
        try {

            if ($error = $this->requirePermission($request, 'write', 'You need write permissions to update possible values.')) {
            return $error;
            }

            // Valider les données
            $validated = $request->validate([
                'possible_values' => 'nullable|string'
            ]);

            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            if (!$dbId) {
                return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
            }

            // Récupérer la description de la table
            $psDesc = PsDescription::where('dbid', $dbId)
                ->where('psname', $procedureName)
                ->first();

            if (!$psDesc) {
                return response()->json(['error' => 'Table non trouvée'], 404);
            }

            // Mettre à jour les valeurs possibles de la colonne
            $column = PsParameter::where('id_ps', $psDesc->id)
                ->where('name', $columnName)
                ->first();

            if (!$column) {
                return response()->json(['error' => 'Colonne non trouvée'], 404);
            }

            // Vérifier si les valeurs possibles ont changé
            if ($column->rangevalues !== $validated['possible_values']) {
                $oldRangeValues = $column->rangevalues;
                $column->rangevalues = $validated['possible_values'];
                $column->save();
                
                // Log pour déboguer le résultat de la sauvegarde
                Log::info('Résultat de la sauvegarde', [
                    'column' => $columnName,
                    'rangevalues' => $column->rangevalues,
                    'saveResult' => $column 
                ]);
                
                //Log de l'audit pour les valeurs possibles
                $this->logAudit(
                    $dbId, 
                    $psDesc->id, 
                    $columnName . '_rangevalues', 
                    'update', 
                    $oldRangeValues, 
                    $validated['possible_values']
                );
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour des valeurs possibles', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Erreur lors de la mise à jour des valeurs possibles: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sauvegarde toutes les informations d'une procédure stockée (uniquement la description)
     */
    public function saveAll(Request $request, $procedureName)
    {
        try {
            // Valider les données
            $validated = $request->validate([
                'description' => 'nullable|string'
            ]);

            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            if (!$dbId) {
                return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
            }

            // Récupérer la description de la procédure
            $procedureDesc = PsDescription::where('dbid', $dbId)
                ->where('psname', $procedureName)
                ->first();

            if (!$procedureDesc) {
                return response()->json(['error' => 'Procédure stockée non trouvée'], 404);
            }

            // Mettre à jour la description
            $procedureDesc->description = $validated['description'];
            $procedureDesc->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la sauvegarde des informations: ' . $e->getMessage()
            ], 500);
        }
    }
}
