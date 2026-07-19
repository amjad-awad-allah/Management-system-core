<?php

namespace App\Shared\Contracts\Billing;

interface BillingContract
{
    /**
     * Create an invoice for a specific business reference (e.g. lesson, appointment).
     *
     * @return array<string, mixed>
     */
    public function createInvoice(string $referenceType, string $referenceId, float $amount, array $items = []): array;

    /**
     * Record a payment against a specific invoice.
     *
     * @return array<string, mixed>
     */
    public function recordPayment(string $invoiceId, float $amount, string $method): array;

    /**
     * Get transactions matching a specific reference type and set of reference IDs.
     *
     * @param string $referenceType
     * @param array<string> $referenceIds
     * @return array<array<string, mixed>>
     */
    public function getTransactionsByReferences(string $referenceType, array $referenceIds): array;
}
