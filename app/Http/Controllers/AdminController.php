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
}