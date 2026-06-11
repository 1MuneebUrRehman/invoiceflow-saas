<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\CurrentTenant;

/**
 * Create a tenant with one of every domain model.
 *
 * @return array{tenant: Tenant, user: User, client: Client, invoice: Invoice, item: InvoiceItem, payment: Payment}
 */
function createTenantGraph(): array
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->owner()->for($tenant)->create();
    $client = Client::factory()->for($tenant)->create();
    $invoice = Invoice::factory()->for($tenant)->for($client)->create();
    $item = InvoiceItem::factory()->for($tenant)->for($invoice)->create();
    $payment = Payment::factory()->for($tenant)->for($invoice)->create();

    return compact('tenant', 'user', 'client', 'invoice', 'item', 'payment');
}

test('a tenant only ever sees its own records, for every domain model', function () {
    $tenantA = createTenantGraph();
    $tenantB = createTenantGraph();

    app(CurrentTenant::class)->set($tenantA['tenant']);

    expect(Client::all()->pluck('id')->all())->toBe([$tenantA['client']->id])
        ->and(Invoice::all()->pluck('id')->all())->toBe([$tenantA['invoice']->id])
        ->and(InvoiceItem::all()->pluck('id')->all())->toBe([$tenantA['item']->id])
        ->and(Payment::all()->pluck('id')->all())->toBe([$tenantA['payment']->id]);
});

test('another tenant record cannot be fetched by id', function () {
    $tenantA = createTenantGraph();
    $tenantB = createTenantGraph();

    app(CurrentTenant::class)->set($tenantA['tenant']);

    expect(Client::find($tenantB['client']->id))->toBeNull()
        ->and(Invoice::find($tenantB['invoice']->id))->toBeNull()
        ->and(InvoiceItem::find($tenantB['item']->id))->toBeNull()
        ->and(Payment::find($tenantB['payment']->id))->toBeNull();
});

test('tenant_id cannot be spoofed when creating records inside a tenant context', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    app(CurrentTenant::class)->set($tenantA);

    $client = new Client(Client::factory()->raw(['tenant_id' => null]));
    $client->tenant_id = $tenantB->id;
    $client->save();

    expect($client->tenant_id)->toBe($tenantA->id);
});

test('the middleware sets the tenant context from the authenticated user', function () {
    $tenantA = createTenantGraph();

    $this->actingAs($tenantA['user'])
        ->get(route('dashboard'))
        ->assertOk();

    expect(app(CurrentTenant::class)->id())->toBe($tenantA['tenant']->id);
});

test('queries run unscoped when no tenant context is set', function () {
    // Deliberate: console commands and queue workers without a tenant context
    // operate across tenants. See docs/decisions.md.
    createTenantGraph();
    createTenantGraph();

    expect(app(CurrentTenant::class)->isSet())->toBeFalse()
        ->and(Client::count())->toBe(2)
        ->and(Invoice::count())->toBe(2);
});
