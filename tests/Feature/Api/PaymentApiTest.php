<?php

use App\Actions\Invoices\MarkInvoicePaid;
use App\Enums\PaymentProvider;
use App\Enums\TenantPlan;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;

function paymentUser(): array
{
    $tenant = Tenant::factory()->create(['plan' => TenantPlan::Pro]);
    $user = User::factory()->for($tenant, 'tenant')->create(['role' => UserRole::Owner]);
    $client = Client::factory()->for($tenant)->create();

    return [$user, $tenant, $client];
}

it('lists payments across all invoices for the tenant', function (): void {
    [$user, $tenant, $client] = paymentUser();
    $invoice = Invoice::factory()->sent()->for($tenant)->for($client)->create();

    app(MarkInvoicePaid::class)->execute(
        invoice: $invoice,
        amount: $invoice->total,
        currency: $invoice->currency,
        provider: PaymentProvider::Manual,
        providerReference: 'ref-100',
    );

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/payments')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'amount', 'currency', 'provider', 'paid_at']]]);
});

it('lists payments for a specific invoice', function (): void {
    [$user, $tenant, $client] = paymentUser();
    $invoice = Invoice::factory()->sent()->for($tenant)->for($client)->create();

    app(MarkInvoicePaid::class)->execute(
        invoice: $invoice,
        amount: $invoice->total,
        currency: $invoice->currency,
        provider: PaymentProvider::Manual,
        providerReference: 'ref-001',
    );

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/invoices/{$invoice->public_id}/payments")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('records a manual payment', function (): void {
    [$user, $tenant, $client] = paymentUser();
    $invoice = Invoice::factory()->sent()->for($tenant)->for($client)->create(['total' => 10000]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/invoices/{$invoice->public_id}/payments", [
            'amount' => 10000,
            'currency' => 'USD',
        ])
        ->assertCreated()
        ->assertJsonPath('data.amount', 10000)
        ->assertJsonPath('data.provider', 'manual');

    $this->assertDatabaseHas('payments', [
        'invoice_id' => $invoice->id,
        'amount' => 10000,
        'provider' => 'manual',
    ]);
});

it('marks invoice as paid when full amount recorded', function (): void {
    [$user, $tenant, $client] = paymentUser();
    $invoice = Invoice::factory()->sent()->for($tenant)->for($client)->create(['total' => 5000]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/invoices/{$invoice->public_id}/payments", [
            'amount' => 5000,
            'currency' => 'USD',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'status' => 'paid',
        'amount_paid' => 5000,
    ]);
});

it('rejects payment on a non-payable invoice', function (): void {
    [$user, $tenant, $client] = paymentUser();
    $invoice = Invoice::factory()->paid()->for($tenant)->for($client)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/invoices/{$invoice->public_id}/payments", [
            'amount' => 1000,
            'currency' => 'USD',
        ])
        ->assertStatus(422);
});

it('cannot access another tenant\'s invoice payments', function (): void {
    [$user] = paymentUser();
    $otherInvoice = Invoice::factory()->sent()->for(Tenant::factory())->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/invoices/{$otherInvoice->public_id}/payments", [
            'amount' => 1000,
            'currency' => 'USD',
        ])
        ->assertNotFound();
});
