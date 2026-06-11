<?php

use App\Actions\Invoices\SendInvoice;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Invoices')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }

    #[Computed]
    public function invoices()
    {
        return Invoice::query()
            ->with('client')
            ->when($this->search, fn ($q) => $q->where(
                fn ($q2) => $q2
                    ->where('number', 'like', "%{$this->search}%")
                    ->orWhereHas('client', fn ($q3) => $q3->where('name', 'like', "%{$this->search}%"))
            ))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(20);
    }

    #[Computed]
    public function statusCounts(): array
    {
        return Invoice::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function sendInvoice(int $id): void
    {
        $invoice = Invoice::with(['client', 'tenant'])->findOrFail($id);
        app(SendInvoice::class)->execute($invoice);
        unset($this->invoices);
        $this->dispatch('notify', message: "Invoice {$invoice->number} sent to {$invoice->client->email}.", type: 'success');
    }

    public function delete(int $id): void
    {
        Invoice::findOrFail($id)->delete();
        unset($this->invoices);
        $this->dispatch('notify', message: 'Invoice deleted.', type: 'success');
    }

    public function downloadPdf(int $id): mixed
    {
        $invoice = Invoice::findOrFail($id);
        abort_unless(filled($invoice->pdf_path), 404);

        return Storage::disk('local')->download($invoice->pdf_path, "{$invoice->number}.pdf");
    }

    public function formatted(Invoice $invoice): string
    {
        return Number::currency($invoice->total / 100, in: $invoice->currency);
    }
};
?>

