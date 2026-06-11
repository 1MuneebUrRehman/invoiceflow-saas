<?php

namespace App\Tenancy;

use App\Models\Tenant;
use Illuminate\Container\Attributes\Scoped;

/**
 * Holds the active tenant for the current request / job lifecycle.
 *
 * Scoped to the container lifecycle so state is flushed between requests
 * (Octane) and between queued jobs, preventing tenant context leaks.
 */
#[Scoped]
class CurrentTenant
{
    protected ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function isSet(): bool
    {
        return $this->tenant !== null;
    }

    public function forget(): void
    {
        $this->tenant = null;
    }
}
