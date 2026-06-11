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

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice {$this->invoice->number} from {$this->invoice->tenant->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice',
            with: [
                'invoice' => $this->invoice,
                'invoiceUrl' => URL::signedRoute('invoices.public', ['publicId' => $this->invoice->public_id]),
                'formattedTotal' => Number::currency($this->invoice->total / 100, in: $this->invoice->currency),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->invoice->pdf_path === null) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->invoice->pdf_path)
                ->as("{$this->invoice->number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
