<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProcedureController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReleaseApiController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\TriggerController;
use App\Http\Controllers\ViewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseStructureController;
use App\Http\Controllers\FunctionController;
use App\Http\Controllers\LandingTenantController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\SpecificSearchController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TenantController;
use App\Models\Domain;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Tenant;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::get('/', function () {
    return redirect('/landing');
});

Route::get('/landing', [LandingTenantController::class, 'create'])->name('landing');
Route::get('/registerTenant', [TenantController::class, 'register'])->name('register');
Route::post('/start', [TenantController::class, 'start'])->name('tenant.start');
//Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
Route::post('/check-email', function (Request $request) {
    //  Validation du format de l'email
    $validator = Validator::make($request->all(), [
        'email' => 'required|email:rfc|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'exists' => false,
            'invalid' => true,
            'message' => 'Format d’email invalide.',
        ]);
    }

    // 🔍 Vérifie si l'email existe déjà dans la table tenants
    $exists = Tenant::where('contact_email', $request->email)->exists();

    return response()->json([
        'exists' => $exists,
        'invalid' => false,
        'message' => $exists
            ? 'Cette adresse email est déjà utilisée.'
            : 'Adresse email disponible.',
    ]);
});
Route::post('/check-slug', function (Request $request) {
    $validator = Validator::make($request->all(), [
        'slug' => 'required|string|alpha_dash'
    ]);

    if ($validator->fails()) {
        return response()->json(['exists' => false, 'invalid' => true]);
    }

    $exists = Tenant::where('slug', $request->slug)->exists();
    return response()->json(['exists' => $exists]);
});

Route::get('/pricing', [SubscriptionController::class, 'index'])->name('pricing');


Route::get('/subscription', [SubscriptionController::class, 'index'])->name('index');
    Route::get('/show', [SubscriptionController::class, 'show'])->name('show');
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::post('/{subscription}/activate', [SubscriptionController::class, 'activate'])->name('activate');
    Route::put('/{subscription}/change-plan', [SubscriptionController::class, 'changePlan'])->name('changePlan');
    Route::put('/{subscription}/change-cycle', [SubscriptionController::class, 'changeBillingCycle'])->name('changeCycle');
    Route::post('/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('renew');
    Route::delete('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
    Route::post('/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('resume');

$host = request()->getHost();
if (!in_array($host, config('tenancy.central_domains', []))) {
    
    $centralConnection = config('tenancy.database.central_connection', 'sqlsrv');
    
    $domain = Domain::on($centralConnection)->where('domain', $host)->first();
    
    if ($domain && $domain->tenant) {
        $tenant = $domain->tenant;
        $tenantConnection = 'tenant_' . strtolower(str_replace(['-', ' '], '_', $tenant->slug));
        
        config([
            "database.connections.{$tenantConnection}" => [
                'driver' => 'sqlsrv',
                'host' => config('database.connections.sqlsrv.host'),
                'port' => config('database.connections.sqlsrv.port'),
                'database' => $tenant->getDatabaseName(),
                'username' => config('database.connections.sqlsrv.username'),
                'password' => config('database.connections.sqlsrv.password'),
                'charset' => config('database.connections.sqlsrv.charset'),
                'prefix' => '',
                'prefix_indexes' => true,
            ],
            'database.default' => $tenantConnection,

            'session.cookie' => env('SESSION_COOKIE', 'laravel_session') . '_' . $tenant->slug,
            'session.domain' => '.domain.test',
            'session.same_site' => 'lax',
        ]);
        
        tenancy()->initialize($tenant);
        
        Log::info('Tenant initialized correctly', [
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
            'database_name' => $tenant->getDatabaseName(),
            'connection' => $tenantConnection,
            'new_default' => config('database.default')
        ]);
    }

    Route::get('/csrf-debug', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId(),
        'session_name' => session()->getName(),
        'session_domain' => config('session.domain'),
        'session_cookie' => config('session.cookie'),
        'host' => request()->getHost(),
        'cookies' => request()->cookies->all(),
        'headers' => request()->headers->all(),
    ]);
});
}

