<?php

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->client = Client::factory()->for($this->tenant)->create();
});

it('marks sent invoices past due date as overdue', function (): void {
    $overdue = Invoice::factory()->sent()
        ->for($this->tenant)->for($this->client)
        ->create(['due_date' => now()->subDay()]);

    $this->artisan('invoices:mark-overdue')->assertSuccessful();

    $this->assertDatabaseHas('invoices', [
        'id' => $overdue->id,
        'status' => InvoiceStatus::Overdue->value,
    ]);
});

it('leaves invoices due today as sent', function (): void {
    $today = Invoice::factory()->sent()
        ->for($this->tenant)->for($this->client)
        ->create(['due_date' => today()]);

    $this->artisan('invoices:mark-overdue')->assertSuccessful();

    $this->assertDatabaseHas('invoices', [
        'id' => $today->id,
        'status' => InvoiceStatus::Sent->value,
    ]);
});

it('does not change paid or cancelled invoices', function (): void {
    $paid = Invoice::factory()->paid()->for($this->tenant)->for($this->client)->create(['due_date' => now()->subDays(5)]);
    $cancelled = Invoice::factory()->cancelled()->for($this->tenant)->for($this->client)->create(['due_date' => now()->subDays(5)]);

    $this->artisan('invoices:mark-overdue')->assertSuccessful();

    $this->assertDatabaseHas('invoices', ['id' => $paid->id, 'status' => InvoiceStatus::Paid->value]);
    $this->assertDatabaseHas('invoices', ['id' => $cancelled->id, 'status' => InvoiceStatus::Cancelled->value]);
});

it('marks overdue invoices across all tenants', function (): void {
    $otherTenant = Tenant::factory()->create();
    $otherClient = Client::factory()->for($otherTenant)->create();

    $invoice1 = Invoice::factory()->sent()->for($this->tenant)->for($this->client)->create(['due_date' => now()->subDay()]);
    $invoice2 = Invoice::factory()->sent()->for($otherTenant)->for($otherClient)->create(['due_date' => now()->subDay()]);

    $this->artisan('invoices:mark-overdue')->assertSuccessful();

    foreach ([$invoice1, $invoice2] as $inv) {
        $this->assertDatabaseHas('invoices', [
            'id' => $inv->id,
            'status' => InvoiceStatus::Overdue->value,
        ]);
    }
});
