<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth')] #[Title('Reset password')] class extends Component
{
    #[Locked]
    public string $token = '';

    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'token'    => 'required|string',
            'email'    => 'required|string|email',
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ];
    }

    public function resetPassword(): void
    {
        $this->validate();

        $status = Password::reset(
            [
                'email'                 => $this->email,
                'password'              => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token'                 => $this->token,
            ],
            function (User $user) {
                $user->forceFill([
                    'password'       => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $this->redirect(route('login'));
        } else {
            $this->addError('email', __($status));
        }
    }
};
?>

<div class="rounded-modal bg-white p-8 shadow-pop ring-1 ring-midnight/5 sm:p-10">
    <h1 class="font-display text-2xl font-semibold tracking-tight text-midnight">Set new password</h1>
    <p class="mt-1 text-sm text-slate">Choose a strong password you haven't used before.</p>

    <form wire:submit="resetPassword" class="mt-6 space-y-5">
        <div>
            <x-ui.label for="email">Email address</x-ui.label>
            <x-ui.input id="email" type="email" wire:model="email" required autocomplete="email" />
            <x-ui.error for="email" />
        </div>

        <div>
            <x-ui.label for="password">New password</x-ui.label>
            <x-ui.input id="password" type="password" wire:model="password" required autofocus autocomplete="new-password" />
            <x-ui.error for="password" />
        </div>

        <div>
            <x-ui.label for="password_confirmation">Confirm new password</x-ui.label>
            <x-ui.input id="password_confirmation" type="password" wire:model="password_confirmation" required autocomplete="new-password" />
            <x-ui.error for="password_confirmation" />
        </div>

        <button
            type="submit"
            class="inline-flex w-full items-center justify-center gap-2 rounded-field bg-lapis px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis disabled:cursor-not-allowed disabled:opacity-60"
        >
            <span wire:loading.remove>Reset password</span>
            <span wire:loading>Resetting…</span>
        </button>
    </form>
</div>
