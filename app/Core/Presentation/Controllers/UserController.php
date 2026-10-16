<?php

namespace App\Core\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('manage users'))) {
            abort(403, 'Unauthorized to list users.');
        }

        $users = User::with('roles')->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $users]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $currentUser = $request->user();
        if (!$currentUser || ((string) $currentUser->id !== $id && !$currentUser->hasRole('Super Admin') && !$currentUser->can('manage users'))) {
            abort(403, 'Unauthorized to view this user.');
        }

        $user = User::with('roles')->findOrFail($id);
        return response()->json(['data' => $user]);
    }

    public function store(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        if (!$currentUser || (!$currentUser->hasRole('Super Admin') && !$currentUser->can('manage users'))) {
            abort(403, 'Unauthorized to create users.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name'
        ]);

        if (!empty($validated['roles']) && in_array('Super Admin', $validated['roles']) && !$currentUser->hasRole('Super Admin')) {
            abort(403, 'Only Super Admin can assign the Super Admin role.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user->load('roles')
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $currentUser = $request->user();
        if (!$currentUser || (!$currentUser->hasRole('Super Admin') && !$currentUser->can('manage users'))) {
            abort(403, 'Unauthorized to update users.');
        }

        $user = User::findOrFail($id);

        if ($user->hasRole('Super Admin') && !$currentUser->hasRole('Super Admin')) {
            abort(403, 'Only Super Admin can modify a Super Admin account.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name'
        ]);

        if (!empty($validated['roles']) && in_array('Super Admin', $validated['roles']) && !$currentUser->hasRole('Super Admin')) {
            abort(403, 'Only Super Admin can assign the Super Admin role.');
        }

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user->load('roles')
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $currentUser = $request->user();
        if (!$currentUser || (!$currentUser->hasRole('Super Admin') && !$currentUser->can('manage users'))) {
            abort(403, 'Unauthorized to delete users.');
        }

        $user = User::findOrFail($id);

        if ($user->hasRole('Super Admin') && !$currentUser->hasRole('Super Admin')) {
            abort(403, 'Only Super Admin can delete a Super Admin account.');
        }
        
        if ($user->id === $currentUser->id) {
            return response()->json(['message' => 'Cannot delete yourself'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
