<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudentPackageController
{
    public function index(string $studentId)
    {
        $packages = StudentPackage::where('student_id', $studentId)->get();
        return response()->json($packages);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|string',
            'package_id' => 'nullable|string',
            'subject_id' => 'nullable|string',
            'funding_source' => 'required|in:private,jobcenter',
            'voucher_reference' => 'nullable|string|max:255',
            'total_hours' => 'required|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        $validated['id'] = (string) Str::ulid();
        $validated['remaining_hours'] = $validated['total_hours'];
        $validated['status'] = 'active';

        $package = StudentPackage::create($validated);

        return response()->json($package, 201);
    }
}
