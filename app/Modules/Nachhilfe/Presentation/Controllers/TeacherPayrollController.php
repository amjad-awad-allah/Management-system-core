<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use Illuminate\Http\Request;
use App\Modules\Nachhilfe\Domain\Services\PayrollGeneratorService;
use App\Modules\Nachhilfe\Domain\Services\PayrollStateTransitionService;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;

class TeacherPayrollController extends Controller
{
    public function summary(Request $request, \App\Modules\Nachhilfe\Application\Services\PayrollCalculationService $service)
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole(['Super Admin', 'Center Manager']) && !$user->can('manage billing'))) {
            abort(403, 'Unauthorized to view payroll summary.');
        }

        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $summary = $service->getPayrollSummary($validated['month']);
        return response()->json($summary);
    }

    public function approve(Request $request, \App\Modules\Nachhilfe\Application\Services\PayrollCalculationService $service)
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole(['Super Admin', 'Center Manager']) && !$user->can('manage billing'))) {
            abort(403, 'Unauthorized to approve payroll.');
        }

        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $userId = $user->id;
        $summary = $service->approvePayroll($validated['month'], $userId);

        return response()->json([
            'message' => 'Payroll snapshot approved successfully',
            'summary' => $summary,
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $canManageBilling = $user->hasRole(['Super Admin', 'Center Manager']) || $user->can('manage billing');

        $query = TeacherPayroll::with('teacher');

        if (!$canManageBilling) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $query->where('teacher_id', $teacher->id);
            } else {
                abort(403, 'Unauthorized access to payrolls.');
            }
        } else {
            if ($request->teacher_id) {
                $query->where('teacher_id', $request->teacher_id);
            }
        }

        $payrolls = $query
            ->when($request->month, fn($q, $m) => $q->where('month', $m))
            ->when($request->date, fn($q, $d) => $q->whereDate('created_at', $d))
            ->when($request->from_date && $request->to_date, fn($q) => $q->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]))
            ->when($request->search, function ($q, $search) {
                $q->whereHas('teacher', function ($tq) use ($search) {
                    $tq->where('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($payrolls);
    }

    public function show(Request $request, TeacherPayroll $payroll)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $canManageBilling = $user->hasRole(['Super Admin', 'Center Manager']) || $user->can('manage billing');
        $isOwner = $payroll->teacher && (string) $payroll->teacher->user_id === (string) $user->id;

        if (!$canManageBilling && !$isOwner) {
            abort(403, 'Unauthorized access to this payroll record.');
        }

        $payroll->load(['teacher', 'items.lesson.subject', 'items.lesson.room']);
        return response()->json($payroll);
    }

    public function generate(Request $request, PayrollGeneratorService $service, PayrollStateTransitionService $transitionService)
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole(['Super Admin', 'Center Manager']) && !$user->can('manage billing'))) {
            abort(403, 'Unauthorized to generate payroll.');
        }

        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'month' => 'required|date_format:Y-m',
            'total_amount' => 'nullable|numeric|min:0|max:99999999.99',
            'status' => 'nullable|in:Draft,Processing,draft,processing',
        ]);

        $teacher = Teacher::findOrFail($request->teacher_id);
        
        try {
            // Check if user passed an explicit manual amount (or if they want manual creation)
            if ($request->has('total_amount') && $request->input('total_amount') !== null) {
                return \Illuminate\Support\Facades\DB::transaction(function () use ($teacher, $request) {
                    // Pessimistically lock the Teacher record to serialize concurrent generation for this teacher
                    Teacher::where('id', $teacher->id)->lockForUpdate()->first();

                    $existingPayroll = TeacherPayroll::where('teacher_id', $teacher->id)
                        ->where('month', $request->month)
                        ->lockForUpdate()
                        ->first();

                    if ($existingPayroll) {
                        if ($existingPayroll->isSealedOrApproved()) {
                            return response()->json(['message' => 'A finalized payroll already exists for this month.'], 409);
                        }

                        $existingStatusLower = strtolower((string) $existingPayroll->status);
                        if (!in_array($existingStatusLower, ['draft', 'processing'], true)) {
                            return response()->json([
                                'message' => "Cannot replace payroll with non-draft status '{$existingPayroll->status}'."
                            ], 409);
                        }

                        $existingPayroll->items()->delete();
                        $existingPayroll->delete();
                    }

                    $status = $request->input('status', 'Draft');
                    $payroll = TeacherPayroll::create([
                        'teacher_id' => $teacher->id,
                        'month' => $request->month,
                        'total_amount' => $request->total_amount,
                        'status' => $status,
                    ]);

                    return response()->json([
                        'message' => 'Manual payroll created successfully',
                        'payroll' => $payroll
                    ], 201);
                });
            }

            // Otherwise, auto-generate based on lessons
            $payroll = $service->generateForTeacher($teacher, $request->month);
            
            if (!$payroll) {
                return response()->json(['message' => 'No lessons taught this month.'], 404);
            }

            // If a status was explicitly requested for the generated payroll, transition via domain service
            if ($request->has('status') && $request->input('status') !== null) {
                $payroll = $transitionService->transition(
                    $payroll,
                    $request->status,
                    $user,
                    ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]
                );
            }

            return response()->json([
                'message' => 'Payroll generated successfully',
                'payroll' => $payroll
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function update(Request $request, TeacherPayroll $payroll, PayrollStateTransitionService $transitionService)
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole(['Super Admin', 'Center Manager']) && !$user->can('manage billing'))) {
            abort(403, 'Unauthorized to update payroll.');
        }

        $validated = $request->validate([
            'status' => 'required|in:Draft,Processing,Approved,Paid,Void,draft,processing,approved,paid,void',
            'total_amount' => 'required|numeric|min:0|max:99999999.99',
            'void_reason' => 'nullable|string|max:500',
        ]);

        try {
            $updated = $transitionService->transition(
                $payroll,
                $validated['status'],
                $user,
                [
                    'total_amount' => $validated['total_amount'],
                    'void_reason' => $request->input('void_reason'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            return response()->json([
                'message' => 'Payroll updated successfully',
                'payroll' => $updated->load('teacher')
            ]);
        } catch (\DomainException $e) {
            $status = str_contains($e->getMessage(), 'Cannot modify financial figures') ? 409 : 422;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }

    public function updateStatus(Request $request, TeacherPayroll $payroll, PayrollStateTransitionService $transitionService)
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole(['Super Admin', 'Center Manager']) && !$user->can('manage billing'))) {
            abort(403, 'Unauthorized to update payroll status.');
        }

        $validated = $request->validate([
            'status' => 'required|in:Draft,Processing,Approved,Paid,Void,draft,processing,approved,paid,void',
            'void_reason' => 'nullable|string|max:500',
        ]);

        try {
            $updated = $transitionService->transition(
                $payroll,
                $validated['status'],
                $user,
                [
                    'void_reason' => $request->input('void_reason'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            return response()->json([
                'message' => 'Payroll status updated successfully',
                'payroll' => $updated
            ]);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, TeacherPayroll $payroll)
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole(['Super Admin', 'Center Manager']) && !$user->can('manage billing'))) {
            abort(403, 'Unauthorized to delete payroll.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($payroll) {
            /** @var TeacherPayroll $lockedPayroll */
            $lockedPayroll = TeacherPayroll::where('id', $payroll->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayroll->isSealedOrApproved()) {
                return response()->json([
                    'message' => 'Cannot delete an approved, paid, or void payroll record.'
                ], 409);
            }

            $lockedPayroll->items()->delete();
            $lockedPayroll->delete();

            return response()->json([
                'message' => 'Payroll deleted successfully'
            ]);
        });
    }
}
