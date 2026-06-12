<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Verify email')] class extends Component
{
    public bool $sent = false;

    public function resend(): void
    {
        if (auth()->user()->hasVerifiedEmail()) {
            $this->redirect(route('dashboard'));

            return;
        }

        auth()->user()->sendEmailVerificationNotification();
        $this->sent = true;
    }
};
?>

<div class="flex flex-1 items-center justify-center">
    <div class="w-full max-w-md rounded-modal bg-white p-8 shadow-pop ring-1 ring-midnight/5 text-center sm:p-10">
        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-lapis/10 text-lapis">
            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </span>

        <h1 class="mt-4 font-display text-2xl font-semibold tracking-tight text-midnight">Verify your email</h1>

        @if ($sent)
            <p class="mt-3 text-sm font-medium text-verdant">
                A new verification link has been sent to your inbox.
            </p>
        @else
            <p class="mt-3 text-sm text-slate">
                We sent a link to <span class="font-medium text-midnight">{{ auth()->user()->email }}</span>. Click it to activate your account.
            </p>
        @endif

        <p class="mt-2 text-sm text-slate">Didn't get it? Check your spam folder, or resend below.</p>

        <div class="mt-6 flex flex-col gap-3">
            <button
                wire:click="resend"
                class="inline-flex w-full items-center justify-center gap-2 rounded-field bg-lapis px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="resend">Resend verification email</span>
                <span wire:loading wire:target="resend">Sending…</span>
            </button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-field px-4 py-2 text-sm font-medium text-slate ring-1 ring-midnight/12 transition hover:bg-mist hover:text-midnight dark:hover:bg-white/6">
                    Sign out and try another account
                </button>
            </form>
        </div>
    </div>
</div>
