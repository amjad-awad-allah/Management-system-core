<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Nachhilfe\Infrastructure\Models\Invoice;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Domain\Services\InvoiceGeneratorService;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::with('student')
            ->when($request->student_id, fn($q, $sid) => $q->where('student_id', $sid))
            ->when($request->month, fn($q, $m) => $q->where('month', $m))
            ->orderBy('month', 'desc')
            ->paginate();

        return response()->json($invoices);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['student', 'items.lesson.subject']);
        return response()->json($invoice);
    }

    public function generate(Request $request, InvoiceGeneratorService $service)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'month' => 'required|date_format:Y-m'
        ]);

        $student = Student::findOrFail($request->student_id);
        
        try {
            $invoice = $service->generateForStudent($student, $request->month);
            
            if (!$invoice) {
                return response()->json(['message' => 'No billable lessons found for this month.'], 404);
            }

            return response()->json([
                'message' => 'Invoice generated successfully',
                'invoice' => $invoice
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,unpaid,paid,void'
        ]);

        $invoice->status = $validated['status'];
        if ($validated['status'] === 'paid' && !$invoice->paid_at) {
            $invoice->paid_at = now();
        } elseif ($validated['status'] !== 'paid') {
            $invoice->paid_at = null;
        }
        
        $invoice->save();

        return response()->json([
            'message' => 'Invoice status updated',
            'invoice' => $invoice
        ]);
    }
}
