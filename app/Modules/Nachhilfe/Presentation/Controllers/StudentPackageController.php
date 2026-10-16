<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudentPackageController
{
    public function index(Request $request, string $studentId)
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        if ($user->hasRole('Student')) {
            $student = \App\Modules\Nachhilfe\Infrastructure\Models\Student::where('user_id', $user->id)->first();
            if (!$student || $student->id !== $studentId) {
                abort(403, 'Unauthorized to view this student packages.');
            }
        } elseif (!$user->hasAnyRole(['Super Admin', 'Center Manager', 'Admin']) && !$user->can('manage students') && !$user->can('manage billing')) {
            abort(403, 'Unauthorized.');
        }

        $packages = StudentPackage::where('student_id', $studentId)->get();
        return response()->json($packages);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Center Manager', 'Admin']) && !$user->can('manage billing') && !$user->can('manage students'))) {
            abort(403, 'Unauthorized to assign packages.');
        }

        $validated = $request->validate([
            'student_id' => 'required|string',
            'package_id' => 'nullable|string',
            'subject_id' => 'nullable|string',
            'funding_source' => 'required|in:private,jobcenter',
            'voucher_reference' => 'nullable|string|max:255',
            'total_hours' => 'required|integer|min:1',
            'expires_at' => 'nullable|date',
            'status' => 'sometimes|string|in:active,pending_approval,rejected,expired,exhausted',
        ]);

        $validated['id'] = (string) Str::ulid();
        $validated['remaining_hours'] = $validated['total_hours'];
        $validated['status'] = $request->input('status', 'active');

        $package = StudentPackage::create($validated);

        if ($validated['funding_source'] === 'private') {
            try {
                $billing = app(\App\Shared\Contracts\Billing\BillingContract::class);
                // For a real app, amount should come from the Package model. 
                // Since this is a demo, we will calculate a dummy amount (e.g. 15 per hour).
                $amount = $validated['total_hours'] * 15;
                $items = [
                    [
                        'description' => "Student Package ({$validated['total_hours']} Hours)",
                        'quantity' => $validated['total_hours'],
                        'unit_price' => 15,
                        'total' => $amount
                    ]
                ];
                $billing->createInvoice('student_package', $package->id, $amount, $items);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to generate invoice', ['error' => $e->getMessage()]);
            }
        }

        return response()->json($package, 201);
    }
}
