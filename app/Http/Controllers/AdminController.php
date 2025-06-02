<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Index', [
            'users' => User::with('role')->get(),
            'roles' => Role::with('permissions')->get(),
            'permissions' => Permission::all()
        ]);
    }

    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role_id' => $validated['role_id']
        ]);

        return back()->with('success', 'Utilisateur créé avec succès');
    }

    public function updateUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id'
        ]);

        $user->update(['role_id' => $validated['role_id']]);

        return back()->with('success', 'Rôle mis à jour avec succès');
    }

    public function updateRolePermissions(Request $request, Role $role)
    {
        try {
            Log::info('Permissions reçues:', $request->all());
            Log::info('Role:', ['id' => $role->id, 'name' => $role->name]);

            $validated = $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'exists:permissions,id'
            ]);

            DB::beginTransaction();
            $result = $role->permissions()->sync($request->permissions);
            Log::info('Sync result:', $result);
            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDeletedProjects()
    {
        try {
            // Vérifier si l'utilisateur est admin
            if (!$this->isUserAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé. Privilèges administrateur requis.'
                ], 403);
            }

            // Récupérer tous les projets supprimés (de tous les utilisateurs)
            $deletedProjects = Project::onlyTrashed()
                ->with('user:id,name,email') // Inclure les infos utilisateur
                ->orderBy('deleted_at', 'desc')
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'description' => $project->description,
                        'db_type' => $project->db_type,
                        'user' => [
                            'id' => $project->user->id,
                            'name' => $project->user->name,
                            'email' => $project->user->email,
                        ],
                        'deleted_at' => $project->deleted_at ? $project->deleted_at->format('d/m/Y H:i') : null,
                        'created_at' => $project->created_at ? $project->created_at->format('d/m/Y H:i') : null
                    ];
                });

            Log::info('Admin - Projets supprimés récupérés', [
                'admin_id' => auth()->id(),
                'count' => $deletedProjects->count()
            ]);

            return response()->json([
                'success' => true,
                'projects' => $deletedProjects
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur dans AdminController::getDeletedProjects', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du chargement des projets supprimés: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaure un projet supprimé (admin uniquement)
     */
    public function restoreProject($id)
    {
        try {
            if (!$this->isUserAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé. Privilèges administrateur requis.'
                ], 403);
            }

            $project = Project::withTrashed()->findOrFail($id);
            
            if (!$project->trashed()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ce projet n\'est pas supprimé'
                ], 400);
            }

            $project->restore();

            Log::info('Admin - Projet restauré', [
                'project_id' => $id,
                'project_name' => $project->name,
                'project_owner' => $project->user->name,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Projet restauré avec succès'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur dans AdminController::restoreProject', [
                'id' => $id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la restauration du projet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Suppression définitive d'un projet (admin uniquement)
     */
    public function forceDeleteProject($id)
    {
        try {
            if (!$this->isUserAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé. Privilèges administrateur requis.'
                ], 403);
            }

            $project = Project::withTrashed()->findOrFail($id);
            
            // Vérifier s'il y a des dépendances critiques
            $dbDescriptionsCount = DbDescription::where('project_id', $project->id)->count();
            
            if ($dbDescriptionsCount > 0) {
                return response()->json([
                    'success' => false,
                    'error' => "Impossible de supprimer définitivement ce projet car il contient {$dbDescriptionsCount} base(s) de données associée(s)."
                ], 400);
            }

            $projectName = $project->name;
            $projectOwner = $project->user->name;
            $project->forceDelete();

            Log::warning('Admin - Projet supprimé définitivement', [
                'project_id' => $id,
                'project_name' => $projectName,
                'project_owner' => $projectOwner,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Projet supprimé définitivement'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur dans AdminController::forceDeleteProject', [
                'id' => $id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la suppression définitive: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des projets pour les admins
     */
    public function getProjectStats()
    {
        try {
            if (!$this->isUserAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé.'
                ], 403);
            }

            $stats = [
                'total_projects' => Project::withTrashed()->count(),
                'active_projects' => Project::count(),
                'deleted_projects' => Project::onlyTrashed()->count(),
                'projects_by_user' => Project::withTrashed()
                    ->select('user_id', DB::raw('count(*) as total'))
                    ->with('user:id,name')
                    ->groupBy('user_id')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'user_name' => $item->user->name,
                            'project_count' => $item->total
                        ];
                    }),
                'projects_by_type' => Project::withTrashed()
                    ->select('db_type', DB::raw('count(*) as total'))
                    ->groupBy('db_type')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->db_type => $item->total];
                    })
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur dans AdminController::getProjectStats', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du chargement des statistiques: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * check if admin connecté
     */
    private function isUserAdmin()
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        
        if ($user->role && $user->role->name === 'admin') {
            return true;
        }

        
        if (isset($user->role) && $user->role === 'admin') {
            return true;
        }

       
        if ($user->role && $user->role->permissions()->where('name', 'manage_projects')->exists()) {
            return true;
        }

        return false;
    }

}