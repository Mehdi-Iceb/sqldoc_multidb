<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Project;
use App\Models\DbDescription;
use App\Models\UserProjectAccess;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Index', [
            'users' => User::with(['role', 'projectAccesses.project'])->get(),
            'roles' => Role::with('permissions')->get(),
            'permissions' => Permission::all(),
            'projects' => Project::with('user')->whereNull('deleted_at')->get()
        ]);
    }

    public function createUser(Request $request)
    {
        $messages = [
            'email.unique' => 'E-mail already used.',
            'password.min' => 'Password must contain at least 8 characters.'
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id'
        ], $messages);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role_id' => $validated['role_id']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User successfully created',
            'user' => $user
        ]);
    }

    public function updateUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id'
        ]);

        $user->update(['role_id' => $validated['role_id']]);

        return response()->json([
            'success' => true,
            'message' => 'Rôle mis à jour avec succès'
        ]);
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

    /**
     * Accorder l'accès à un projet pour un utilisateur
     */
    public function grantProjectAccess(Request $request)
    {
        try {
            if (!$this->isUserAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized access. Administrator privileges required.'
                ], 403);
            }

            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'project_ids' => 'required|array',
                'project_ids.*' => 'exists:projects,id',
                'access_level' => 'required|in:read,write,admin'
            ]);

            $userId = $validated['user_id'];
            $accessLevel = $validated['access_level'];
            $projectIds = $validated['project_ids'];

            DB::transaction(function () use ($userId, $accessLevel, $projectIds) {
                foreach ($projectIds as $projectId) {
                    $existingAccess = UserProjectAccess::where('user_id', $userId)
                        ->where('project_id', $projectId)
                        ->first();

                    if ($existingAccess) {
                        if ($existingAccess->access_level !== $accessLevel) {
                            $existingAccess->update(['access_level' => $accessLevel]);
                        }
                    } else {
                        UserProjectAccess::create([
                            'user_id' => $userId,
                            'project_id' => $projectId,
                            'access_level' => $accessLevel,
                        ]);
                    }

                    Log::info('Admin - Accès projet accordé/modifié', [
                        'user_id' => $userId,
                        'project_id' => $projectId,
                        'access_level' => $accessLevel,
                        'admin_id' => auth()->id()
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Access granted/updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans AdminController::grantProjectAccess', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error granting access: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Révoquer l'accès à un projet pour un utilisateur
     */
    public function revokeProjectAccess(Request $request)
    {
        try {
            if (!$this->isUserAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé.'
                ], 403);
            }

            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'project_id' => 'required|exists:projects,id'
            ]);

            $deleted = UserProjectAccess::where('user_id', $validated['user_id'])
                ->where('project_id', $validated['project_id'])
                ->delete();

            if ($deleted) {
                Log::info('Admin - Accès projet révoqué', [
                    'user_id' => $validated['user_id'],
                    'project_id' => $validated['project_id'],
                    'admin_id' => auth()->id()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Accès révoqué avec succès'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Aucun accès trouvé à révoquer'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Erreur dans AdminController::revokeProjectAccess', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la révocation de l\'accès: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les accès aux projets pour un utilisateur
     */
    public function getUserProjectAccesses($userId)
    {
        try {
            if (!$this->isUserAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé.'
                ], 403);
            }

            $user = User::with(['projectAccesses.project'])->findOrFail($userId);
            
            $accesses = $user->projectAccesses->map(function ($access) {
                return [
                    'id' => $access->id,
                    'project_id' => $access->project_id,
                    'project_name' => $access->project->name,
                    'project_owner' => $access->project->user->name,
                    'access_level' => $access->access_level,
                    'granted_at' => $access->created_at->format('d/m/Y H:i')
                ];
            });

            return response()->json([
                'success' => true,
                'accesses' => $accesses
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans AdminController::getUserProjectAccesses', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du chargement des accès: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir tous les projets disponibles pour attribution
     */
    public function getAvailableProjects()
    {
        try {
            if (!$this->isUserAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé.'
                ], 403);
            }

            $projects = Project::with('user')
                ->whereNull('deleted_at')
                ->select('id', 'name', 'description', 'db_type', 'user_id')
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'description' => $project->description,
                        'db_type' => $project->db_type,
                        'owner_name' => $project->user->name,
                        'display_name' => $project->name . ' (' . $project->user->name . ')'
                    ];
                });

            return response()->json([
                'success' => true,
                'projects' => $projects
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans AdminController::getAvailableProjects', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du chargement des projets: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDeletedProjects()
    {
        try {
            if (!$this->isUserAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Accès non autorisé. Privilèges administrateur requis.'
                ], 403);
            }

            $deletedProjects = Project::onlyTrashed()
                ->with('user:id,name,email')
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