<?php

use App\Actions\Auth\RegisterTenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth')] #[Title('Create your account')] class extends Component
{
    public string $business_name = '';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function register(RegisterTenant $registerTenant): void
    {
        $validated = $this->validate();

        $user = $registerTenant->execute(
            businessName: $validated['business_name'],
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
        );

        Auth::login($user);

        session()->regenerate();

        $this->redirectIntended(route('dashboard'));
    }
};
?>

<div class="rounded-modal bg-white p-8 shadow-pop ring-1 ring-midnight/5 sm:p-10">
    <h1 class="font-display text-2xl font-semibold tracking-tight">Create your account</h1>
    <p class="mt-1 text-sm text-slate">Start sending invoices in minutes.</p>

    <form wire:submit="register" class="mt-6 space-y-5">
        <div>
            <x-ui.label for="business_name">Business name</x-ui.label>
            <x-ui.input
                id="business_name"
                type="text"
                wire:model="business_name"
                placeholder="e.g. Northline Studio"
                required
                autofocus
                autocomplete="organization"
            />
            <x-ui.error for="business_name" />
        </div>

        <div>
            <x-ui.label for="name">Your name</x-ui.label>
            <x-ui.input id="name" type="text" wire:model="name" required autocomplete="name" />
            <x-ui.error for="name" />
        </div>

        <div>
            <x-ui.label for="email">Email address</x-ui.label>
            <x-ui.input id="email" type="email" wire:model="email" required autocomplete="email" />
            <x-ui.error for="email" />
        </div>

        <div>
            <x-ui.label for="password">Password</x-ui.label>
            <x-ui.input id="password" type="password" wire:model="password" required autocomplete="new-password" />
            <x-ui.error for="password" />
        </div>

        <div>
            <x-ui.label for="password_confirmation">Confirm password</x-ui.label>
            <x-ui.input
                id="password_confirmation"
                type="password"
                wire:model="password_confirmation"
                required
                autocomplete="new-password"
            />
            <x-ui.error for="password_confirmation" />
        </div>

        <x-ui.button type="submit">
            <span wire:loading.remove>Create account</span>
            <span wire:loading>Creating account…</span>
        </x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-slate">
        Already have an account?
        <a
            href="{{ route('login') }}"
            class="rounded-field font-medium text-lapis hover:text-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis"
        >
            Sign in
        </a>
    </p>
</div>
