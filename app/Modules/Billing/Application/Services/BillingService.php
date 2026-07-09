<?php

namespace App\Modules\Billing\Application\Services;

use App\Modules\Billing\Infrastructure\Models\Invoice;
use App\Modules\Billing\Infrastructure\Models\Payment;
use App\Shared\Contracts\Billing\BillingContract;
use Carbon\Carbon;
use Exception;

class BillingService implements BillingContract
{
    public function createInvoice(string $referenceType, string $referenceId, float $amount): array
    {
        $invoice = Invoice::create([
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'amount' => $amount,
            'status' => 'unpaid',
            'due_date' => Carbon::now()->addDays(7),
        ]);

        return $invoice->toArray();
    }

    public function recordPayment(string $invoiceId, float $amount, string $method): array
    {
        $invoice = Invoice::findOrFail($invoiceId);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'method' => $method,
            'status' => 'completed',
        ]);

        $totalPaid = $invoice->payments()->sum('amount');
        if ($totalPaid >= $invoice->amount) {
            $invoice->update(['status' => 'paid']);
        }

        return $payment->toArray();
    }
}
