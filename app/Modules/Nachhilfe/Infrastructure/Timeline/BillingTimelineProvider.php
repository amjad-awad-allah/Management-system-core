<?php

namespace App\Modules\Nachhilfe\Infrastructure\Timeline;

use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Application\Contracts\TimelineProviderInterface;
use App\Modules\Nachhilfe\Infrastructure\Models\Invoice;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use App\Shared\Contracts\Billing\BillingContract;
use Carbon\Carbon;

class BillingTimelineProvider implements TimelineProviderInterface
{
    private ?BillingContract $billingContract;

    public function __construct()
    {
        try {
            $this->billingContract = app(BillingContract::class);
        } catch (\Exception $e) {
            $this->billingContract = null;
        }
    }

    public function getEvents(Student $student): array
    {
        $events = [];

        // 1. Nachhilfe local invoices that are paid
        $nachhilfeInvoices = Invoice::where('student_id', $student->id)
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->get();

        foreach ($nachhilfeInvoices as $invoice) {
            $events[] = [
                'id' => 'nachhilfe-invoice-paid-' . $invoice->id,
                'type' => 'payment',
                'title' => 'Lesson invoice paid',
                'description' => "Invoice #{$invoice->invoice_number} for {$invoice->month} of €{$invoice->total_amount}",
                'date' => $invoice->paid_at->format('Y-m-d'),
                'time' => '00:00',
                'icon' => 'credit-card',
                'color' => 'emerald',
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->total_amount,
                    'month' => $invoice->month,
                    'source' => 'nachhilfe_invoice'
                ]
            ];
        }

        // 2. Billing payments via BillingContract for StudentPackages
        if ($this->billingContract) {
            $packageIds = StudentPackage::where('student_id', $student->id)->pluck('id')->toArray();
            if (!empty($packageIds)) {
                try {
                    $payments = $this->billingContract->getTransactionsByReferences('student_package', $packageIds);
                    foreach ($payments as $payment) {
                        $createdAt = Carbon::parse($payment['created_at']);
                        $events[] = [
                            'id' => 'billing-payment-' . $payment['id'],
                            'type' => 'payment',
                            'title' => 'Package payment received',
                            'description' => "Payment of €{$payment['amount']} via " . ($payment['method'] ?? 'cash'),
                            'date' => $createdAt->format('Y-m-d'),
                            'time' => $createdAt->format('H:i'),
                            'icon' => 'credit-card',
                            'color' => 'emerald',
                            'metadata' => [
                                'payment_id' => $payment['id'],
                                'amount' => $payment['amount'],
                                'method' => $payment['method'],
                                'status' => $payment['status'],
                                'source' => 'billing_payment'
                            ]
                        ];
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Timeline failed to fetch Billing payments: ' . $e->getMessage());
                }
            }
        }

        return $events;
    }
}
