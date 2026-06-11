<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Actions\Invoices\GenerateInvoiceNumber;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Tenancy\CurrentTenant;
use Carbon\CarbonImmutable;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    /**
     * Generate the invoice number and insert the record in one transaction,
     * so the tenant row lock guarantees sequential, collision-free numbers.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $data['number'] = app(GenerateInvoiceNumber::class)->execute(
                tenantId: (int) app(CurrentTenant::class)->id(),
                year: CarbonImmutable::parse($data['issue_date'])->year,
            );

            return static::getModel()::create($data);
        });
    }

    /**
     * Line items are persisted after the record itself, so totals are
     * recomputed once everything is in the database.
     */
    protected function afterCreate(): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->record;

        $invoice->recalculateTotals();
    }
}
