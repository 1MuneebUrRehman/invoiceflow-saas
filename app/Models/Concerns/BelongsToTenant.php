<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scopes the model to the active tenant and stamps tenant_id on create.
 *
 * When a tenant context is set, tenant_id is always forced to the active
 * tenant — a request can never write rows into another tenant, even via
 * mass assignment. Without a context (console, seeders, tests), tenant_id
 * must be provided explicitly.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $currentTenant = app(CurrentTenant::class);

            if ($currentTenant->isSet()) {
                $model->setAttribute('tenant_id', $currentTenant->id());
            }
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