// IMPORTANT: Appliquer tous les middlewares web à toutes les routes
Route::middleware(['web'])->group(function () {

    // Debug routes
    // Route::get('/debug-advanced', function () {
    //     return [
    //         'host' => request()->getHost(),
    //         'tenant_initialized' => tenant() ? 'YES' : 'NO',
    //         'tenant_id' => tenant('id'),
    //         'tenant_slug' => tenant('slug'),
    //         'database_default' => config('database.default'),
    //         'database_expected' => tenant() ? tenant()->getDatabaseName() : 'N/A',
    //         'connection_config' => config('database.connections.'.config('database.default')),
    //         'users_table_exists' => \Schema::hasTable('users'),
    //         'users_count' => \App\Models\User::count(),
    //     ];
    // });

    Route::get('/debug', function () {
        return [
            'tenant_id' => tenant('id'),
            'database' => config('database.default'),
            'authenticated' => Auth::check(),  // ← Ajoutez ceci
            'current_user' => Auth::user(),
            'session_driver' => config('session.driver'),
            'users_count' => \App\Models\User::count(),
            'session_domain' => config('session.domain'),
            'all_users' => \App\Models\User::all(),
        ];
    });

    Route::get('/test-admin', function () {
    $user = Auth::user();
    
    return [
        'user' => $user,
        'role' => $user->role,
        'is_admin_by_role_id' => $user->role_id === 1,
        'is_admin_by_role_name' => $user->role->name === 'Admin',
        'can_manage_users' => Gate::allows('manage-users'),  // Si vous utilisez Gates
    ];
    })->middleware('auth');

    // Routes accessibles uniquement aux invités (non authentifiés)
    Route::middleware('guest')->group(function () {
        Route::get('register', [RegisteredUserController::class, 'create'])
            ->name('register');

        // Route::get('login', [AuthenticatedSessionController::class, 'create'])
        //     ->name('login');

        // Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
            ->name('password.request');

        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
            ->name('password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
            ->name('password.reset');

        Route::post('reset-password', [NewPasswordController::class, 'store'])
            ->name('password.store');
    });




//Route::post('/start', [TenantController::class, 'store'])->name('tenant.public.store');


    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);



// Routes accessibles uniquement aux utilisateurs authentifiés
Route::middleware('auth')->group(function () {
    // Page d'accueil du tenant
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Email verification routes
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Basic application routes
    Route::get('/about', fn () => Inertia::render('About'))->name('about');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');

    // Project routes
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/connect', [ProjectController::class, 'connect'])->name('projects.connect');
    Route::post('/projects/{project}/connect', [ProjectController::class, 'handleConnect'])->name('projects.handle-connect');
    Route::post('/disconnect', [ProjectController::class, 'disconnect'])->name('disconnect');
    Route::get('/projects/{project}/open', [ProjectController::class, 'open'])->name('projects.open');
    Route::post('/projects/{project}/test-connection', [ProjectController::class, 'testConnection'])->name('projects.test-connection');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    
   // Routes qui ne nécessitent pas de projet sélectionné
    Route::get('/dashboard-data', [DashboardController::class, 'index']);
    Route::get('/database-structure', [DatabaseStructureController::class, 'index']);
    Route::post('/database-structure/refresh', [DatabaseStructureController::class, 'refresh'])->name('database.structure.refresh');
    Route::delete('/database-structure/cache', [DatabaseStructureController::class, 'clearCache'])->name('database.structure.clear-cache');
    Route::get('/database-structure/cache-status', [DatabaseStructureController::class, 'cacheStatus'])->name('database.structure.cache-status');

    // Routes pour le soft delete des projets
    Route::delete('/projects/{id}/soft', [ProjectController::class, 'softDelete'])->name('projects.soft-delete');
    Route::post('/projects/{id}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
    //Route::delete('/projects/{id}/force', [ProjectController::class, 'forceDelete'])->name('projects.force-delete');
    Route::get('/projects/deleted', [ProjectController::class, 'deleted'])->name('projects.deleted');
    Route::put('/projects/{id}', [ProjectController::class, 'update'])->name('projects.update');
});

// ROUTES ADMIN
Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    Route::post('/admin/users', [AdminController::class, 'createUser']);
    Route::post('/admin/users/{user}/role', [AdminController::class, 'updateUserRole']);
    Route::put('/admin/roles/{role}/permissions', [AdminController::class, 'updateRolePermissions']);

    //creation de role
    Route::post('/admin/createrole', [AdminController::class, 'createRole']);

    // Nouvelles routes pour la gestion des accès aux projets
    Route::get('/admin/projects/available', [AdminController::class, 'getAvailableProjects'])->name('admin.projects.available');
    Route::get('/admin/users/{user}/project-accesses', [AdminController::class, 'getUserProjectAccesses'])->name('admin.users.project-accesses');
    Route::post('/admin/project-access/grant', [AdminController::class, 'grantProjectAccess'])->name('admin.project-access.grant');
    Route::post('/admin/project-access/revoke', [AdminController::class, 'revokeProjectAccess'])->name('admin.project-access.revoke');
    
    // Routes existantes pour les projets supprimés 
    Route::get('/projects/deleted', [AdminController::class, 'getDeletedProjects'])->name('admin.projects.deleted');
    Route::post('/projects/{id}/restore', [AdminController::class, 'restoreProject'])->name('admin.projects.restore');
    Route::get('/projects/stats', [AdminController::class, 'getProjectStats'])->name('admin.projects.stats');
    Route::get('/admin/projects/all', [AdminController::class, 'getAllProjects']);
    Route::get('/projects/{id}/deletion-preview', [ProjectController::class, 'getProjectDeletionPreview']);
    Route::delete('/projects/{id}/force', [ProjectController::class, 'forceDeleteProject']);
});

// ROUTES AVEC PERMISSIONS DE LECTURE (projet requis)
Route::middleware(['auth', 'project.permissions:read'])->group(function () {
   

    Route::get('/table/{tableName}/details', [TableController::class, 'details'])->name('table.details');
    Route::get('/table/{tableName}/column/{columnName}/audit-logs', [TableController::class, 'getAuditLogs'])->name('table.audit.logs');

    Route::get('/view/{viewName}/details', [ViewController::class, 'details'])->name('view.details');
    Route::get('/view/{viewName}/column/{columnName}/audit-logs', [ViewController::class, 'getAuditLogs'])->name('view.audit.logs');

    Route::get('/function/{functionName}/details', [FunctionController::class, 'details'])->name('function.details');
    Route::get('/function/{functionName}/function/{parameterName}/audit-logs', [FunctionController::class, 'getAuditLogs'])->name('function.audit.logs');

    Route::get('/procedure/{procedureName}/details', [ProcedureController::class, 'details'])->name('procedure.details');
    Route::get('/procedure/{procedureName}/parameter/{parameterName}/audit-logs', [ProcedureController::class, 'getAuditLogs'])->name('procedure.parameter.audit-logs');

    Route::get('/trigger/{triggerName}/details', [TriggerController::class, 'details'])->name('trigger.details');

    // Routes pour le contrôleur Release
    Route::prefix('releases')->name('releases.')->group(function () {
        Route::get('/', [ReleaseController::class, 'index'])->name('index');
        Route::get('/{id}', [ReleaseController::class, 'show'])->name('show');
    });

    Route::get('/specific-search', [SpecificSearchController::class, 'specificSearch'])->name('specific.search')->name('specific.search');
});

// ROUTES AVEC PERMISSIONS D'ÉCRITURE
Route::middleware(['auth', 'project.permissions:write'])->group(function () {
    Route::post('/table/{tableName}/save-description', [TableController::class, 'saveDescription'])->name('table.savedescription');
    Route::post('/table/{tableName}/column/{columnName}/description', [TableController::class, 'updateColumnDescription'])->name('table.column.updateDescription');
    Route::post('/table/{tableName}/column/{columnName}/possible-values', [TableController::class, 'updateColumnPossibleValues'])->name('table.column.updatePossibleValues');
    Route::post('/table/{tableName}/column/{columnName}/properties', [TableController::class, 'updateColumnProperties'])->name('table.column.properties');
    Route::post('/table/{tableName}/column/{columnName}/release', [TableController::class, 'updateColumnRelease']);
    Route::post('/table/{tableName}/column/add', [TableController::class, 'addColumn'])->name('table.column.add');
    Route::post('/table/{tableName}/relation/add', [TableController::class, 'addRelation'])->name('table.relation.add');

    Route::post('/view/{viewName}/save-description', [ViewController::class, 'saveDescription'])->name('view.saveDescription');
    Route::post('/view/{viewName}/column/{columnName}/description', [ViewController::class, 'saveColumnDescription'])->name('view.column.saveDescription');
    Route::post('/view/{viewName}/save-all', [ViewController::class, 'saveAll'])->name('view.saveAll');
    Route::post('/view/{viewName}/save-structure', [ViewController::class, 'saveStructure'])->name('view.saveStructure');
    Route::post('/view/{viewName}/column/{columnName}/description', [ViewController::class, 'updateColumnDescription'])->name('view.column.updateDescription');
    Route::post('/view/{viewName}/column/{columnName}/rangevalues', [ViewController::class, 'updateColumnRangeValues'])->name('view.column.updateRangeValues');
    Route::post('/view/{viewName}/column/{columnName}/release', [ViewController::class, 'updateColumnRelease']);

    Route::post('/function/{functionName}/description', [FunctionController::class, 'saveDescription'])->name('function.saveDescription');
    Route::post('/function-parameter/{parameterId}/update-description', [FunctionController::class, 'saveParameterDescription'])->name('function.parameter.updateDescription');
    // Route::post('/function/{functionName}/function/{parameterName}/description', [FunctionController::class, 'updateColumnDescription'])->name('function.column.updateDescription');
    // Route::post('/function/{functionName}/function/{parameterName}/rangevalues', [FunctionController::class, 'updateColumnRangeValues'])->name('function.column.updateRangeValues');
    // Route::post('/function/{functionName}/function/{parameterName}/release', [FunctionController::class, 'updateColumnRelease']);
    Route::post('/function/{functionName}/description', [FunctionController::class, 'updateDescription'])
    ->name('function.update-description');
    Route::post('/function/{functionName}/function/{parameterId}/description', [FunctionController::class, 'updateParameterDescription'])
    ->name('function.update-parameter-description');
// Routes AJAX pour les fonctions (retournent du JSON)
    Route::post('/function/{functionName}/function/{parameterName}/range-values', [FunctionController::class, 'updateParameterRangeValues'])
    ->name('function.update-parameter-range-values');
    Route::post('/function/{functionName}/function/{parameterName}/release', [FunctionController::class, 'updateParameterRelease'])
    ->name('function.update-parameter-release');

    Route::post('/procedure/{procedureName}/description', [ProcedureController::class, 'saveDescription'])->name('procedure.saveDescription');
    Route::post('/procedure-parameter/{parameterId}/update-description', [ProcedureController::class, 'saveParameterDescription'])->name('procedure.parameter.updateDescription');
    Route::post('/procedure/{procedureName}/save-all', [ProcedureController::class, 'saveAll'])->name('procedure.saveAll');
    Route::post('/procedure/{procedureName}/parameter/{parameterName}/description', [ProcedureController::class, 'updateColumnDescription'])
    ->name('procedure.column.updateDescription');
    Route::post('/procedure/{procedureName}/parameter/{parameterName}/rangevalues', [ProcedureController::class, 'updateColumnRangeValues'])
    ->name('procedure.column.updateRangeValues');
    Route::post('/procedure/{procedureName}/parameter/{parameterName}/release', [ProcedureController::class, 'updateParameterRelease'])
    ->name('procedure.parameter.updateRelease');

    Route::post('/procedure/{procedureName}/description', [ProcedureController::class, 'updateDescription'])
    ->name('procedure.update-description');


    Route::post('/trigger/{triggerName}/description', [TriggerController::class, 'saveDescription'])->name('trigger.description');
    Route::post('/trigger/{triggerName}/save-all', [TriggerController::class, 'saveAll'])->name('trigger.saveall');

    // Routes pour le contrôleur Release - modification
    Route::prefix('releases')->name('releases.')->group(function () {
        Route::get('/create', [ReleaseController::class, 'create'])->name('create');
        Route::post('/', [ReleaseController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ReleaseController::class, 'edit'])->name('edit');
        Route::post('/{id}', [ReleaseController::class, 'update'])->name('update');
        Route::delete('/{id}', [ReleaseController::class, 'destroy'])->name('destroy');
    });
});


// ROUTES API AVEC PERMISSIONS
Route::middleware('auth')->prefix('api')->group(function () {
    
    // API Lecture
    Route::middleware('project.permissions:read')->group(function () {
        Route::get('/table/{tableName}/details', [TableController::class, 'apiDetails'])->name('api.table.details');
        Route::get('/table-id/{tableName}', [TableController::class, 'getTableId']);
        Route::get('/view/{viewName}/details', [ViewController::class, 'details'])->name('api.view.details');
        Route::get('/function/{functionName}/details', [FunctionController::class, 'apiDetails'])->name('api.function.details');
        Route::get('/procedure/{procedureName}/details', [ProcedureController::class, 'details'])->name('api.procedure.details');
        Route::get('/trigger/{triggerName}/details', [TriggerController::class, 'details'])->name('api.trigger.details');
        Route::get('/releases', [ReleaseApiController::class, 'index'])->name('api.releases.index');
        Route::get('/releases/all', [ReleaseApiController::class, 'getAllVersions'])->name('api.releases.all');
    });
    
    // API Écriture
    Route::middleware('project.permissions:write')->group(function () {
        Route::post('/table/{tableName}/column/{columnName}/release', [TableController::class, 'updateColumnRelease']);
        Route::post('/releases', [ReleaseApiController::class, 'store'])->name('api.releases.store');
        Route::post('/releases/{id}', [ReleaseApiController::class, 'update'])->name('api.releases.update');
        Route::delete('/releases/{id}', [ReleaseApiController::class, 'destroy'])->name('api.releases.destroy');
        Route::post('/releases/assign-to-column', [ReleaseApiController::class, 'assignReleaseToColumn'])->name('api.releases.assign');
        Route::post('/releases/remove-from-column', [ReleaseApiController::class, 'removeReleaseFromColumn'])->name('api.releases.remove');
    });
});

});