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
            ->when($request->date, fn($q, $d) => $q->whereDate('created_at', $d))
            ->when($request->from_date && $request->to_date, fn($q) => $q->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]))
            ->when($request->search, function ($q, $search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

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
            'month' => 'required|date_format:Y-m',
            'total_amount' => 'nullable|numeric|min:0|max:99999999.99',
            'status' => 'nullable|in:draft,unpaid,paid,void',
            'due_date' => 'nullable|date',
        ]);

        $student = Student::findOrFail($request->student_id);
        
        try {
            // Check if user passed an explicit manual amount (or if they want manual creation)
            if ($request->has('total_amount') && $request->input('total_amount') !== null) {
                // Delete draft invoice if exists
                $existingInvoice = Invoice::where('student_id', $student->id)
                    ->where('month', $request->month)
                    ->first();
                
                if ($existingInvoice) {
                    if ($existingInvoice->status !== 'draft') {
                        return response()->json(['message' => 'A finalized invoice already exists for this month.'], 409);
                    }
                    $existingInvoice->items()->delete();
                    $existingInvoice->forceDelete();
                }

                $status = $request->input('status', 'unpaid');
                $invoice = Invoice::create([
                    'invoice_number' => 'INV-' . strtoupper(\Illuminate\Support\Str::random(8)),
                    'student_id' => $student->id,
                    'month' => $request->month,
                    'total_amount' => $request->total_amount,
                    'status' => $status,
                    'due_date' => $request->input('due_date') ?? \Carbon\Carbon::createFromFormat('Y-m', $request->month)->endOfMonth()->addDays(14)->toDateString(),
                    'paid_at' => $status === 'paid' ? now() : null,
                ]);

                return response()->json([
                    'message' => 'Manual invoice created successfully',
                    'invoice' => $invoice
                ], 201);
            }

            // Otherwise, auto-generate based on lessons
            $invoice = $service->generateForStudent($student, $request->month);
            
            if (!$invoice) {
                return response()->json(['message' => 'No billable lessons found for this month.'], 404);
            }

            // If a status was explicitly requested for the generated invoice, update it
            if ($request->has('status') && $request->input('status') !== null) {
                $invoice->status = $request->status;
                if ($request->status === 'paid') {
                    $invoice->paid_at = now();
                }
                $invoice->save();
            }

            return response()->json([
                'message' => 'Invoice generated successfully',
                'invoice' => $invoice
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,unpaid,paid,void',
            'total_amount' => 'required|numeric|min:0|max:99999999.99',
            'due_date' => 'required|date',
        ]);

        $invoice->status = $validated['status'];
        $invoice->total_amount = $validated['total_amount'];
        $invoice->due_date = $validated['due_date'];

        if ($validated['status'] === 'paid' && !$invoice->paid_at) {
            $invoice->paid_at = now();
        } elseif ($validated['status'] !== 'paid') {
            $invoice->paid_at = null;
        }

        $invoice->save();

        return response()->json([
            'message' => 'Invoice updated successfully',
            'invoice' => $invoice->load('student')
        ]);
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

    public function destroy(Invoice $invoice)
    {
        $invoice->items()->delete();
        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully'
        ]);
    }
}
