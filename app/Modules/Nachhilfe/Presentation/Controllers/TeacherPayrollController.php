<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Nachhilfe\Infrastructure\Models\TeacherPayroll;
use Illuminate\Http\Request;
use App\Modules\Nachhilfe\Domain\Services\PayrollGeneratorService;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;

class TeacherPayrollController extends Controller
{
    public function index(Request $request)
    {
        $payrolls = TeacherPayroll::with('teacher')
            ->when($request->teacher_id, fn($q, $tid) => $q->where('teacher_id', $tid))
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

    public function show(TeacherPayroll $payroll)
    {
        $payroll->load(['teacher', 'items.lesson.subject', 'items.lesson.room']);
        return response()->json($payroll);
    }

    public function generate(Request $request, PayrollGeneratorService $service)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'month' => 'required|date_format:Y-m',
            'total_amount' => 'nullable|numeric|min:0|max:99999999.99',
            'status' => 'nullable|in:Draft,Processing,Paid,Void',
        ]);

        $teacher = Teacher::findOrFail($request->teacher_id);
        
        try {
            // Check if user passed an explicit manual amount (or if they want manual creation)
            if ($request->has('total_amount') && $request->input('total_amount') !== null) {
                // Delete draft payroll if exists
                $existingPayroll = TeacherPayroll::where('teacher_id', $teacher->id)
                    ->where('month', $request->month)
                    ->first();
                
                if ($existingPayroll) {
                    if (in_array($existingPayroll->status, ['Paid', 'Void'])) {
                        return response()->json(['message' => 'A finalized payroll already exists for this month.'], 409);
                    }
                    $existingPayroll->items()->delete();
                    $existingPayroll->forceDelete();
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
            }

            // Otherwise, auto-generate based on lessons
            $payroll = $service->generateForTeacher($teacher, $request->month);
            
            if (!$payroll) {
                return response()->json(['message' => 'No lessons taught this month.'], 404);
            }

            // If a status was explicitly requested for the generated payroll, update it
            if ($request->has('status') && $request->input('status') !== null) {
                $payroll->status = $request->status;
                $payroll->save();
            }

            return response()->json([
                'message' => 'Payroll generated successfully',
                'payroll' => $payroll
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function update(Request $request, TeacherPayroll $payroll)
    {
        $validated = $request->validate([
            'status' => 'required|in:Draft,Processing,Paid,Void',
            'total_amount' => 'required|numeric|min:0|max:99999999.99',
        ]);

        $payroll->status = $validated['status'];
        $payroll->total_amount = $validated['total_amount'];
        $payroll->save();

        return response()->json([
            'message' => 'Payroll updated successfully',
            'payroll' => $payroll->load('teacher')
        ]);
    }

    public function updateStatus(Request $request, TeacherPayroll $payroll)
    {
        $validated = $request->validate([
            'status' => 'required|in:Draft,Processing,Paid,Void'
        ]);

        $payroll->status = $validated['status'];
        $payroll->save();

        return response()->json([
            'message' => 'Payroll status updated successfully',
            'payroll' => $payroll
        ]);
    }

    public function destroy(TeacherPayroll $payroll)
    {
        $payroll->items()->delete();
        $payroll->delete();

        return response()->json([
            'message' => 'Payroll deleted successfully'
        ]);
    }
}
