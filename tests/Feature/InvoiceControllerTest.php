<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Core\Models\User;
use App\Modules\Billing\Infrastructure\Models\Invoice;
use App\Modules\Billing\Infrastructure\Models\InvoiceItem;
use App\Shared\Contracts\Billing\BillingContract;
use Illuminate\Support\Str;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_pay_and_pdf_generation()
    {
        $this->markTestSkipped('Deprecated in favor of Nachhilfe monthly billing engine');
        // 1. Setup Data
        $user = User::create([
            'id' => (string) Str::ulid(),
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password')
        ]);
        $this->actingAs($user, 'sanctum');

        \Illuminate\Support\Facades\Cache::put('module_enabled_Billing', true);

        $billing = app(BillingContract::class);
        $invoiceData = $billing->createInvoice('test_ref', '123', 300, [
            ['description' => 'Math 10h', 'quantity' => 10, 'unit_price' => 20, 'total' => 200],
            ['description' => 'Physics 5h', 'quantity' => 5, 'unit_price' => 20, 'total' => 100],
        ]);

        $invoiceId = $invoiceData['id'];

        // 2. Test Fetching Invoices
        $response = $this->getJson('/api/v1/billing/invoices');
        $response->assertStatus(200);
        $response->assertJsonFragment(['amount' => 300]);

        // 3. Test Partial Payment
        $response = $this->postJson("/api/v1/billing/invoices/{$invoiceId}/pay", [
            'amount' => 100,
            'method' => 'cash'
        ]);
        $response->assertStatus(201);
        
        $invoice = Invoice::find($invoiceId);
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals(100, $invoice->total_paid);
        $this->assertEquals(200, $invoice->balance);

        // 4. Test PDF Generation
        $response = $this->get("/api/v1/billing/invoices/{$invoiceId}/pdf");
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
