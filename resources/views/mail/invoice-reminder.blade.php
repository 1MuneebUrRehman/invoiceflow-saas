<x-mail::message>
# Payment reminder — Invoice {{ $invoice->number }}

Hi {{ $invoice->client->name }},

This is a friendly reminder that invoice **{{ $invoice->number }}** from
**{{ $invoice->tenant->name }}** for **{{ $formattedTotal }}** is now
**{{ $daysOverdue }} {{ $daysOverdue === 1 ? 'day' : 'days' }} overdue**.

It was due on **{{ $invoice->due_date->format('F j, Y') }}**. Please arrange
payment at your earliest convenience to avoid any disruption.

<x-mail::button :url="$invoiceUrl">
View &amp; Pay Invoice
</x-mail::button>

If you have already sent payment, please disregard this message. Thank you for
your business.

Thanks,<br>
{{ $invoice->tenant->name }}
</x-mail::message>
