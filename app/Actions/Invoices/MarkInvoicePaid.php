<?php

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentProvider;
use App\Events\InvoicePaid;
use App\Models\Invoice;
use App\Models\Payment;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class MarkInvoicePaid
{
    /**
     * Record a payment against an invoice, update its balance, and fire InvoicePaid.
     *
     * Idempotency is enforced by the unique index on payments.provider_reference —
     * duplicate webhook deliveries will throw a UniqueConstraintViolationException
     * which the caller (webhook controller) should treat as a 200 no-op.
     *
     * Safe to call from console/queue context (no CurrentTenant required).
     */
    public function execute(
        Invoice $invoice,
        int $amount,
        string $currency,
        PaymentProvider $provider,
        ?string $providerReference = null,
        ?array $providerPayload = null,
        ?DateTimeInterface $paidAt = null,
    ): void {
        $paidAt ??= now();

        DB::transaction(function () use ($invoice, $amount, $currency, $provider, $providerReference, $providerPayload, $paidAt): void {
            // forceFill bypasses mass-assignment for tenant_id, which is not in
            // Payment's fillable list because it is normally stamped by the trait.
            (new Payment)->forceFill([
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'currency' => $currency,
                'provider' => $provider,
                'provider_reference' => $providerReference,
                'provider_payload' => $providerPayload,
                'paid_at' => $paidAt,
            ])->save();

            $newAmountPaid = $invoice->amount_paid + $amount;
            $fullyPaid = $newAmountPaid >= $invoice->total;

            $invoice->forceFill([
                'amount_paid' => $newAmountPaid,
                'status' => $fullyPaid ? InvoiceStatus::Paid : $invoice->status,
                'paid_at' => $fullyPaid ? $paidAt : $invoice->paid_at,
            ])->saveQuietly();
        });

        InvoicePaid::dispatch($invoice->fresh());
    }
}
