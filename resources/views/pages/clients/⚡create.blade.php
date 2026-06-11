<?php

use App\Models\Client;
use App\Models\Tenant;
use App\Tenancy\CurrentTenant;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('New Client')] class extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $company_name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:255')]
    public string $phone = '';

    #[Validate('required|string|size:3')]
    public string $currency = 'USD';

    #[Validate('nullable|string|max:255')]
    public string $address_line1 = '';

    #[Validate('nullable|string|max:255')]
    public string $address_line2 = '';

    #[Validate('nullable|string|max:255')]
    public string $city = '';

    #[Validate('nullable|string|max:255')]
    public string $state = '';

    #[Validate('nullable|string|max:255')]
    public string $postal_code = '';

    #[Validate('nullable|string|size:2')]
    public string $country = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    public function mount(): void
    {
        $this->currency = app(CurrentTenant::class)->get()->default_currency ?? 'USD';
    }

    public function save(): void
    {
        $data = $this->validate();

        Client::create(array_map(fn ($v) => $v === '' ? null : $v, $data));

        session()->flash('success', 'Client created successfully.');
        $this->redirect(route('clients.index'));
    }

    public function getCurrencyOptions(): array
    {
        return Tenant::currencyOptions();
    }
};
?>

<div class="relative">
    <div aria-hidden="true" class="pointer-events-none absolute -inset-x-4 top-0 -z-10">
        <div class="absolute -top-24 right-0 size-[24rem] rounded-full bg-verdant/6 blur-3xl"></div>
    </div>

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('clients.index') }}" class="text-sm font-medium text-slate transition hover:text-midnight">← Clients</a>
        <span class="text-slate/40">/</span>
        <span class="text-sm font-medium text-midnight">New client</span>
    </div>

    <h1 class="font-display text-3xl font-semibold tracking-tight text-midnight">New client</h1>

    <form wire:submit="save" class="mt-8 space-y-6">
        {{-- Contact --}}
        <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5">
            <h2 class="mb-5 font-display text-lg font-semibold text-midnight">Contact</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-midnight">Name <span class="text-garnet">*</span></label>
                    <input wire:model="name" type="text" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 placeholder-slate/60 focus:ring-2 focus:ring-lapis/40 focus:outline-none" placeholder="Jane Smith">
                    @error('name') <p class="mt-1 text-xs text-garnet">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-midnight">Company name</label>
                    <input wire:model="company_name" type="text" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 placeholder-slate/60 focus:ring-2 focus:ring-lapis/40 focus:outline-none" placeholder="Acme Corp">
                    @error('company_name') <p class="mt-1 text-xs text-garnet">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-midnight">Email <span class="text-garnet">*</span></label>
                    <input wire:model="email" type="email" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 placeholder-slate/60 focus:ring-2 focus:ring-lapis/40 focus:outline-none" placeholder="jane@acme.com">
                    @error('email') <p class="mt-1 text-xs text-garnet">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-midnight">Phone</label>
                    <input wire:model="phone" type="tel" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 placeholder-slate/60 focus:ring-2 focus:ring-lapis/40 focus:outline-none" placeholder="+1 555 0100">
                    @error('phone') <p class="mt-1 text-xs text-garnet">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-midnight">Default currency <span class="text-garnet">*</span></label>
                    <select wire:model="currency" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 focus:ring-2 focus:ring-lapis/40 focus:outline-none">
                        @foreach ($this->getCurrencyOptions() as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('currency') <p class="mt-1 text-xs text-garnet">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Billing address --}}
        <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5">
            <h2 class="mb-5 font-display text-lg font-semibold text-midnight">Billing address <span class="ml-2 text-sm font-normal text-slate">(optional)</span></h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-midnight">Address line 1</label>
                    <input wire:model="address_line1" type="text" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 placeholder-slate/60 focus:ring-2 focus:ring-lapis/40 focus:outline-none" placeholder="123 Main St">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-midnight">Address line 2</label>
                    <input wire:model="address_line2" type="text" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 placeholder-slate/60 focus:ring-2 focus:ring-lapis/40 focus:outline-none" placeholder="Suite 400">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-midnight">City</label>
                    <input wire:model="city" type="text" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 placeholder-slate/60 focus:ring-2 focus:ring-lapis/40 focus:outline-none" placeholder="San Francisco">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-midnight">State / province</label>
                    <input wire:model="state" type="text" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 placeholder-slate/60 focus:ring-2 focus:ring-lapis/40 focus:outline-none" placeholder="CA">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-midnight">Postal code</label>
                    <input wire:model="postal_code" type="text" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 placeholder-slate/60 focus:ring-2 focus:ring-lapis/40 focus:outline-none" placeholder="94105">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-midnight">Country code</label>
                    <input wire:model="country" type="text" maxlength="2" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 placeholder-slate/60 focus:ring-2 focus:ring-lapis/40 focus:outline-none" placeholder="US">
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5">
            <h2 class="mb-5 font-display text-lg font-semibold text-midnight">Internal notes <span class="ml-2 text-sm font-normal text-slate">(never shown to client)</span></h2>
            <textarea wire:model="notes" rows="3" class="w-full rounded-field border-0 bg-mist px-3.5 py-2.5 text-sm text-midnight ring-1 ring-midnight/15 placeholder-slate/60 focus:ring-2 focus:ring-lapis/40 focus:outline-none" placeholder="Payment preferences, contact notes…"></textarea>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('clients.index') }}" class="rounded-field px-4 py-2.5 text-sm font-medium text-slate transition hover:bg-white hover:text-midnight">Cancel</a>
            <button type="submit" class="rounded-field bg-lapis px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis">
                <span wire:loading.remove>Save client</span>
                <span wire:loading>Saving…</span>
            </button>
        </div>
    </form>
</div>
