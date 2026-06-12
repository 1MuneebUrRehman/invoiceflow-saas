<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Settings')] class extends Component
{
    use WithFileUploads;

    public string $tab = 'business';

    // Business
    public string $business_name = '';
    public string $default_currency = 'USD';
    public $logo = null;

    // Profile
    public string $profile_name = '';
    public string $profile_email = '';

    // Password
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        $this->business_name = $tenant->name;
        $this->default_currency = $tenant->default_currency;
        $this->profile_name = $user->name;
        $this->profile_email = $user->email;
    }

    public function saveBusiness(): void
    {
        $this->validate([
            'business_name'    => 'required|string|max:255',
            'default_currency' => 'required|string|size:3',
            'logo'             => 'nullable|image|max:2048|mimes:jpg,jpeg,png,gif,webp,svg',
        ]);

        $tenant = auth()->user()->tenant;

        if ($this->logo) {
            if ($tenant->logo_path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }

            $path = $this->logo->store('tenant-logos', 'public');
            $tenant->logo_path = $path;
        }

        $tenant->name = $this->business_name;
        $tenant->default_currency = $this->default_currency;
        $tenant->save();

        $this->logo = null;
        $this->dispatch('notify', message: 'Business settings saved.', type: 'success');
    }

    public function removeLogo(): void
    {
        $tenant = auth()->user()->tenant;

        if ($tenant->logo_path) {
            Storage::disk('public')->delete($tenant->logo_path);
            $tenant->update(['logo_path' => null]);
        }

        $this->dispatch('notify', message: 'Logo removed.', type: 'success');
    }

    public function saveProfile(): void
    {
        $user = auth()->user();

        $this->validate([
            'profile_name'  => 'required|string|max:255',
            'profile_email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $emailChanged = $this->profile_email !== $user->email;

        $user->name = $this->profile_name;
        $user->email = $this->profile_email;

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
            $this->dispatch('notify', message: 'Profile saved. Check your inbox to verify your new email.', type: 'info');
        } else {
            $this->dispatch('notify', message: 'Profile saved.', type: 'success');
        }
    }

    public function savePassword(): void
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password'     => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user = auth()->user();

        if (! Hash::check($this->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->update(['password' => $this->new_password]);

        $this->reset('current_password', 'new_password', 'new_password_confirmation');
        $this->dispatch('notify', message: 'Password updated.', type: 'success');
    }

    public function getCurrencyOptions(): array
    {
        return Tenant::currencyOptions();
    }
};
?>

<div
    x-data="{ tab: @entangle('tab') }"
    class="relative"
>
    <div aria-hidden="true" class="pointer-events-none absolute -inset-x-4 top-0 -z-10">
        <div class="absolute -top-24 left-1/2 size-[32rem] -translate-x-1/2 rounded-full bg-lapis/6 blur-3xl"></div>
    </div>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight text-midnight">Settings</h1>
            <p class="mt-1 text-sm text-slate">Manage your business, profile, and security preferences.</p>
        </div>
    </div>

    {{-- Tab nav --}}
    <div class="mt-6 flex gap-1 border-b border-midnight/8 dark:border-white/8">
        @foreach ([
            ['id' => 'business', 'label' => 'Business',  'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
            ['id' => 'account',  'label' => 'Account',   'icon' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
            ['id' => 'security', 'label' => 'Security',  'icon' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>'],
        ] as $t)
            <button
                x-on:click="tab = '{{ $t['id'] }}'"
                :class="tab === '{{ $t['id'] }}' ? 'border-lapis text-lapis' : 'border-transparent text-slate hover:text-midnight hover:border-midnight/20 dark:hover:text-[#c8d8ed]'"
                class="-mb-px flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $t['icon'] !!}</svg>
                {{ $t['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Business tab --}}
    <div x-show="tab === 'business'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <form wire:submit="saveBusiness" class="mt-6 space-y-6">
            <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5">
                <h2 class="font-display text-base font-semibold text-midnight">Business details</h2>
                <p class="mt-0.5 text-sm text-slate">This information appears on your invoices.</p>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-ui.label for="business_name">Business name</x-ui.label>
                        <x-ui.input id="business_name" type="text" wire:model="business_name" required />
                        <x-ui.error for="business_name" />
                    </div>

                    <div>
                        <x-ui.label for="default_currency">Default currency</x-ui.label>
                        <select
                            id="default_currency"
                            wire:model="default_currency"
                            class="mt-1.5 block w-full rounded-field border border-slate/25 bg-white px-3 py-2.5 text-sm text-midnight transition focus:border-lapis focus:outline-none focus:ring-2 focus:ring-lapis/25 dark:bg-[#0b1c2e] dark:border-white/12"
                        >
                            @foreach ($this->getCurrencyOptions() as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-ui.error for="default_currency" />
                    </div>
                </div>
            </div>

            {{-- Logo --}}
            <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5">
                <h2 class="font-display text-base font-semibold text-midnight">Business logo</h2>
                <p class="mt-0.5 text-sm text-slate">Displayed in the app header. Recommended: square PNG or SVG, max 2 MB.</p>

                <div class="mt-5 flex flex-wrap items-start gap-6">
                    {{-- Preview --}}
                    <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-card bg-mist ring-1 ring-midnight/8 dark:bg-white/5">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" class="h-full w-full object-cover" alt="Logo preview">
                        @elseif (auth()->user()->tenant->logo_path)
                            <img src="{{ Storage::disk('public')->url(auth()->user()->tenant->logo_path) }}" class="h-full w-full object-contain p-2" alt="Current logo">
                        @else
                            <span class="font-display text-2xl font-semibold text-slate/40">
                                {{ Str::upper(Str::substr(auth()->user()->tenant->name, 0, 1)) }}
                            </span>
                        @endif
                    </div>

                    <div class="flex-1">
                        <label class="block">
                            <span class="text-sm font-medium text-midnight">Choose file</span>
                            <input
                                type="file"
                                wire:model="logo"
                                accept="image/*"
                                class="mt-1.5 block w-full text-sm text-slate file:mr-4 file:rounded-field file:border-0 file:bg-lapis/8 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-lapis hover:file:bg-lapis/12 dark:file:bg-lapis/15 dark:file:text-lapis"
                            >
                        </label>
                        <x-ui.error for="logo" />

                        @if (auth()->user()->tenant->logo_path)
                            <button
                                type="button"
                                wire:click="removeLogo"
                                class="mt-3 text-xs font-medium text-garnet hover:text-garnet/80"
                            >
                                Remove current logo
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="rounded-field bg-lapis px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="saveBusiness">Save business settings</span>
                    <span wire:loading wire:target="saveBusiness">Saving…</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Account tab --}}
    <div x-show="tab === 'account'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none">
        <form wire:submit="saveProfile" class="mt-6">
            <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5">
                <h2 class="font-display text-base font-semibold text-midnight">Profile information</h2>
                <p class="mt-0.5 text-sm text-slate">Update your name and email address.</p>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-ui.label for="profile_name">Full name</x-ui.label>
                        <x-ui.input id="profile_name" type="text" wire:model="profile_name" required autocomplete="name" />
                        <x-ui.error for="profile_name" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-ui.label for="profile_email">Email address</x-ui.label>
                        <x-ui.input id="profile_email" type="email" wire:model="profile_email" required autocomplete="email" />
                        <x-ui.error for="profile_email" />

                        @if (!auth()->user()->hasVerifiedEmail())
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400">
                                <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                Email not verified.
                                <a href="{{ route('verification.notice') }}" class="font-medium underline">Resend link</a>
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button
                    type="submit"
                    class="rounded-field bg-lapis px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="saveProfile">Save profile</span>
                    <span wire:loading wire:target="saveProfile">Saving…</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Security tab --}}
    <div x-show="tab === 'security'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none">
        <form wire:submit="savePassword" class="mt-6">
            <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5">
                <h2 class="font-display text-base font-semibold text-midnight">Change password</h2>
                <p class="mt-0.5 text-sm text-slate">Use a strong password you don't use anywhere else.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <x-ui.label for="current_password">Current password</x-ui.label>
                        <x-ui.input id="current_password" type="password" wire:model="current_password" required autocomplete="current-password" />
                        <x-ui.error for="current_password" />
                    </div>

                    <div>
                        <x-ui.label for="new_password">New password</x-ui.label>
                        <x-ui.input id="new_password" type="password" wire:model="new_password" required autocomplete="new-password" />
                        <x-ui.error for="new_password" />
                    </div>

                    <div>
                        <x-ui.label for="new_password_confirmation">Confirm new password</x-ui.label>
                        <x-ui.input id="new_password_confirmation" type="password" wire:model="new_password_confirmation" required autocomplete="new-password" />
                        <x-ui.error for="new_password_confirmation" />
                    </div>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button
                    type="submit"
                    class="rounded-field bg-lapis px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="savePassword">Update password</span>
                    <span wire:loading wire:target="savePassword">Updating…</span>
                </button>
            </div>
        </form>
    </div>
</div>
