<?php

use App\Actions\Invoices\CreateStripeCheckoutSession;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Layout('layouts::public')] #[Title('Invoice')] class extends Component
{
    #[Locked]
    public Invoice $invoice;

    public function mount(string $publicId): void
    {
        $this->invoice = Invoice::query()
            ->withoutGlobalScope(TenantScope::class)
            ->with(['tenant', 'client', 'items'])
            ->where('public_id', $publicId)
            ->firstOrFail();
    }

    public function downloadPdf(): StreamedResponse
    {
        abort_unless(filled($this->invoice->pdf_path), 404);

        return Storage::disk('local')->download($this->invoice->pdf_path, "{$this->invoice->number}.pdf");
    }

    public function payWithStripe(): mixed
    {
        abort_unless($this->invoice->status->isPayable(), 403);
        abort_unless(filled(config('services.stripe.secret')), 501, 'Stripe is not configured.');

        $checkoutUrl = app(CreateStripeCheckoutSession::class)->execute($this->invoice);

        return redirect()->away($checkoutUrl);
    }

    public function formatMoney(int $minorUnits): string
    {
        return Number::currency($minorUnits / 100, in: $this->invoice->currency);
    }
};
?>

<div>
    @php
        $statusClasses = match ($invoice->status) {
            \App\Enums\InvoiceStatus::Sent => 'bg-lapis/10 text-lapis',
            \App\Enums\InvoiceStatus::Paid => 'bg-verdant/10 text-verdant',
            \App\Enums\InvoiceStatus::Overdue => 'bg-garnet/10 text-garnet',
            default => 'bg-slate/10 text-slate',
        };
    @endphp

    <div class="flex items-center justify-between gap-4">
        <span class="flex items-center gap-2.5">
            <x-ui.logo-mark class="size-9" />
            <span class="font-display text-xl font-semibold tracking-tight text-midnight">
                {{ $invoice->tenant->name }}
            </span>
        </span>

        <button
            type="button"
            wire:click="downloadPdf"
            @class([
                'inline-flex items-center gap-2 rounded-field bg-white px-4 py-2 text-sm font-medium text-midnight shadow-sm ring-1 ring-midnight/10 transition hover:bg-mist focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis',
                'hidden' => blank($invoice->pdf_path),
            ])
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <path d="M7 10l5 5 5-5" />
                <path d="M12 15V3" />
            </svg>
            <span wire:loading.remove wire:target="downloadPdf">Download PDF</span>
            <span wire:loading wire:target="downloadPdf">Preparing…</span>
        </button>
    </div>

    <div class="mt-6 overflow-hidden rounded-modal bg-white shadow-pop ring-1 ring-midnight/5">
        <div class="border-b border-midnight/5 p-8 sm:p-10">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-medium tracking-wide text-slate uppercase">Invoice</p>
                    <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight">{{ $invoice->number }}</h1>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold tracking-wide uppercase {{ $statusClasses }}">
                    {{ $invoice->status->label() }}
                </span>
            </div>

            <div class="mt-8 grid gap-6 sm:grid-cols-3">
                <div>
                    <p class="text-xs font-medium tracking-wide text-slate uppercase">Billed to</p>
                    <p class="mt-1.5 font-semibold">{{ $invoice->client->company_name ?? $invoice->client->name }}</p>
                    @if ($invoice->client->company_name)
                        <p class="text-sm text-slate">{{ $invoice->client->name }}</p>
                    @endif
                    <p class="text-sm text-slate">{{ $invoice->client->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium tracking-wide text-slate uppercase">Issue date</p>
                    <p class="mt-1.5 font-semibold">{{ $invoice->issue_date->format('M j, Y') }}</p>
                    <p class="mt-3 text-xs font-medium tracking-wide text-slate uppercase">Due date</p>
                    <p class="mt-1.5 font-semibold">{{ $invoice->due_date->format('M j, Y') }}</p>
                </div>
                <div class="rounded-card bg-mist p-4 sm:text-right">
                    <p class="text-xs font-medium tracking-wide text-slate uppercase">Amount due</p>
                    <p data-money class="mt-1.5 text-2xl font-semibold tracking-tight">
                        {{ $this->formatMoney($invoice->amountDue()) }}
                    </p>
                    @if ($invoice->status === \App\Enums\InvoiceStatus::Paid)
                        <p class="mt-1 text-xs font-medium text-verdant">Paid in full — thank you!</p>
                    @elseif ($invoice->status->isPayable() && filled(config('services.stripe.secret')))
                        <button
                            type="button"
                            wire:click="payWithStripe"
                            wire:loading.attr="disabled"
                            class="mt-3 inline-flex items-center gap-2 rounded-field bg-lapis px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep disabled:opacity-60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
                        >
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
                                <line x1="1" y1="10" x2="23" y2="10" />
                            </svg>
                            <span wire:loading.remove wire:target="payWithStripe">Pay now</span>
                            <span wire:loading wire:target="payWithStripe">Redirecting…</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-8 sm:p-10">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-midnight/10 text-left text-xs font-medium tracking-wide text-slate uppercase">
                        <th class="pb-3 font-medium">Description</th>
                        <th class="pb-3 text-right font-medium">Qty</th>
                        <th class="pb-3 text-right font-medium">Unit price</th>
                        <th class="pb-3 text-right font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr wire:key="item-{{ $item->id }}" class="border-b border-midnight/5">
                            <td class="py-3 pr-4">{{ $item->description }}</td>
                            <td data-money class="py-3 text-right whitespace-nowrap">{{ rtrim(rtrim((string) $item->quantity, '0'), '.') }}</td>
                            <td data-money class="py-3 text-right whitespace-nowrap">{{ $this->formatMoney($item->unit_price) }}</td>
                            <td data-money class="py-3 text-right font-medium whitespace-nowrap">{{ $this->formatMoney($item->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-6 ml-auto max-w-xs space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate">Subtotal</span>
                    <span data-money>{{ $this->formatMoney($invoice->subtotal) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate">Tax ({{ rtrim(rtrim((string) $invoice->tax_rate, '0'), '.') }}%)</span>
                    <span data-money>{{ $this->formatMoney($invoice->tax_amount) }}</span>
                </div>
                <div class="flex justify-between border-t border-midnight/10 pt-2 text-base font-semibold">
                    <span>Total</span>
                    <span data-money>{{ $this->formatMoney($invoice->total) }}</span>
                </div>
                @if ($invoice->amount_paid > 0)
                    <div class="flex justify-between">
                        <span class="text-slate">Paid</span>
                        <span data-money>&minus;{{ $this->formatMoney($invoice->amount_paid) }}</span>
                    </div>
                    <div class="flex justify-between font-semibold text-garnet">
                        <span>Amount due</span>
                        <span data-money>{{ $this->formatMoney($invoice->amountDue()) }}</span>
                    </div>
                @endif
            </div>

            @if ($invoice->notes)
                <div class="mt-8 rounded-card bg-mist p-4 text-sm">
                    <p class="text-xs font-medium tracking-wide text-slate uppercase">Notes</p>
                    <p class="mt-1.5 whitespace-pre-line">{{ $invoice->notes }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
