<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::auth')] #[Title('Forgot password')] class extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    public bool $sent = false;

    public function sendLink(): void
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->sent = true;
        } else {
            $this->addError('email', __($status));
        }
    }
};
?>

<div class="rounded-modal bg-white p-8 shadow-pop ring-1 ring-midnight/5 sm:p-10">
    @if ($sent)
        <div class="text-center">
            <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-verdant/10 text-verdant">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </span>
            <h1 class="mt-4 font-display text-2xl font-semibold tracking-tight text-midnight">Check your email</h1>
            <p class="mt-2 text-sm text-slate">
                If <span class="font-medium text-midnight">{{ $email }}</span> is registered, we've sent a password reset link. Check your spam folder if it doesn't arrive.
            </p>
            <a
                href="{{ route('login') }}"
                class="mt-6 inline-block rounded-field px-4 py-2 text-sm font-medium text-lapis hover:text-lapis-deep"
            >
                ← Back to sign in
            </a>
        </div>
    @else
        <h1 class="font-display text-2xl font-semibold tracking-tight text-midnight">Forgot your password?</h1>
        <p class="mt-1 text-sm text-slate">Enter your email and we'll send a reset link.</p>

        <form wire:submit="sendLink" class="mt-6 space-y-5">
            <div>
                <x-ui.label for="email">Email address</x-ui.label>
                <x-ui.input id="email" type="email" wire:model="email" required autofocus autocomplete="email" />
                <x-ui.error for="email" />
            </div>

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-field bg-lapis px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span wire:loading.remove>Send reset link</span>
                <span wire:loading>Sending…</span>
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate">
            Remember your password?
            <a href="{{ route('login') }}" class="rounded-field font-medium text-lapis hover:text-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis">
                Sign in
            </a>
        </p>
    @endif
</div>
