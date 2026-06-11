<?php

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;

it('issues a token with valid credentials', function (): void {
    $user = User::factory()
        ->for(Tenant::factory(), 'tenant')
        ->create(['password' => bcrypt('password')]);

    $this->postJson('/api/v1/auth/tokens', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'PHPUnit',
    ])
        ->assertCreated()
        ->assertJsonStructure(['token', 'token_type']);
});

it('rejects invalid credentials', function (): void {
    $user = User::factory()
        ->for(Tenant::factory(), 'tenant')
        ->create(['password' => bcrypt('password')]);

    $this->postJson('/api/v1/auth/tokens', [
        'email' => $user->email,
        'password' => 'wrong',
        'device_name' => 'PHPUnit',
    ])->assertUnprocessable();
});

it('revokes the current token', function (): void {
    $user = User::factory()
        ->for(Tenant::factory(), 'tenant')
        ->create(['role' => UserRole::Owner]);

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->deleteJson('/api/v1/auth/tokens/current')
        ->assertOk()
        ->assertJson(['message' => 'Token revoked.']);

    // Token is now invalid
    $this->withToken($token)
        ->getJson('/api/v1/billing')
        ->assertUnauthorized();
});

it('requires device_name when issuing a token', function (): void {
    $user = User::factory()->for(Tenant::factory(), 'tenant')->create();

    $this->postJson('/api/v1/auth/tokens', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['device_name']);
});
