<?php

namespace App\Core\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Models\Role;
use App\Core\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('manage roles'))) {
            abort(403, 'Unauthorized to list roles.');
        }

        $roles = Role::with('permissions')->get();
        return response()->json(['data' => $roles]);
    }

    public function permissions(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('manage roles'))) {
            abort(403, 'Unauthorized to list permissions.');
        }

        $permissions = Permission::all();
        return response()->json(['data' => $permissions]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('manage roles'))) {
            abort(403, 'Unauthorized to view this role.');
        }

        $role = Role::with('permissions')->findOrFail($id);
        return response()->json(['data' => $role]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('manage roles'))) {
            abort(403, 'Unauthorized to create roles.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json([
            'message' => 'Role created successfully',
            'data' => $role->load('permissions')
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('manage roles'))) {
            abort(403, 'Unauthorized to update roles.');
        }

        $role = Role::findOrFail($id);
        
        if ($role->name === 'Super Admin') {
            return response()->json(['message' => 'Cannot modify Super Admin role'], 400);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        if (isset($validated['name'])) {
            $role->name = $validated['name'];
            $role->save();
        }

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => $role->load('permissions')
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('manage roles'))) {
            abort(403, 'Unauthorized to delete roles.');
        }

        $role = Role::findOrFail($id);

        if ($role->name === 'Super Admin') {
            return response()->json(['message' => 'Cannot delete Super Admin role'], 400);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }
}
