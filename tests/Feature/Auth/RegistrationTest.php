<?php

use App\Actions\Auth\RegisterTenant;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Livewire\Livewire;

test('the registration page renders', function () {
    $this->get(route('register'))->assertOk();
});

test('a new tenant and owner are created on registration', function () {
    Livewire::test('pages::auth.register')
        ->set('business_name', 'Halloway Design Co')
        ->set('name', 'Imogen Halloway')
        ->set('email', 'imogen@halloway.test')
        ->set('password', 'secret-password')
        ->set('password_confirmation', 'secret-password')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    $tenant = Tenant::query()->where('name', 'Halloway Design Co')->sole();
    $user = User::query()->where('email', 'imogen@halloway.test')->sole();

    expect($user->tenant_id)->toBe($tenant->id)
        ->and($user->role)->toBe(UserRole::Owner);
});

test('registration requires a unique email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test('pages::auth.register')
        ->set('business_name', 'Studio')
        ->set('name', 'Someone')
        ->set('email', 'taken@example.com')
        ->set('password', 'secret-password')
        ->set('password_confirmation', 'secret-password')
        ->call('register')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

test('no orphan tenant is left behind when user creation fails', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $tenantCount = Tenant::count();

    expect(fn () => app(RegisterTenant::class)->execute(
        businessName: 'Doomed Studio',
        name: 'Someone',
        email: 'taken@example.com',
        password: 'secret-password',
    ))->toThrow(UniqueConstraintViolationException::class);

    expect(Tenant::count())->toBe($tenantCount)
        ->and(Tenant::query()->where('name', 'Doomed Studio')->exists())->toBeFalse();
});
