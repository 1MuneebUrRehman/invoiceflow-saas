<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class BillingController extends Controller
{
    /** Return current plan info for the tenant. */
    public function show(Request $request): JsonResponse
    {
        $tenant = app(CurrentTenant::class)->get();

        return response()->json([
            'plan' => $tenant?->plan->value ?? 'free',
            'monthly_invoice_limit' => $tenant?->plan->monthlyInvoiceLimit(),
        ]);
    }

    /** Redirect to Stripe Customer Billing Portal to manage the Pro subscription. */
    public function portal(Request $request): JsonResponse
    {
        $tenant = app(CurrentTenant::class)->get();

        if (! $tenant?->stripe_id) {
            return response()->json([
                'message' => 'No active billing account. Please upgrade to Pro first.',
            ], 422);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        $session = $stripe->billingPortal->sessions->create([
            'customer' => $tenant->stripe_id,
            'return_url' => route('dashboard'),
        ]);

        return response()->json(['url' => $session->url]);
    }
}
