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
        <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5 transition hover:shadow-card-hover">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-field bg-lapis/10 text-lapis">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="2" y="6" width="20" height="12" rx="2" />
                        <circle cx="12" cy="12" r="2.5" />
                        <path d="M6 12h.01M18 12h.01" />
                    </svg>
                </span>
                <p class="text-sm font-medium text-slate">Outstanding</p>
            </div>
            <p data-money class="mt-4 text-3xl font-semibold tracking-tight text-midnight">{{ $this->formattedOutstanding() }}</p>
        </div>

        <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5 transition hover:shadow-card-hover">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-field bg-lapis/10 text-lapis">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                        <path d="M14 3v5h5" />
                        <path d="M9 13h6M9 17h4" />
                    </svg>
                </span>
                <p class="text-sm font-medium text-slate">Invoices</p>
            </div>
            <p data-money class="mt-4 text-3xl font-semibold tracking-tight text-midnight">{{ $this->invoiceCount }}</p>
        </div>

        <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5 transition hover:shadow-card-hover">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-field bg-lapis/10 text-lapis">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                        <circle cx="10" cy="7" r="4" />
                        <path d="M21 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </span>
                <p class="text-sm font-medium text-slate">Clients</p>
            </div>
            <p data-money class="mt-4 text-3xl font-semibold tracking-tight text-midnight">{{ $this->clientCount }}</p>
        </div>
    </div>

    @if ($this->invoiceCount === 0)
        <div class="mt-8 rounded-card border border-dashed border-slate/25 bg-white p-12 text-center shadow-card">
            <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-lapis/10 text-lapis">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                    <path d="M14 3v5h5" />
                    <path d="M12 11v6M9 14h6" />
                </svg>
            </span>
            <h2 class="mt-4 font-display text-xl font-semibold">No invoices yet</h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-slate">
                Your invoicing workspace is ready. Client and invoice management arrive with the full dashboard.
            </p>
        </div>
    @endif
</div>
