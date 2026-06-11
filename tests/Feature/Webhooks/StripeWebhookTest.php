<?php

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->webhookSecret = 'whsec_test_secret';
    Config::set('services.stripe.webhook_secret', $this->webhookSecret);

    $this->tenant = Tenant::factory()->create();
    $this->client = Client::factory()->for($this->tenant)->create();
});

/**
 * Build a Stripe-signed webhook request.
 * Replicates Stripe\WebhookSignature::computeSignature.
 */
function stripeWebhookPayload(array $data, string $secret): array
{
    $payload = json_encode($data);
    $timestamp = time();
    $signedPayload = "{$timestamp}.{$payload}";
    $sig = hash_hmac('sha256', $signedPayload, $secret);
    $sigHeader = "t={$timestamp},v1={$sig}";

    return [$payload, $sigHeader];
}

it('returns 400 for an invalid signature', function (): void {
    $this->postJson('/webhooks/stripe', [], ['Stripe-Signature' => 'invalid'])
        ->assertStatus(400);
});

it('handles checkout.session.completed and marks the invoice paid', function (): void {
    $invoice = Invoice::factory()->sent()
        ->for($this->tenant)->for($this->client)
        ->create(['total' => 25000]);

    $event = [
        'id' => 'evt_test_001',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_abc',
                'object' => 'checkout.session',
                'amount_total' => 25000,
                'currency' => 'usd',
                'payment_intent' => 'pi_test_001',
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'public_id' => $invoice->public_id,
                ],
                'mode' => 'payment',
                'status' => 'complete',
            ],
        ],
    ];

    [$payload, $sigHeader] = stripeWebhookPayload($event, $this->webhookSecret);

    $this->call('POST', '/webhooks/stripe', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_Stripe-Signature' => $sigHeader,
    ], $payload)->assertOk();

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'status' => InvoiceStatus::Paid->value,
        'amount_paid' => 25000,
    ]);

    $this->assertDatabaseHas('payments', [
        'invoice_id' => $invoice->id,
        'amount' => 25000,
        'provider_reference' => 'pi_test_001',
    ]);
});

it('is idempotent — duplicate webhook is a no-op', function (): void {
    $invoice = Invoice::factory()->sent()
        ->for($this->tenant)->for($this->client)
        ->create(['total' => 10000]);

    $event = [
        'id' => 'evt_test_002',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_def',
                'object' => 'checkout.session',
                'amount_total' => 10000,
                'currency' => 'usd',
                'payment_intent' => 'pi_test_dup',
                'metadata' => ['invoice_id' => $invoice->id, 'public_id' => $invoice->public_id],
                'mode' => 'payment',
                'status' => 'complete',
            ],
        ],
    ];

    [$payload, $sigHeader] = stripeWebhookPayload($event, $this->webhookSecret);

    // Send twice — both should return 200
    $this->call('POST', '/webhooks/stripe', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_Stripe-Signature' => $sigHeader,
    ], $payload)->assertOk();

    $this->call('POST', '/webhooks/stripe', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_Stripe-Signature' => $sigHeader,
    ], $payload)->assertOk();

    // Only one payment row should exist
    expect($invoice->payments()->count())->toBe(1);
});

it('ignores unknown event types gracefully', function (): void {
    $event = ['id' => 'evt_unknown', 'type' => 'customer.created', 'data' => ['object' => []]];

    [$payload, $sigHeader] = stripeWebhookPayload($event, $this->webhookSecret);

    $this->call('POST', '/webhooks/stripe', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_Stripe-Signature' => $sigHeader,
    ], $payload)->assertOk();
});

it('skips already-paid invoices without error', function (): void {
    $invoice = Invoice::factory()->paid()
        ->for($this->tenant)->for($this->client)
        ->create(['total' => 10000]);

    $event = [
        'id' => 'evt_test_003',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_ghi',
                'object' => 'checkout.session',
                'amount_total' => 10000,
                'currency' => 'usd',
                'payment_intent' => 'pi_test_003',
                'metadata' => ['invoice_id' => $invoice->id, 'public_id' => $invoice->public_id],
                'mode' => 'payment',
                'status' => 'complete',
            ],
        ],
    ];

    [$payload, $sigHeader] = stripeWebhookPayload($event, $this->webhookSecret);

    $this->call('POST', '/webhooks/stripe', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_Stripe-Signature' => $sigHeader,
    ], $payload)->assertOk();
});
