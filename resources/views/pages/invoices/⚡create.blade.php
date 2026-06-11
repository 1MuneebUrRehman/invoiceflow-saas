<?php

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Tenancy\CurrentTenant;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Invoice')] class extends Component
{
    public ?int $client_id = null;
    public string $currency = 'USD';
    public string $issue_date = '';
    public string $due_date = '';
    public string $tax_rate = '0';
    public string $notes = '';

    public function mount(): void
    {
        $tenant = app(CurrentTenant::class)->get();
        $this->currency = $tenant->default_currency ?? 'USD';
        $this->issue_date = today()->toDateString();
        $this->due_date = today()->addDays(14)->toDateString();
    }

    #[Computed]
    public function clients()
    {
        return Client::orderBy('name')->get(['id', 'name', 'currency']);
    }

    public function updatedClientId(?string $value): void
    {
        if ($value) {
            $currency = Client::find($value)?->currency;
            if ($currency) {
                $this->currency = $currency;
            }
        }
    }

    public function getCurrencyOptions(): array
    {
        return Tenant::currencyOptions();
    }

    /** Called from Alpine with the line items array. */
    public function saveWithItems(array $rawItems): void
    {
        $this->validate([
            'client_id'  => 'required|integer|exists:clients,id',
            'currency'   => 'required|string|size:3',
            'issue_date' => 'required|date',
            'due_date'   => 'required|date|after_or_equal:issue_date',
            'tax_rate'   => 'required|numeric|min:0|max:100',
        ]);

        if (empty($rawItems)) {
            $this->addError('items', 'Add at least one line item.');
            return;
        }

        foreach ($rawItems as $item) {
            if (empty(trim((string) ($item['description'] ?? '')))) {
                $this->addError('items', 'Each line item must have a description.');
                return;
            }
        }

        $count = Invoice::withTrashed()->count() + 1;
        $number = sprintf('INV-%d-%04d', now()->year, $count);

        $invoice = Invoice::create([
            'client_id'   => $this->client_id,
            'number'      => $number,
            'status'      => InvoiceStatus::Draft,
            'currency'    => $this->currency,
            'tax_rate'    => $this->tax_rate,
            'issue_date'  => $this->issue_date,
            'due_date'    => $this->due_date,
            'notes'       => $this->notes ?: null,
            'subtotal'    => 0,
            'tax_amount'  => 0,
            'total'       => 0,
            'amount_paid' => 0,
        ]);

        foreach ($rawItems as $pos => $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity'    => (float) ($item['quantity'] ?? 1),
                'unit_price'  => (int) round((float) ($item['unit_price'] ?? 0) * 100),
                'position'    => $pos,
            ]);
        }

        $invoice->recalculateTotals();

        session()->flash('success', "Invoice {$number} created.");
        $this->redirect(route('invoices.index'));
    }
};
?>

