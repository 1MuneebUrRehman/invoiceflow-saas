<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Jobs\SendPaymentReminderEmail;
use App\Models\Invoice;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('invoices:send-reminders')]
#[Description('Queue payment-reminder emails for overdue invoices at +3/+7/+14 days.')]
class SendInvoiceReminders extends Command
{
    /** Reminder intervals in days after the due date. */
    private const INTERVALS = [3, 7, 14];

    public function handle(): int
    {
        $dispatched = 0;

        foreach (self::INTERVALS as $days) {
            // Invoice was due at least $days ago but within a 90-day window
            // so stale invoices don't keep getting reminders indefinitely.
            Invoice::query()
                ->where('status', InvoiceStatus::Overdue)
                ->whereDate('due_date', '<=', today()->subDays($days))
                ->whereDate('due_date', '>=', today()->subDays(90))
                ->whereJsonDoesntContain('reminders_sent', $days)
                ->with(['client', 'tenant'])
                ->chunk(100, function ($invoices) use ($days, &$dispatched): void {
                    foreach ($invoices as $invoice) {
                        SendPaymentReminderEmail::dispatch($invoice, $days);

                        // Mark this interval as sent before the next chunk to
                        // prevent re-queuing if the chunk loop is interrupted.
                        $sent = $invoice->reminders_sent ?? [];
                        $sent[] = $days;
                        $invoice->forceFill(['reminders_sent' => array_values(array_unique($sent))])->saveQuietly();

                        $dispatched++;
                    }
                });
        }

        $this->info("Dispatched {$dispatched} reminder ".str('email')->plural($dispatched).'.');

        return self::SUCCESS;
    }
}