<div class="relative">
    <div aria-hidden="true" class="pointer-events-none absolute -inset-x-4 top-0 -z-10">
        <div class="absolute -top-24 left-1/2 size-[32rem] -translate-x-1/3 rounded-full bg-lapis/6 blur-3xl"></div>
    </div>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight text-midnight">Invoices</h1>
            <p class="mt-1 text-sm text-slate">Create, send, and track your invoices.</p>
        </div>
        <a
            href="{{ route('invoices.create') }}"
            class="rounded-field bg-lapis px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
        >
            + New invoice
        </a>
    </div>

    {{-- Status tabs --}}
    <div class="mt-6 flex flex-wrap gap-1.5">
        @php
            $tabs = [
                '' => 'All',
                'draft' => 'Draft',
                'sent' => 'Sent',
                'overdue' => 'Overdue',
                'paid' => 'Paid',
                'cancelled' => 'Cancelled',
            ];
        @endphp
        @foreach ($tabs as $value => $label)
            <button
                wire:click="$set('status', '{{ $value }}')"
                @class([
                    'rounded-full px-3.5 py-1.5 text-xs font-medium transition',
                    'bg-midnight text-white shadow-sm' => $status === $value,
                    'bg-white text-slate ring-1 ring-midnight/10 hover:ring-midnight/20 hover:text-midnight' => $status !== $value,
                ])
            >
                {{ $label }}
                @if ($value !== '' && isset($this->statusCounts[$value]))
                    <span class="{{ $status === $value ? 'opacity-70' : 'opacity-50' }}">{{ $this->statusCounts[$value] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="mt-4 rounded-card bg-white shadow-card ring-1 ring-midnight/5 overflow-hidden">
        {{-- Search toolbar --}}
        <div class="flex items-center gap-3 border-b border-midnight/6 px-5 py-3.5">
            <div class="relative flex-1 max-w-sm">
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search invoices…"
                    class="w-full rounded-field border-0 bg-mist py-2 pl-9 pr-4 text-sm text-midnight placeholder-slate ring-1 ring-midnight/10 focus:ring-2 focus:ring-lapis/40 focus:outline-none"
                >
            </div>
            <span class="text-sm text-slate">{{ $this->invoices->total() }} {{ Str::plural('invoice', $this->invoices->total()) }}</span>
        </div>

        @if ($this->invoices->isEmpty())
            <div class="px-5 py-16 text-center">
                <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-lapis/10 text-lapis">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/></svg>
                </span>
                @if ($search || $status)
                    <p class="mt-4 text-sm font-medium text-midnight">No invoices match your filters</p>
                    <button wire:click="$set('search', ''); $set('status', '')" class="mt-3 text-sm font-medium text-lapis hover:text-lapis-deep">Clear filters</button>
                @else
                    <p class="mt-4 font-display text-lg font-semibold text-midnight">No invoices yet</p>
                    <p class="mt-1 text-sm text-slate">Create your first invoice to get started.</p>
                    <a href="{{ route('invoices.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-field bg-lapis px-4 py-2 text-sm font-semibold text-white transition hover:bg-lapis-deep">
                        Create first invoice
                    </a>
                @endif
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-midnight/6">
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate">Invoice</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate">Client</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate">Amount</th>
                        <th class="hidden px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate sm:table-cell">Due</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-midnight/5">
                    @foreach ($this->invoices as $invoice)
                        @php
                            $statusColor = [
                                'draft'     => 'bg-slate/10 text-slate',
                                'sent'      => 'bg-lapis/10 text-lapis',
                                'paid'      => 'bg-verdant/10 text-verdant',
                                'overdue'   => 'bg-garnet/10 text-garnet',
                                'cancelled' => 'bg-slate/10 text-slate',
                            ][$invoice->status->value] ?? 'bg-slate/10 text-slate';
                        @endphp
                        <tr class="group transition hover:bg-mist/60">
                            <td class="px-5 py-3.5">
                                <a href="{{ route('invoices.edit', $invoice) }}" class="font-semibold text-midnight transition group-hover:text-lapis">
                                    {{ $invoice->number }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-slate">{{ $invoice->client?->name ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                                    {{ $invoice->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-medium text-midnight">{{ $this->formatted($invoice) }}</td>
                            <td class="hidden px-5 py-3.5 text-right text-slate sm:table-cell {{ $invoice->status === InvoiceStatus::Overdue ? 'text-garnet font-medium' : '' }}">
                                {{ $invoice->due_date->format('M j, Y') }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1.5 opacity-0 transition group-hover:opacity-100">
                                    @if ($invoice->status === InvoiceStatus::Draft || $invoice->status->isPayable())
                                        <button
                                            wire:click="sendInvoice({{ $invoice->id }})"
                                            wire:confirm="Send {{ $invoice->number }} to {{ $invoice->client?->email }}?"
                                            class="rounded-field px-2.5 py-1.5 text-xs font-medium text-lapis ring-1 ring-lapis/20 transition hover:bg-lapis/8"
                                            title="Send invoice"
                                        >
                                            Send
                                        </button>
                                    @endif
                                    @if (filled($invoice->pdf_path))
                                        <button
                                            wire:click="downloadPdf({{ $invoice->id }})"
                                            class="rounded-field px-2.5 py-1.5 text-xs font-medium text-slate ring-1 ring-midnight/10 transition hover:bg-mist"
                                            title="Download PDF"
                                        >
                                            PDF
                                        </button>
                                    @endif
                                    <a
                                        href="{{ route('invoices.edit', $invoice) }}"
                                        class="rounded-field px-2.5 py-1.5 text-xs font-medium text-slate ring-1 ring-midnight/10 transition hover:bg-mist hover:text-midnight"
                                    >
                                        Edit
                                    </a>
                                    <button
                                        wire:click="delete({{ $invoice->id }})"
                                        wire:confirm="Delete {{ $invoice->number }}? This cannot be undone."
                                        class="rounded-field px-2.5 py-1.5 text-xs font-medium text-garnet ring-1 ring-garnet/20 transition hover:bg-garnet/8"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($this->invoices->hasPages())
                <div class="flex items-center justify-between border-t border-midnight/6 px-5 py-3">
                    <p class="text-xs text-slate">
                        Showing {{ $this->invoices->firstItem() }}–{{ $this->invoices->lastItem() }} of {{ $this->invoices->total() }}
                    </p>
                    <div class="flex items-center gap-1.5">
                        @if ($this->invoices->onFirstPage())
                            <span class="rounded-field px-3 py-1.5 text-xs font-medium text-slate/40 ring-1 ring-midnight/8 cursor-not-allowed">← Prev</span>
                        @else
                            <button wire:click="previousPage" class="rounded-field px-3 py-1.5 text-xs font-medium text-slate ring-1 ring-midnight/10 transition hover:bg-mist hover:text-midnight">← Prev</button>
                        @endif
                        @if ($this->invoices->hasMorePages())
                            <button wire:click="nextPage" class="rounded-field px-3 py-1.5 text-xs font-medium text-slate ring-1 ring-midnight/10 transition hover:bg-mist hover:text-midnight">Next →</button>
                        @else
                            <span class="rounded-field px-3 py-1.5 text-xs font-medium text-slate/40 ring-1 ring-midnight/8 cursor-not-allowed">Next →</span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