<div class="relative">
    <div aria-hidden="true" class="pointer-events-none absolute -inset-x-4 top-0 -z-10">
        <div class="absolute -top-24 left-1/3 size-[28rem] rounded-full bg-lapis/6 blur-3xl"></div>
    </div>

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-slate transition hover:text-midnight">← Invoices</a>
        <span class="text-slate/40">/</span>
        <span class="text-sm font-medium text-midnight">New invoice</span>
    </div>

    <h1 class="font-display text-3xl font-semibold tracking-tight text-midnight">New invoice</h1>

    <div
        x-data="{
            items: [{ description: '', quantity: 1, unit_price: '' }],
            currency: $wire.entangle('currency'),
            taxRate: $wire.entangle('tax_rate'),
            saving: false,

            get subtotal() {
                return this.items.reduce((sum, item) => {
                    return sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
                }, 0);
            },
            get taxAmount() {
                return this.subtotal * (parseFloat(this.taxRate) || 0) / 100;
            },
            get total() {
                return this.subtotal + this.taxAmount;
            },
            fmt(n) {
                try {
                    return new Intl.NumberFormat('en-US', { style: 'currency', currency: this.currency || 'USD', minimumFractionDigits: 2 }).format(n);
                } catch {
                    return n.toFixed(2);
                }
            },
            addItem() {
                this.items.push({ description: '', quantity: 1, unit_price: '' });
                this.$nextTick(() => {
                    const inputs = this.$el.querySelectorAll('.item-desc');
                    inputs[inputs.length - 1]?.focus();
                });
            },
            removeItem(i) {
                if (this.items.length > 1) this.items.splice(i, 1);
            },
            async submit() {
                this.saving = true;
                try {
                    await $wire.saveWithItems(JSON.parse(JSON.stringify(this.items)));
                } finally {
                    this.saving = false;
                }
            }
        }"
        class="mt-8 space-y-6"
    >
        {{-- Details --}}
        <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5">
            <h2 class="mb-5 font-display text-lg font-semibold text-midnight">Details</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-midnight">Client <span class="text-garnet">*</span></label>
                    <select wire:model.live="client_id" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 focus:ring-2 focus:ring-lapis/40 focus:outline-none">
                        <option value="">Select a client…</option>
                        @foreach ($this->clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="mt-1 text-xs text-garnet">{{ $message }}</p> @enderror
                    @if ($this->clients->isEmpty())
                        <p class="mt-1 text-xs text-slate">No clients yet. <a href="{{ route('clients.create') }}" class="font-medium text-lapis hover:underline">Add one first.</a></p>
                    @endif
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-midnight">Currency <span class="text-garnet">*</span></label>
                    <select wire:model="currency" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 focus:ring-2 focus:ring-lapis/40 focus:outline-none">
                        @foreach ($this->getCurrencyOptions() as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('currency') <p class="mt-1 text-xs text-garnet">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-midnight">Issue date <span class="text-garnet">*</span></label>
                    <input wire:model="issue_date" type="date" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 focus:ring-2 focus:ring-lapis/40 focus:outline-none">
                    @error('issue_date') <p class="mt-1 text-xs text-garnet">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-midnight">Due date <span class="text-garnet">*</span></label>
                    <input wire:model="due_date" type="date" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 focus:ring-2 focus:ring-lapis/40 focus:outline-none">
                    @error('due_date') <p class="mt-1 text-xs text-garnet">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Line items --}}
        <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="font-display text-lg font-semibold text-midnight">Line items</h2>
                <p class="text-xs text-slate">Amounts in <span x-text="currency"></span></p>
            </div>

            @error('items') <p class="mb-3 rounded-field bg-garnet/8 px-3 py-2 text-xs text-garnet">{{ $message }}</p> @enderror

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-midnight/8">
                            <th class="pb-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate">Description</th>
                            <th class="w-24 pb-2.5 text-right text-xs font-semibold uppercase tracking-wide text-slate">Qty</th>
                            <th class="w-36 pb-2.5 text-right text-xs font-semibold uppercase tracking-wide text-slate">Unit price</th>
                            <th class="w-36 pb-2.5 text-right text-xs font-semibold uppercase tracking-wide text-slate">Amount</th>
                            <th class="w-10 pb-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-midnight/5">
                        <template x-for="(item, i) in items" :key="i">
                            <tr>
                                <td class="py-2 pr-3">
                                    <input
                                        x-model="item.description"
                                        type="text"
                                        placeholder="Description of service or product"
                                        class="item-desc w-full rounded-field border-0 bg-mist px-3 py-2 text-sm text-midnight ring-1 ring-midnight/10 placeholder-slate/50 focus:ring-2 focus:ring-lapis/40 focus:outline-none"
                                    >
                                </td>
                                <td class="py-2 pr-3">
                                    <input
                                        x-model="item.quantity"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        class="w-full rounded-field border-0 bg-mist px-3 py-2 text-right text-sm text-midnight ring-1 ring-midnight/10 focus:ring-2 focus:ring-lapis/40 focus:outline-none"
                                    >
                                </td>
                                <td class="py-2 pr-3">
                                    <input
                                        x-model="item.unit_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        class="w-full rounded-field border-0 bg-mist px-3 py-2 text-right text-sm text-midnight ring-1 ring-midnight/10 placeholder-slate/50 focus:ring-2 focus:ring-lapis/40 focus:outline-none"
                                    >
                                </td>
                                <td class="py-2 pr-3 text-right font-medium text-midnight">
                                    <span x-text="fmt((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0))"></span>
                                </td>
                                <td class="py-2 text-right">
                                    <button
                                        type="button"
                                        @click="removeItem(i)"
                                        x-show="items.length > 1"
                                        class="flex size-7 items-center justify-center rounded-full text-slate/50 transition hover:bg-garnet/10 hover:text-garnet"
                                        title="Remove line"
                                    >
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <button
                type="button"
                @click="addItem()"
                class="mt-4 flex items-center gap-2 rounded-field px-3.5 py-2 text-sm font-medium text-lapis ring-1 ring-lapis/20 transition hover:bg-lapis/8 focus-visible:outline-2 focus-visible:outline-lapis"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add line item
            </button>
        </div>

        {{-- Summary & notes --}}
        <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-midnight">Tax rate</label>
                        <div class="flex items-center gap-0">
                            <input wire:model="tax_rate" type="number" min="0" max="100" step="0.1" class="w-24 rounded-l-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 focus:ring-2 focus:ring-lapis/40 focus:outline-none">
                            <span class="rounded-r-field bg-mist px-3.5 py-2.5 text-sm font-medium text-slate ring-1 ring-l-0 ring-midnight/15">%</span>
                        </div>
                        @error('tax_rate') <p class="mt-1 text-xs text-garnet">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-midnight">Notes <span class="font-normal text-slate">(shown to client)</span></label>
                        <textarea wire:model="notes" rows="3" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 placeholder-slate/60 focus:ring-2 focus:ring-lapis/40 focus:outline-none" placeholder="Payment terms, thank-you note…"></textarea>
                    </div>
                </div>

                <div class="flex flex-col justify-end">
                    <div class="rounded-card bg-mist p-5 space-y-3">
                        <div class="flex items-center justify-between text-sm text-slate">
                            <span>Subtotal</span>
                            <span x-text="fmt(subtotal)" class="font-medium text-midnight"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-slate">
                            <span>Tax (<span x-text="parseFloat(taxRate) || 0"></span>%)</span>
                            <span x-text="fmt(taxAmount)" class="font-medium text-midnight"></span>
                        </div>
                        <div class="flex items-center justify-between border-t border-midnight/10 pt-3">
                            <span class="font-semibold text-midnight">Total</span>
                            <span x-text="fmt(total)" class="font-display text-xl font-semibold text-midnight"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('invoices.index') }}" class="rounded-field px-4 py-2.5 text-sm font-medium text-slate transition hover:bg-white hover:text-midnight">Cancel</a>
            <button
                type="button"
                @click="submit()"
                :disabled="saving"
                class="rounded-field bg-lapis px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep disabled:opacity-60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
            >
                <span x-text="saving ? 'Saving…' : 'Save invoice'"></span>
            </button>
        </div>
    </div>
</div>
