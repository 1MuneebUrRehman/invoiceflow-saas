<?php

use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

test('the login page renders', function () {
    $this->get(route('login'))->assertOk();
});

test('a user can sign in with valid credentials', function () {
    $user = User::factory()->owner()->for(Tenant::factory())->create();

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('invalid credentials are rejected', function () {
    $user = User::factory()->owner()->for(Tenant::factory())->create();

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

test('a user can sign out', function () {
    $user = User::factory()->owner()->for(Tenant::factory())->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

test('guests are redirected away from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
