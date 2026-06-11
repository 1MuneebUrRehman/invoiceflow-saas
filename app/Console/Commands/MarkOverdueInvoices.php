<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('invoices:mark-overdue')]
#[Description('Mark sent invoices whose due date has passed as overdue.')]
class MarkOverdueInvoices extends Command
{
    public function handle(): int
    {
        // TenantScope is a no-op without CurrentTenant, so this runs across all tenants.
        $count = Invoice::query()
            ->where('status', InvoiceStatus::Sent)
            ->where('due_date', '<', today())
            ->update(['status' => InvoiceStatus::Overdue->value]);

        $this->info("Marked {$count} ".str('invoice')->plural($count).' as overdue.');

        return self::SUCCESS;
    }
}
