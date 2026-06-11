<?php

use App\Jobs\SendPaymentReminderEmail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Queue::fake();
    $this->tenant = Tenant::factory()->create();
    $this->client = Client::factory()->for($this->tenant)->create();
});

it('dispatches reminder job for invoices due the right number of days ago', function (): void {
    // Due 3 days ago → +3 reminder
    Invoice::factory()->overdue()
        ->for($this->tenant)->for($this->client)
        ->create(['due_date' => today()->subDays(3), 'reminders_sent' => []]);

    $this->artisan('invoices:send-reminders')->assertSuccessful();

    Queue::assertPushed(SendPaymentReminderEmail::class, 1);
});

it('does not re-send a reminder that was already sent', function (): void {
    Invoice::factory()->overdue()
        ->for($this->tenant)->for($this->client)
        ->create(['due_date' => today()->subDays(3), 'reminders_sent' => [3]]);

    $this->artisan('invoices:send-reminders')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('sends multiple interval reminders independently', function (): void {
    // Due 7 days ago → eligible for both +3 and +7 intervals, but +3 already sent
    Invoice::factory()->overdue()
        ->for($this->tenant)->for($this->client)
        ->create(['due_date' => today()->subDays(7), 'reminders_sent' => [3]]);

    $this->artisan('invoices:send-reminders')->assertSuccessful();

    Queue::assertPushed(SendPaymentReminderEmail::class, 1);
});

it('marks the interval as sent in the database', function (): void {
    $invoice = Invoice::factory()->overdue()
        ->for($this->tenant)->for($this->client)
        ->create(['due_date' => today()->subDays(3), 'reminders_sent' => []]);

    $this->artisan('invoices:send-reminders')->assertSuccessful();

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
    ]);

    expect($invoice->fresh()->reminders_sent)->toContain(3);
});

it('skips invoices outside the 90-day window', function (): void {
    Invoice::factory()->overdue()
        ->for($this->tenant)->for($this->client)
        ->create(['due_date' => today()->subDays(95), 'reminders_sent' => []]);

    $this->artisan('invoices:send-reminders')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('skips sent and draft invoices', function (): void {
    Invoice::factory()->sent()
        ->for($this->tenant)->for($this->client)
        ->create(['due_date' => today()->subDays(3), 'reminders_sent' => []]);

    $this->artisan('invoices:send-reminders')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('is idempotent — running twice dispatches once', function (): void {
    Invoice::factory()->overdue()
        ->for($this->tenant)->for($this->client)
        ->create(['due_date' => today()->subDays(3), 'reminders_sent' => []]);

    $this->artisan('invoices:send-reminders');
    $this->artisan('invoices:send-reminders');

    Queue::assertPushed(SendPaymentReminderEmail::class, 1);
});
