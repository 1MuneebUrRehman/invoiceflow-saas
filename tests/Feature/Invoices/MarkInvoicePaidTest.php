<?php

use App\Actions\Invoices\MarkInvoicePaid;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentProvider;
use App\Events\InvoicePaid;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->client = Client::factory()->for($this->tenant)->create();
    $this->action = app(MarkInvoicePaid::class);
});

it('records a payment row', function (): void {
    $invoice = Invoice::factory()->sent()->for($this->tenant)->for($this->client)->create(['total' => 10000]);

    $this->action->execute($invoice, 10000, 'USD', PaymentProvider::Stripe, 'pi_abc');

    $this->assertDatabaseHas('payments', [
        'invoice_id' => $invoice->id,
        'amount' => 10000,
        'currency' => 'USD',
        'provider_reference' => 'pi_abc',
    ]);
});

it('marks invoice paid when fully covered', function (): void {
    $invoice = Invoice::factory()->sent()->for($this->tenant)->for($this->client)->create(['total' => 10000]);

    $this->action->execute($invoice, 10000, 'USD', PaymentProvider::Stripe);

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'status' => InvoiceStatus::Paid->value,
        'amount_paid' => 10000,
    ]);
});

it('does not mark paid on partial payment', function (): void {
    $invoice = Invoice::factory()->sent()->for($this->tenant)->for($this->client)->create(['total' => 10000]);

    $this->action->execute($invoice, 5000, 'USD', PaymentProvider::Stripe);

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'status' => InvoiceStatus::Sent->value,
        'amount_paid' => 5000,
    ]);
});

it('accumulates multiple partial payments', function (): void {
    $invoice = Invoice::factory()->sent()->for($this->tenant)->for($this->client)->create(['total' => 10000]);

    $this->action->execute($invoice, 6000, 'USD', PaymentProvider::Manual, 'pay-1');
    $this->action->execute($invoice->fresh(), 4000, 'USD', PaymentProvider::Manual, 'pay-2');

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'status' => InvoiceStatus::Paid->value,
        'amount_paid' => 10000,
    ]);

    expect($invoice->payments()->count())->toBe(2);
});

it('dispatches InvoicePaid event', function (): void {
    Event::fake([InvoicePaid::class]);

    $invoice = Invoice::factory()->sent()->for($this->tenant)->for($this->client)->create(['total' => 5000]);

    $this->action->execute($invoice, 5000, 'USD', PaymentProvider::Stripe);

    Event::assertDispatched(InvoicePaid::class, function (InvoicePaid $event) use ($invoice): bool {
        return $event->invoice->id === $invoice->id;
    });
});

it('throws on duplicate provider_reference', function (): void {
    $invoice = Invoice::factory()->sent()->for($this->tenant)->for($this->client)->create(['total' => 10000]);

    $this->action->execute($invoice, 10000, 'USD', PaymentProvider::Stripe, 'pi_dup');

    expect(fn () => $this->action->execute($invoice, 10000, 'USD', PaymentProvider::Stripe, 'pi_dup'))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('overpayment still marks the invoice as paid', function (): void {
    $invoice = Invoice::factory()->sent()->for($this->tenant)->for($this->client)->create(['total' => 1000]);

    $this->action->execute($invoice, 1500, 'USD', PaymentProvider::Stripe);

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'status' => InvoiceStatus::Paid->value,
        'amount_paid' => 1500,
    ]);
});
