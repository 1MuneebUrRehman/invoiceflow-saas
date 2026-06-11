<x-mail::message>
# Invoice {{ $invoice->number }}

Hi {{ $invoice->client->name }},

{{ $invoice->tenant->name }} has sent you an invoice for **{{ $formattedTotal }}**, due
{{ $invoice->due_date->format('F j, Y') }}.

<x-mail::button :url="$invoiceUrl">
View invoice
</x-mail::button>

A PDF copy is attached for your records. If you have any questions about this
invoice, just reply to this email.

Thanks,<br>
{{ $invoice->tenant->name }}
</x-mail::message>
