<?php

namespace App\Models\Scopes;

use App\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Constrains every query to the active tenant.
 *
 * Deliberately a no-op when no tenant context is set (console commands,
 * queue workers iterating all tenants): queries run unscoped rather than
 * silently returning nothing. HTTP requests always have a tenant set via
 * the SetCurrentTenant middleware.
 *
 * @implements Scope<Model>
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $currentTenant = app(CurrentTenant::class);

        if ($currentTenant->isSet()) {
            $builder->where($model->qualifyColumn('tenant_id'), $currentTenant->id());
        }
    }
}
