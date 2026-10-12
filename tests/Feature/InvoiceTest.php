<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MakesTenants;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_totals_taxes_and_partial_payments(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $customer = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $this->actingAs($owner);

        // 2 × £100 + 1 × £50 = £250; 10% discount = £25; taxable £225; 20% VAT = £45; total £270.
        $response = $this->post(route('invoices.store'), [
            'customer_id' => $customer->id, 'currency' => 'GBP',
            'discount_percent' => 10, 'tax_percent' => 20, 'payment_terms_days' => 14,
            'items' => [
                ['description' => 'Labour', 'quantity' => 2, 'unit_price' => 100],
                ['description' => 'Parts', 'quantity' => 1, 'unit_price' => 50],
            ],
        ]);
        $invoice = Invoice::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(250.00, (float) $invoice->subtotal);
        $this->assertEquals(25.00, (float) $invoice->discount_total);
        $this->assertEquals(45.00, (float) $invoice->tax_total);
        $this->assertEquals(270.00, (float) $invoice->total);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{4}$/', $invoice->number);

        // Issue then partial payment.
        $this->post(route('invoices.issue', $invoice))->assertRedirect();
        $this->assertEquals('issued', $invoice->fresh()->status);
        $this->post(route('invoices.payments', $invoice), ['amount' => 100, 'method' => 'bank', 'reference' => 'BANK-1']);
        $this->assertEquals('partially_paid', $invoice->fresh()->status);
        $this->assertEquals(170.00, $invoice->fresh()->balance());

        // Overpayment blocked.
        $this->post(route('invoices.payments', $invoice), ['amount' => 500, 'method' => 'bank'])->assertStatus(422);

        // Duplicate reference blocked (idempotency).
        $this->post(route('invoices.payments', $invoice), ['amount' => 50, 'method' => 'bank', 'reference' => 'BANK-1'])->assertStatus(422);

        // Second payment completes; void with payments blocked.
        $this->post(route('invoices.payments', $invoice), ['amount' => 170, 'method' => 'bank', 'reference' => 'BANK-2']);
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->post(route('invoices.void', $invoice))->assertStatus(422);
    }

    public function test_invoice_pdf_downloads(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $customer = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $invoice = Invoice::create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id,
            'status' => 'issued', 'currency' => 'GBP', 'tax_percent' => 20]);
        $invoice->items()->create(['description' => 'Work', 'quantity' => 1, 'unit_price' => 120]);
        $invoice->load('items');
        $invoice->recalculate();

        $this->actingAs($owner);
        $response = $this->get(route('invoices.pdf', $invoice))->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }
}
