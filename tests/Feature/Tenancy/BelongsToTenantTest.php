<?php

use App\Models\Client;
use App\Models\Tenant;
use App\Tenancy\CurrentTenant;

test('tenant_id is stamped automatically when a tenant context is set', function () {
    $tenant = Tenant::factory()->create();

    app(CurrentTenant::class)->set($tenant);

    $client = Client::create(Client::factory()->raw(['tenant_id' => null]));

    expect($client->tenant_id)->toBe($tenant->id);
});

test('an explicit tenant_id is respected when no context is set', function () {
    $tenant = Tenant::factory()->create();

    $client = Client::factory()->for($tenant)->create();

    expect($client->tenant_id)->toBe($tenant->id);
});

test('the trait exposes a tenant relationship', function () {
    $tenant = Tenant::factory()->create();

    $client = Client::factory()->for($tenant)->create();

    expect($client->tenant)->toBeInstanceOf(Tenant::class)
        ->and($client->tenant->id)->toBe($tenant->id);
});
