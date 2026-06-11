<?php

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Collection;
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

    #[Computed]
    public function overdueCount(): int
    {
        return Invoice::query()->where('status', InvoiceStatus::Overdue)->count();
    }

    #[Computed]
    public function recentInvoices(): Collection
    {
        return Invoice::query()
            ->with('client')
            ->latest()
            ->limit(5)
            ->get();
    }

    public function formattedOutstanding(): string
    {
        $currency = auth()->user()?->tenant?->default_currency ?? 'USD';

        return Number::currency($this->outstandingTotal / 100, in: $currency);
    }

    public function formattedAmount(Invoice $invoice): string
    {
        return Number::currency($invoice->total / 100, in: $invoice->currency);
    }
};
?>

<div class="relative">

    {{-- Subtle ambient blobs, identical to welcome page --}}
    <div aria-hidden="true" class="pointer-events-none absolute -inset-x-4 top-0 -z-10 sm:-inset-x-6">
        <div class="absolute -top-32 left-1/2 size-[36rem] -translate-x-1/3 rounded-full bg-lapis/8 blur-3xl"></div>
        <div class="absolute -top-10 right-0 size-[20rem] rounded-full bg-verdant/6 blur-3xl"></div>
    </div>

    {{-- Page header --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight text-midnight">
                Welcome back, {{ Str::before(auth()->user()->name, ' ') }}
            </h1>
            <p class="mt-1 text-sm text-slate">Here's where your business stands today.</p>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('clients.index') }}"
                class="rounded-field bg-white px-4 py-2 text-sm font-medium text-midnight shadow-card ring-1 ring-midnight/10 transition hover:shadow-card-hover hover:ring-midnight/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
            >
                Manage clients
            </a>
            <a
                href="{{ route('invoices.create') }}"
                class="rounded-field bg-lapis px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
            >
                + New invoice
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        {{-- Outstanding --}}
        <div class="group rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5 transition hover:shadow-card-hover">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate">Outstanding</p>
                <span class="flex size-9 items-center justify-center rounded-field bg-lapis/10 text-lapis transition group-hover:bg-lapis/15">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="2" y="6" width="20" height="12" rx="2" />
                        <circle cx="12" cy="12" r="2.5" />
                        <path d="M6 12h.01M18 12h.01" />
                    </svg>
                </span>
            </div>
            <p data-money class="mt-3 text-2xl font-semibold tracking-tight text-midnight">{{ $this->formattedOutstanding() }}</p>
            @if ($this->overdueCount > 0)
                <p class="mt-1 flex items-center gap-1 text-xs font-medium text-garnet">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    {{ $this->overdueCount }} {{ Str::plural('invoice', $this->overdueCount) }} overdue
                </p>
            @else
                <p class="mt-1 flex items-center gap-1 text-xs font-medium text-verdant">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    All current
                </p>
            @endif
        </div>

        {{-- Invoices --}}
        <div class="group rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5 transition hover:shadow-card-hover">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate">Invoices</p>
                <span class="flex size-9 items-center justify-center rounded-field bg-lapis/10 text-lapis transition group-hover:bg-lapis/15">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                        <path d="M14 3v5h5" />
                        <path d="M9 13h6M9 17h4" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-semibold tracking-tight text-midnight">{{ $this->invoiceCount }}</p>
            <p class="mt-1 text-xs text-slate">Total created</p>
        </div>

        {{-- Clients --}}
        <div class="group rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5 transition hover:shadow-card-hover">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate">Clients</p>
                <span class="flex size-9 items-center justify-center rounded-field bg-lapis/10 text-lapis transition group-hover:bg-lapis/15">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                        <circle cx="10" cy="7" r="4" />
                        <path d="M21 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-semibold tracking-tight text-midnight">{{ $this->clientCount }}</p>
            <p class="mt-1 text-xs text-slate">Active clients</p>
        </div>
    </div>

    {{-- Recent invoices --}}
    @if ($this->invoiceCount > 0)
        <div class="mt-8">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-display text-lg font-semibold text-midnight">Recent invoices</h2>
                <a
                    href="{{ route('invoices.index') }}"
                    class="text-sm font-medium text-lapis transition hover:text-lapis-deep"
                >
                    View all →
                </a>
            </div>

            <div class="rounded-card bg-white shadow-card ring-1 ring-midnight/5 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-midnight/6">
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate">Invoice</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate">Client</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate">Amount</th>
                            <th class="hidden px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate sm:table-cell">Due</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-midnight/5">
                        @foreach ($this->recentInvoices as $invoice)
                            <tr class="group transition hover:bg-mist/60">
                                <td class="px-5 py-3.5">
                                    <a
                                        href="{{ route('invoices.edit', $invoice) }}"
                                        class="font-semibold text-midnight group-hover:text-lapis transition"
                                    >
                                        {{ $invoice->number }}
                                    </a>
                                </td>
                                <td class="px-5 py-3.5 text-slate">{{ $invoice->client?->name ?? '—' }}</td>
                                <td class="px-5 py-3.5">
                                    @php
                                        $colorMap = [
                                            'draft'     => 'bg-slate/10 text-slate',
                                            'sent'      => 'bg-lapis/10 text-lapis',
                                            'paid'      => 'bg-verdant/10 text-verdant',
                                            'overdue'   => 'bg-garnet/10 text-garnet',
                                            'cancelled' => 'bg-slate/10 text-slate',
                                        ];
                                        $cls = $colorMap[$invoice->status->value] ?? 'bg-slate/10 text-slate';
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cls }}">
                                        {{ $invoice->status->label() }}
                                    </span>
                                </td>
                                <td data-money class="px-5 py-3.5 text-right font-medium text-midnight">
                                    {{ $this->formattedAmount($invoice) }}
                                </td>
                                <td class="hidden px-5 py-3.5 text-right text-slate sm:table-cell">
                                    {{ $invoice->due_date->format('M j, Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- Empty state --}}
        <div class="mt-8 rounded-card border border-dashed border-slate/25 bg-white p-14 text-center shadow-card">
            <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-lapis/10 text-lapis">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                    <path d="M14 3v5h5" />
                    <path d="M12 11v6M9 14h6" />
                </svg>
            </span>
            <h2 class="mt-4 font-display text-xl font-semibold text-midnight">No invoices yet</h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-slate">
                Add your first client, then create and send an invoice in minutes.
            </p>
            <a
                href="{{ route('invoices.create') }}"
                class="mt-6 inline-flex items-center gap-2 rounded-field bg-lapis px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
            >
                Create your first invoice
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M13 6l6 6-6 6" />
                </svg>
            </a>
        </div>
    @endif

</div>
