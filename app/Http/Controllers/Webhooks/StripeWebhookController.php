<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Invoices\MarkInvoicePaid;
use App\Enums\PaymentProvider;
use App\Models\Invoice;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException) {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $this->handleCheckoutCompleted($event->data->object);
        }

        return response('', 200);
    }

    private function handleCheckoutCompleted(Session $session): void
    {
        $invoiceId = $session->metadata->invoice_id ?? null;

        if (! $invoiceId) {
            return;
        }

        /** @var Invoice|null $invoice */
        $invoice = Invoice::find($invoiceId);

        if (! $invoice || ! $invoice->status->isPayable()) {
            return;
        }

        try {
            app(MarkInvoicePaid::class)->execute(
                invoice: $invoice,
                amount: (int) $session->amount_total,
                currency: strtoupper($session->currency),
                provider: PaymentProvider::Stripe,
                providerReference: $session->payment_intent,
                providerPayload: $session->toArray(),
                paidAt: now(),
            );
        } catch (UniqueConstraintViolationException) {
            // Duplicate webhook delivery — already processed, treat as success.
        }
    }
}
