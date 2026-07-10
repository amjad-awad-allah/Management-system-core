<?php

namespace App\Modules\Billing\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Infrastructure\Models\Invoice;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['payments', 'items'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invoice) {
                // If reference is student_package, we could load student data, but we can do it via API
                return $invoice;
            });
        return response()->json($invoices);
    }

    public function pay(Request $request, string $id, \App\Shared\Contracts\Billing\BillingContract $billing)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|in:cash,card,bank_transfer'
        ]);

        $payment = $billing->recordPayment($id, $request->amount, $request->method);

        return response()->json($payment, 201);
    }

    public function pdf(string $id)
    {
        $invoice = Invoice::with(['payments', 'items'])->findOrFail($id);

        $pdf = Pdf::loadView('billing::invoice', compact('invoice'));

        return $pdf->download("invoice-{$invoice->id}.pdf");
    }
}
