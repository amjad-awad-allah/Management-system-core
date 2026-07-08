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
}
