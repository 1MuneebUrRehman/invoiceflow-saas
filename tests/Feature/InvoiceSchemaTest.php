<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Tenant;
use Illuminate\Database\UniqueConstraintViolationException;

test('invoice numbers are unique within a tenant', function () {
    $tenant = Tenant::factory()->create();

    Invoice::factory()->for($tenant)->create(['number' => 'INV-2026-0001']);

    expect(fn () => Invoice::factory()->for($tenant)->create(['number' => 'INV-2026-0001']))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('the same invoice number may exist on different tenants', function () {
    Invoice::factory()->for(Tenant::factory())->create(['number' => 'INV-2026-0001']);

    $invoice = Invoice::factory()->for(Tenant::factory())->create(['number' => 'INV-2026-0001']);

    expect($invoice->exists)->toBeTrue();
});

test('money round-trips as integer minor units', function () {
    $invoice = Invoice::factory()
        ->for(Tenant::factory())
        ->create(['subtotal' => 123_456, 'tax_rate' => 10, 'tax_amount' => 12_346, 'total' => 135_802]);

    $fresh = $invoice->fresh();

    expect($fresh->subtotal)->toBeInt()->toBe(123_456)
        ->and($fresh->tax_amount)->toBeInt()->toBe(12_346)
        ->and($fresh->total)->toBeInt()->toBe(135_802);
});

test('line item amounts stay exact with fractional quantities', function () {
    $item = InvoiceItem::factory()
        ->for(Tenant::factory())
        ->create(['quantity' => 2.5, 'unit_price' => 10_000, 'amount' => 25_000]);

    $fresh = $item->fresh();

    expect($fresh->amount)->toBeInt()->toBe(25_000)
        ->and($fresh->quantity)->toBe('2.50');
});

test('every invoice receives a public ulid on creation', function () {
    $invoice = Invoice::factory()->for(Tenant::factory())->create();

    expect($invoice->public_id)->not->toBeEmpty()
        ->and(strlen($invoice->public_id))->toBe(26);
});
