<?php

namespace App\Actions\Invoices;

use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateInvoiceNumber
{
    /**
     * Next sequential invoice number for a tenant and year, e.g. INV-2026-0001.
     *
     * Acquires a row lock on the tenant so concurrent requests are serialized.
     * Must be called inside the database transaction that inserts the invoice,
     * so the lock is held until the new number is committed.
     */
    public function execute(int $tenantId, int $year): string
    {
        Tenant::query()->whereKey($tenantId)->lockForUpdate()->first();

        $prefix = sprintf('INV-%d-', $year);

        $lastNumber = Invoice::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc(DB::raw('LENGTH(number)'))
            ->orderByDesc('number')
            ->value('number');

        $nextSequence = $lastNumber === null
            ? 1
            : ((int) Str::afterLast($lastNumber, '-')) + 1;

        return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
