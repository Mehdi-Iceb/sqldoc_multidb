<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use Inertia\Inertia;


class SpecificSearchController extends Controller
{
    
    public function index(Request $request)
    {
        // Récupérer le projet actuellement connecté
        $currentProjectId = $request->user()->current_project_id; // adapte si besoin
        $project = Project::with('dbDescriptions.tableDescriptions.tableStructures')->findOrFail($currentProjectId);

        // On récupère toutes les structures via les db_descriptions
        $tableStructuresQuery = \App\Models\TableStructure::query()
            ->whereHas('tableDescription.dbDescription', function ($q) use ($currentProjectId) {
                $q->where('project_id', $currentProjectId);
            });

        // Appliquer les filtres de recherche
        if ($request->filled('column_name')) {
            $tableStructuresQuery->where('column', 'like', '%' . $request->column_name . '%');
        }

        if ($request->filled('type')) {
            $tableStructuresQuery->where('type', $request->data_type);
        }

        $tableStructures = $tableStructuresQuery->paginate(20);

        return Inertia::render('TableStructures/Index', [
            'tableStructures' => $tableStructures,
            'filters' => $request->only(['column_name', 'data_type']),
        ]);
    }
}
