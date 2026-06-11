<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth')] #[Title('Sign in')] class extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        session()->regenerate();

        $this->redirectIntended(route('dashboard'));
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
};
?>

<div class="rounded-modal bg-white p-8 shadow-pop ring-1 ring-midnight/5 sm:p-10">
    <h1 class="font-display text-2xl font-semibold tracking-tight">Sign in</h1>
    <p class="mt-1 text-sm text-slate">Welcome back. Your invoices are waiting.</p>

    <form wire:submit="login" class="mt-6 space-y-5">
        <div>
            <x-ui.label for="email">Email address</x-ui.label>
            <x-ui.input id="email" type="email" wire:model="email" required autofocus autocomplete="email" />
            <x-ui.error for="email" />
        </div>

        <div>
            <x-ui.label for="password">Password</x-ui.label>
            <x-ui.input id="password" type="password" wire:model="password" required autocomplete="current-password" />
            <x-ui.error for="password" />
        </div>

        <label class="flex items-center gap-2 text-sm text-slate">
            <input
                type="checkbox"
                wire:model="remember"
                class="size-4 rounded-sm border-slate/25 text-lapis focus:ring-lapis/25"
            >
            Keep me signed in
        </label>

        <x-ui.button type="submit">
            <span wire:loading.remove>Sign in</span>
            <span wire:loading>Signing in…</span>
        </x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-slate">
        New to InvoiceFlow?
        <a
            href="{{ route('register') }}"
            class="rounded-field font-medium text-lapis hover:text-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
        >
            Create an account
        </a>
    </p>
</div>
