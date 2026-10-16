<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Modules\Nachhilfe\Infrastructure\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageController
{
    public function index()
    {
        return response()->json(Package::where('is_active', true)->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Center Manager', 'Admin']) && !$user->can('manage nachhilfe') && !$user->can('manage billing'))) {
            abort(403, 'Unauthorized to create packages.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hours' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $validated['id'] = (string) Str::ulid();
        $validated['is_active'] = $validated['is_active'] ?? true;

        $package = Package::create($validated);

        return response()->json($package, 201);
    }
    public function show($id)
    {
        $package = Package::findOrFail($id);
        return response()->json($package);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Center Manager', 'Admin']) && !$user->can('manage nachhilfe') && !$user->can('manage billing'))) {
            abort(403, 'Unauthorized to update packages.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'hours' => 'sometimes|integer|min:1',
            'price' => 'sometimes|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $package = Package::findOrFail($id);
        $package->update($validated);

        return response()->json($package);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Center Manager', 'Admin']) && !$user->can('manage nachhilfe') && !$user->can('manage billing'))) {
            abort(403, 'Unauthorized to delete packages.');
        }

        $package = Package::findOrFail($id);
        $package->delete();
        return response()->json(null, 204);
    }
}
