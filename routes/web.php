<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseStructureController;
use App\Http\Controllers\FunctionController;
use App\Http\Controllers\ProcedureController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReleaseApiController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\TriggerController;
use App\Http\Controllers\ViewController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    /*return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);*/
    return redirect('/login');
});


Route::get('/projects', function () {
    return Inertia::render('projects/index');
})->middleware(['auth', 'verified'])->name('projects');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    Route::post('/admin/users', [AdminController::class, 'createUser']);
    Route::post('/admin/users/{user}/role', [AdminController::class, 'updateUserRole']);
    Route::put('/admin/roles/{role}/permissions', [AdminController::class, 'updateRolePermissions']);

    // Nouvelles routes pour la gestion des accès aux projets
    Route::get('/admin/projects/available', [AdminController::class, 'getAvailableProjects'])->name('admin.projects.available');
    Route::get('/admin/users/{user}/project-accesses', [AdminController::class, 'getUserProjectAccesses'])->name('admin.users.project-accesses');
    Route::post('/admin/project-access/grant', [AdminController::class, 'grantProjectAccess'])->name('admin.project-access.grant');
    Route::post('/admin/project-access/revoke', [AdminController::class, 'revokeProjectAccess'])->name('admin.project-access.revoke');
    
    // Routes existantes pour les projets supprimés 
    Route::get('/projects/deleted', [AdminController::class, 'getDeletedProjects'])->name('admin.projects.deleted');
    Route::post('/projects/{id}/restore', [AdminController::class, 'restoreProject'])->name('admin.projects.restore');
    Route::delete('/projects/{id}/force-delete', [AdminController::class, 'forceDeleteProject'])->name('admin.projects.force-delete');
    Route::get('/projects/stats', [AdminController::class, 'getProjectStats'])->name('admin.projects.stats');

    Route::get('/projects/deleted', [AdminController::class, 'getDeletedProjects']);
    Route::post('/projects/{id}/restore', [AdminController::class, 'restoreProject']);
    Route::delete('/projects/{id}/force', [AdminController::class, 'forceDeleteProject']);
    Route::get('/projects/stats', [AdminController::class, 'getProjectStats']);

});


