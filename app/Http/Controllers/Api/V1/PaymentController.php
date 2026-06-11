<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Invoices\MarkInvoicePaid;
use App\Enums\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    /** List all payments, or payments for a specific invoice. */
    public function index(Request $request, ?Invoice $invoice = null): AnonymousResourceCollection
    {
        $query = $invoice
            ? $invoice->payments()->latest('paid_at')
            : Payment::latest('paid_at');

        return PaymentResource::collection($query->paginate(25));
    }

    /** Record a manual payment against an invoice. */
    public function store(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless($invoice->status->isPayable(), 422, 'Invoice is not in a payable state.');

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'provider_reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
        ]);

        app(MarkInvoicePaid::class)->execute(
            invoice: $invoice,
            amount: $validated['amount'],
            currency: $validated['currency'],
            provider: PaymentProvider::Manual,
            providerReference: $validated['provider_reference'] ?? null,
            paidAt: isset($validated['paid_at']) ? new \DateTimeImmutable($validated['paid_at']) : null,
        );

        $payment = $invoice->payments()->latest()->first();

        return (new PaymentResource($payment))->response()->setStatusCode(201);
    }
}
