<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;

function clientUser(): User
{
    return User::factory()
        ->for(Tenant::factory(), 'tenant')
        ->create(['role' => UserRole::Owner]);
}

it('lists only the current tenant\'s clients', function (): void {
    $user = clientUser();
    $ownClients = Client::factory()->count(3)->for($user->tenant)->create();
    Client::factory()->count(2)->for(Tenant::factory())->create(); // other tenant

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/clients')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('creates a client', function (): void {
    $user = clientUser();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/clients', [
            'name' => 'Acme Inc',
            'email' => 'acme@example.com',
            'currency' => 'USD',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Acme Inc')
        ->assertJsonPath('data.email', 'acme@example.com');

    $this->assertDatabaseHas('clients', ['email' => 'acme@example.com', 'tenant_id' => $user->tenant_id]);
});

it('validates required fields on create', function (): void {
    $user = clientUser();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/clients', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email']);
});

it('shows a client', function (): void {
    $user = clientUser();
    $client = Client::factory()->for($user->tenant)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/clients/{$client->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $client->id);
});

it('cannot show another tenant\'s client', function (): void {
    $user = clientUser();
    $otherClient = Client::factory()->for(Tenant::factory())->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/clients/{$otherClient->id}")
        ->assertNotFound();
});

it('updates a client', function (): void {
    $user = clientUser();
    $client = Client::factory()->for($user->tenant)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/clients/{$client->id}", ['name' => 'Updated Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');
});

it('deletes a client', function (): void {
    $user = clientUser();
    $client = Client::factory()->for($user->tenant)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/clients/{$client->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('clients', ['id' => $client->id]);
});

it('requires authentication', function (): void {
    $this->getJson('/api/v1/clients')->assertUnauthorized();
});

it('searches clients by name', function (): void {
    $user = clientUser();
    Client::factory()->for($user->tenant)->create(['name' => 'Alpha Corp']);
    Client::factory()->for($user->tenant)->create(['name' => 'Beta Ltd']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/clients?search=Alpha')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Corp');
});