Route::middleware('auth')->group(function () {
    Route::get('/about', fn () => Inertia::render('About'))->name('about');

    Route::get('users', [UserController::class, 'index'])->name('users.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/connect', [ProjectController::class, 'connect'])->name('projects.connect');
    Route::post('/projects/{project}/connect', [ProjectController::class, 'handleConnect'])->name('projects.handle-connect');
    Route::post('/disconnect', [ProjectController::class, 'disconnect'])->name('disconnect');
    Route::get('/projects/{project}/open', [ProjectController::class, 'open'])->name('projects.open');

    Route::get('/table/{tableName}/details', [TableController::class, 'details'])
        ->name('table.details');
    Route::get('/api/table/{tableName}/details', [TableController::class, 'apiDetails'])
        ->name('api.table.details');
    Route::post('/table/{tableName}/save-structure', [TableController::class, 'saveStructure'])
        ->name('table.saveStructure');
    Route::post('/table/{tableName}/column/{columnName}/description', [TableController::class, 'updateColumnDescription'])
        ->name('table.column.updateDescription');
    Route::post('/table/{tableName}/column/{columnName}/possible-values', [TableController::class, 'updateColumnPossibleValues'])
        ->name('table.column.updatePossibleValues');
    Route::get('/api/table/{tableName}/details', [TableController::class, 'apiDetails'])
        ->name('api.table.details');
    Route::get('/table/{tableName}/column/{columnName}/audit-logs', [TableController::class, 'getAuditLogs'])
        ->name('table.audit.logs');
    Route::post('/table/{tableName}/column/{columnName}/properties', [TableController::class, 'updateColumnProperties'])
        ->name('table.column.properties');
    Route::post('/table/{tableName}/column/add', [TableController::class, 'addColumn'])
        ->name('table.column.add');
    Route::post('/table/{tableName}/relation/add', [TableController::class, 'addRelation'])
        ->name('table.relation.add');
    Route::get('/api/table-id/{tableName}', [TableController::class, 'getTableId']);
    Route::post('/table/{tableName}/column/{columnName}/release', [TableController::class, 'updateColumnRelease']);
    Route::post('/api/table/{tableName}/column/{columnName}/release', [TableController::class, 'updateColumnRelease']);
    Route::get('/api/table-id/{tableName}', [TableController::class, 'getTableId']);

    Route::get('/view/{viewName}/details', [ViewController::class, 'details'])
        ->name('view.details');
    Route::get('/api/view/{viewName}/details', [ViewController::class, 'apiDetails'])
    ->name('api.view.details');
    Route::post('/view/{viewName}/description', [ViewController::class, 'saveDescription'])
        ->name('view.saveDescription');
    Route::post('/view/{viewName}/column/{columnName}/description', [ViewController::class, 'saveColumnDescription'])
        ->name('view.column.saveDescription');
    Route::post('/view/{viewName}/save-all', [ViewController::class, 'saveAll'])
        ->name('view.saveAll');

    Route::get('/function/{functionName}/details', [FunctionController::class, 'details'])
        ->name('function.details');
    Route::get('/api/function/{functionName}/details', [FunctionController::class, 'apiDetails'])
        ->name('api.function.details');
    Route::post('/function/{functionName}/description', [FunctionController::class, 'saveDescription'])
        ->name('function.saveDescription');
    Route::post('/function-parameter/{parameterId}/update-description', [FunctionController::class, 'saveParameterDescription'])
        ->name('function.parameter.updateDescription');

    Route::get('/procedure/{procedureName}/details', [ProcedureController::class, 'details'])
        ->name('procedure.details');
    Route::get('/api/procedure/{procedureName}/details', [ProcedureController::class, 'apiDetails'])
        ->name('api.procedure.details');
    Route::post('/procedure/{procedureName}/description', [ProcedureController::class, 'saveDescription'])
        ->name('procedure.saveDescription');
    Route::post('/procedure-parameter/{parameterId}/update-description', [ProcedureController::class, 'saveParameterDescription'])
        ->name('procedure.parameter.updateDescription');
    Route::post('/procedure/{procedureName}/save-all', [ProcedureController::class, 'saveAll'])
        ->name('procedure.saveAll');

    Route::get('/trigger/{triggerName}/details', [TriggerController::class, 'getDetails'])
        ->name('trigger.details');
    Route::get('/api/trigger/{triggerName}/details', [TriggerController::class, 'apiDetails'])
        ->name('api.trigger.details');
    Route::post('/trigger/{triggerName}/description', [TriggerController::class, 'saveDescription'])
        ->name('trigger.description');
    Route::post('/trigger/{triggerName}/save-all', [TriggerController::class, 'saveAll'])
        ->name('trigger.saveall');

    Route::get('/database-structure', [DatabaseStructureController::class, 'index']);

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/dashboard-data', [DashboardController::class, 'index']);

    // Routes pour le soft delete des projets
    Route::delete('/projects/{id}/soft', [ProjectController::class, 'softDelete'])->name('projects.soft-delete');
    Route::post('/projects/{id}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
    Route::delete('/projects/{id}/force', [ProjectController::class, 'forceDelete'])->name('projects.force-delete');
    Route::get('/projects/deleted', [ProjectController::class, 'deleted'])->name('projects.deleted');
    Route::put('/projects/{id}', [ProjectController::class, 'update'])->name('projects.update');

    // Route::delete('/api/projects/{id}/soft', [ProjectController::class, 'softDelete'])->name('projects.soft-delete');
    // Route::post('/api/projects/{id}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
    // Route::delete('/api/projects/{id}/force', [ProjectController::class, 'forceDelete'])->name('projects.force-delete');
    // Route::get('/api/projects/deleted', [ProjectController::class, 'deleted'])->name('projects.deleted');
    // Route::put('/api/projects/{id}', [ProjectController::class, 'update'])->name('projects.update');

});

// Routes pour le contrôleur Release
    Route::prefix('releases')->name('releases.')->group(function () {
        Route::get('/', [ReleaseController::class, 'index'])->name('releases.index');
        Route::get('/create', [ReleaseController::class, 'create'])->name('create');
        Route::post('/', [ReleaseController::class, 'store'])->name('store');
        Route::get('/{id}', [ReleaseController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ReleaseController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ReleaseController::class, 'update'])->name('update');
        Route::delete('/{id}', [ReleaseController::class, 'destroy'])->name('destroy');
    });

// Routes API pour les versions et associations de colonnes
Route::prefix('api')->group(function () {
    // Routes pour la gestion des versions
    Route::get('/releases', [ReleaseApiController::class, 'index'])->name('api.releases.index');
    Route::get('/releases/all', [ReleaseApiController::class, 'getAllVersions'])->name('api.releases.all');
    Route::post('/releases', [ReleaseApiController::class, 'store'])->name('api.releases.store');
    Route::put('/releases/{id}', [ReleaseApiController::class, 'update'])->name('api.releases.update');
    Route::delete('/releases/{id}', [ReleaseApiController::class, 'destroy'])->name('api.releases.destroy');
    
    // Routes pour l'association des versions aux colonnes
    Route::post('/releases/assign-to-column', [ReleaseApiController::class, 'assignReleaseToColumn'])->name('api.releases.assign');
    Route::post('/releases/remove-from-column', [ReleaseApiController::class, 'removeReleaseFromColumn'])->name('api.releases.remove');
});

// Route pour la page principale des versions
Route::get('/releases', function () {
    return Inertia::render('Releases');
})->name('releases.index');

require __DIR__.'/auth.php';
