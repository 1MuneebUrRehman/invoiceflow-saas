<?php

namespace App\Actions\Invoices;

use App\Models\Invoice;
use Illuminate\Support\Facades\URL;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class CreateStripeCheckoutSession
{
    /**
     * Create a Stripe Checkout Session for the invoice's outstanding balance.
     *
     * Returns the hosted checkout URL to redirect the client to.
     */
    public function execute(Invoice $invoice): string
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => route('invoices.payment.callback', ['publicId' => $invoice->public_id, 'result' => 'success']),
            'cancel_url' => route('invoices.payment.callback', ['publicId' => $invoice->public_id, 'result' => 'cancel']),
            'metadata' => [
                'invoice_id' => (string) $invoice->id,
                'public_id' => $invoice->public_id,
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($invoice->currency),
                    'unit_amount' => $invoice->amountDue(),
                    'product_data' => [
                        'name' => "Invoice {$invoice->number}",
                        'description' => "From {$invoice->tenant->name}",
                    ],
                ],
                'quantity' => 1,
            ]],
        ]);

        return $session->url;
    }
}
