<?php

namespace App\Http\Middleware;

use App\Models\Invoice;
use App\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInvoiceLimit
{
    public function __construct(protected CurrentTenant $currentTenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->currentTenant->get();

        if ($tenant === null || $tenant->isPro()) {
            return $next($request);
        }

        $limit = $tenant->plan->monthlyInvoiceLimit();

        if ($limit === null) {
            return $next($request);
        }

        $used = Invoice::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        if ($used >= $limit) {
            return response()->json([
                'message' => "Free plan allows {$limit} invoices per month. Upgrade to Pro for unlimited invoices.",
                'error' => 'plan_limit_exceeded',
                'used' => $used,
                'limit' => $limit,
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        return $next($request);
    }
}
