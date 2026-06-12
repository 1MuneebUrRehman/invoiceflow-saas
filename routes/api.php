<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Middleware\EnsureInvoiceLimit;
use App\Http\Middleware\SetCurrentTenantForApi;
use Illuminate\Support\Facades\Route;

// Token issuance (unauthenticated) — rate-limited to prevent brute-force
Route::middleware('throttle:auth-api')->group(function (): void {
    Route::post('/v1/auth/tokens', [AuthController::class, 'store'])->name('api.v1.tokens.store');
});

// All other v1 routes require a valid Sanctum token
Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:api', SetCurrentTenantForApi::class])
    ->name('api.v1.')
    ->group(function (): void {
        // Token management
        Route::delete('/auth/tokens/current', [AuthController::class, 'destroy'])->name('tokens.destroy');

        // Clients
        Route::apiResource('clients', ClientController::class);

        // Invoices — bound by public_id (ULID), creation is plan-gated
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/invoices', [InvoiceController::class, 'store'])
            ->middleware(EnsureInvoiceLimit::class)
            ->name('invoices.store');
        Route::get('/invoices/{invoice:public_id}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::put('/invoices/{invoice:public_id}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('/invoices/{invoice:public_id}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::post('/invoices/{invoice:public_id}/send', [InvoiceController::class, 'send'])->name('invoices.send');

        // Payments
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/invoices/{invoice:public_id}/payments', [PaymentController::class, 'index'])->name('invoice-payments.index');
        Route::post('/invoices/{invoice:public_id}/payments', [PaymentController::class, 'store'])->name('invoice-payments.store');

        // Billing / subscription plan
        Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
        Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
    });
