<?php

namespace App\Modules\Billing\Application\Services;

use App\Modules\Billing\Infrastructure\Models\Invoice;
use App\Modules\Billing\Infrastructure\Models\Payment;
use App\Shared\Contracts\Billing\BillingContract;
use Carbon\Carbon;
use Exception;

class BillingService implements BillingContract
{
    public function createInvoice(string $referenceType, string $referenceId, float $amount, array $items = []): array
    {
        $invoice = Invoice::create([
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'amount' => $amount,
            'status' => 'unpaid',
            'due_date' => Carbon::now()->addDays(7),
        ]);

        foreach ($items as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'] ?? $amount,
                'total' => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? $amount),
            ]);
        }

        return $invoice->load('items')->toArray();
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
        } elseif ($totalPaid > 0) {
            $invoice->update(['status' => 'partially_paid']);
        }

        return $payment->toArray();
    }
}
