<?php

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Jobs\GenerateInvoicePdf;
use App\Jobs\SendInvoiceEmail;
use App\Models\Invoice;
use Illuminate\Support\Facades\Bus;

class SendInvoice
{
    /**
     * Mark the invoice as sent and queue PDF generation followed by the
     * client email. The chain guarantees the PDF exists before mailing.
     */
    public function execute(Invoice $invoice): void
    {
        $invoice->forceFill([
            'status' => $invoice->status === InvoiceStatus::Draft ? InvoiceStatus::Sent : $invoice->status,
            'sent_at' => now(),
        ])->save();

        Bus::chain([
            new GenerateInvoicePdf($invoice),
            new SendInvoiceEmail($invoice),
        ])->dispatch();
    }
}
