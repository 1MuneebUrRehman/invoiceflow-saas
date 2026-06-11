<?php

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPaymentReminderEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public int $daysOverdue,
    ) {}

    public function handle(): void
    {
        $invoice = $this->invoice->fresh(['client', 'tenant']);

        // Guard: skip if already paid while the job sat in the queue.
        if ($invoice->status !== InvoiceStatus::Overdue) {
            return;
        }

        Mail::to($invoice->client->email)
            ->send(new InvoiceReminderMail($invoice, $this->daysOverdue));
    }
}
