<?php

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    #[Computed]
    public function clientCount(): int
    {
        return Client::count();
    }

    #[Computed]
    public function invoiceCount(): int
    {
        return Invoice::count();
    }

    #[Computed]
    public function outstandingTotal(): int
    {
        return (int) Invoice::query()
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])
            ->sum(DB::raw('total - amount_paid'));
    }

    public function formattedOutstanding(): string
    {
        $currency = auth()->user()?->tenant?->default_currency ?? 'USD';

        return Number::currency($this->outstandingTotal / 100, in: $currency);
    }
};
?>

<div>
    <h1 class="font-display text-3xl font-semibold tracking-tight">
        Welcome back, {{ Str::before(auth()->user()->name, ' ') }}
    </h1>
    <p class="mt-1 text-sm text-slate">Here's where your business stands today.</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-card bg-white p-6 shadow-card">
            <p class="text-sm font-medium text-slate">Outstanding</p>
            <p data-money class="mt-2 text-2xl font-semibold text-midnight">{{ $this->formattedOutstanding() }}</p>
        </div>

        <div class="rounded-card bg-white p-6 shadow-card">
            <p class="text-sm font-medium text-slate">Invoices</p>
            <p data-money class="mt-2 text-2xl font-semibold text-midnight">{{ $this->invoiceCount }}</p>
        </div>

        <div class="rounded-card bg-white p-6 shadow-card">
            <p class="text-sm font-medium text-slate">Clients</p>
            <p data-money class="mt-2 text-2xl font-semibold text-midnight">{{ $this->clientCount }}</p>
        </div>
    </div>

    @if ($this->invoiceCount === 0)
        <div class="mt-8 rounded-card border border-dashed border-slate/25 bg-white p-10 text-center shadow-card">
            <h2 class="font-display text-xl font-semibold">No invoices yet</h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-slate">
                Your invoicing workspace is ready. Client and invoice management arrive with the full dashboard.
            </p>
        </div>
    @endif
</div>
