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
            ->orderBy('month', 'desc')
            ->paginate();

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
            'month' => 'required|date_format:Y-m'
        ]);

        $teacher = Teacher::findOrFail($request->teacher_id);
        
        try {
            $payroll = $service->generateForTeacher($teacher, $request->month);
            
            if (!$payroll) {
                return response()->json(['message' => 'No lessons taught this month.'], 404);
            }

            return response()->json([
                'message' => 'Payroll generated successfully',
                'payroll' => $payroll
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
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
}
