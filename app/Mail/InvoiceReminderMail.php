<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Number;

class InvoiceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public int $daysOverdue,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reminder: Invoice {$this->invoice->number} is {$this->daysOverdue} ".
                     ($this->daysOverdue === 1 ? 'day' : 'days').' overdue',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice-reminder',
            with: [
                'invoice' => $this->invoice,
                'invoiceUrl' => URL::signedRoute('invoices.public', ['publicId' => $this->invoice->public_id]),
                'formattedTotal' => Number::currency($this->invoice->amountDue() / 100, in: $this->invoice->currency),
                'daysOverdue' => $this->daysOverdue,
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
