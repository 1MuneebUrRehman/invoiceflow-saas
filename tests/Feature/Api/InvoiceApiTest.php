<?php

use App\Enums\InvoiceStatus;
use App\Enums\TenantPlan;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;

function invoiceUser(?TenantPlan $plan = null): array
{
    $tenant = Tenant::factory()->create(['plan' => $plan ?? TenantPlan::Free]);
    $user = User::factory()->for($tenant, 'tenant')->create(['role' => UserRole::Owner]);
    $client = Client::factory()->for($tenant)->create();

    return [$user, $tenant, $client];
}

function invoicePayload(Client $client): array
{
    return [
        'client_id' => $client->id,
        'currency' => 'USD',
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
        'items' => [
            ['description' => 'Design work', 'quantity' => 2, 'unit_price' => 50000],
        ],
    ];
}

// ── List / show ───────────────────────────────────────────────────────────────

it('lists only the current tenant\'s invoices', function (): void {
    [$user, $tenant, $client] = invoiceUser();
    Invoice::factory()->count(2)->for($tenant)->for($client)->create();
    Invoice::factory()->count(3)->for(Tenant::factory())->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/invoices')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('shows an invoice with items and payments', function (): void {
    [$user, $tenant, $client] = invoiceUser();
    $invoice = Invoice::factory()->for($tenant)->for($client)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/invoices/{$invoice->public_id}")
        ->assertOk()
        ->assertJsonPath('data.id', $invoice->public_id)
        ->assertJsonStructure(['data' => ['items', 'payments']]);
});

it('cannot see another tenant\'s invoice', function (): void {
    [$user] = invoiceUser();
    $other = Invoice::factory()->for(Tenant::factory())->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/invoices/{$other->public_id}")
        ->assertNotFound();
});

// ── Create ────────────────────────────────────────────────────────────────────

it('creates a draft invoice with correct totals', function (): void {
    [$user, , $client] = invoiceUser(TenantPlan::Pro);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices', invoicePayload($client))
        ->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.subtotal', 100000)
        ->assertJsonPath('data.total', 100000)
        ->assertJsonPath('data.amount_due', 100000)
        ->assertJsonStructure(['data' => ['items']]);
});

it('validates required invoice fields', function (): void {
    [$user] = invoiceUser(TenantPlan::Pro);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['client_id', 'currency', 'issue_date', 'due_date', 'items']);
});

// ── Plan limits ───────────────────────────────────────────────────────────────

it('blocks invoice creation on free plan after 3 this month', function (): void {
    [$user, $tenant, $client] = invoiceUser(TenantPlan::Free);

    Invoice::factory()->count(3)->for($tenant)->for($client)->create(['created_at' => now()]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices', invoicePayload($client))
        ->assertStatus(402)
        ->assertJsonPath('error', 'plan_limit_exceeded');
});

it('allows invoice creation on free plan with fewer than 3 this month', function (): void {
    [$user, $tenant, $client] = invoiceUser(TenantPlan::Free);
    Invoice::factory()->count(2)->for($tenant)->for($client)->create(['created_at' => now()]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices', invoicePayload($client))
        ->assertCreated();
});

it('allows unlimited invoices on pro plan', function (): void {
    [$user, $tenant, $client] = invoiceUser(TenantPlan::Pro);
    Invoice::factory()->count(10)->for($tenant)->for($client)->create(['created_at' => now()]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices', invoicePayload($client))
        ->assertCreated();
});

it('does not count last month\'s invoices toward this month\'s limit', function (): void {
    [$user, $tenant, $client] = invoiceUser(TenantPlan::Free);

    Invoice::factory()->count(3)->for($tenant)->for($client)->create(['created_at' => now()->subMonth()]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices', invoicePayload($client))
        ->assertCreated();
});

// ── Update / Delete ───────────────────────────────────────────────────────────

it('updates invoice notes', function (): void {
    [$user, $tenant, $client] = invoiceUser(TenantPlan::Pro);
    $invoice = Invoice::factory()->for($tenant)->for($client)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/invoices/{$invoice->public_id}", ['notes' => 'Pay fast'])
        ->assertOk()
        ->assertJsonPath('data.notes', 'Pay fast');
});

it('soft-deletes an invoice', function (): void {
    [$user, $tenant, $client] = invoiceUser(TenantPlan::Pro);
    $invoice = Invoice::factory()->for($tenant)->for($client)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/invoices/{$invoice->public_id}")
        ->assertNoContent();

    $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
});

// ── Filtering ─────────────────────────────────────────────────────────────────

it('filters invoices by status', function (): void {
    [$user, $tenant, $client] = invoiceUser(TenantPlan::Pro);
    Invoice::factory()->sent()->for($tenant)->for($client)->create();
    Invoice::factory()->paid()->for($tenant)->for($client)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/invoices?status=sent')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', InvoiceStatus::Sent->value);
});
