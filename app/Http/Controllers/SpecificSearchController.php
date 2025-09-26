<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\TableStructure;
use App\Models\ViewColumn;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;


class SpecificSearchController extends Controller
{

    
    public function specificSearch(Request $request)
    {
        $currentProjectId = session('current_db_id');

        $tableResults = collect(); // Vide par défaut
        $viewResults = collect();  // Vide par défaut

        if ($request->boolean('in_tables')) {
            $tableQuery = TableStructure::query()
                ->whereHas('TableDescription.dbDescription', function ($q) use ($currentProjectId) {
                    Log::info('Filtrage project_id: ' . $currentProjectId);
                    $q->where('project_id', $currentProjectId);
                });

            if ($request->filled('column')) {
                $tableQuery->where('column', 'like', '%' . $request->column . '%');
            }

            $tableResults = $tableQuery->get();
        }

        if ($request->boolean('in_views')) {
            $viewQuery = ViewColumn::query()
                ->whereHas('ViewDescription.dbDescription', function ($q) use ($currentProjectId) {
                    $q->where('project_id', $currentProjectId);
                });

            if ($request->filled('name')) {
                $viewQuery->where('name', 'like', '%' . $request->column . '%');
            }

            $viewResults = $viewQuery->get();
        }

        Log::info('Recherche colonne : ' . $request->column);
        Log::info('Résultats table : ' . $tableResults->count());
        Log::info('Résultats vue : ' . $viewResults->count());

        return Inertia::render('SpecificSearch', [
            'tableResults' => $tableResults,
            'viewResults' => $viewResults,
            'filters' => $request->only(['column_name', 'in_tables', 'in_views']),
        ]);
    }


}
