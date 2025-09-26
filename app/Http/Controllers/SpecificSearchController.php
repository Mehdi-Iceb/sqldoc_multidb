<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\TableStructure;
use App\Models\ViewColumn;
use Inertia\Inertia;


class SpecificSearchController extends Controller
{

    
    public function specificSearch(Request $request)
    {
        $currentProjectId = $request->user()->current_project_id;

        $tableResults = collect(); // Vide par défaut
        $viewResults = collect();  // Vide par défaut

        if ($request->boolean('in_tables')) {
            $tableQuery = TableStructure::query()
                ->whereHas('tableDescription.dbDescription', function ($q) use ($currentProjectId) {
                    $q->where('project_id', $currentProjectId);
                });

            if ($request->filled('column_name')) {
                $tableQuery->where('column', 'like', '%' . $request->column_name . '%');
            }

            $tableResults = $tableQuery->get();
        }

        if ($request->boolean('in_views')) {
            $viewQuery = ViewColumn::query()
                ->whereHas('viewDescription.dbDescription', function ($q) use ($currentProjectId) {
                    $q->where('project_id', $currentProjectId);
                });

            if ($request->filled('column_name')) {
                $viewQuery->where('column', 'like', '%' . $request->column_name . '%');
            }

            $viewResults = $viewQuery->get();
        }

        return Inertia::render('SpecificSearch', [
            'tableResults' => $tableResults,
            'viewResults' => $viewResults,
            'filters' => $request->only(['column_name', 'in_tables', 'in_views']),
        ]);
    }


}
