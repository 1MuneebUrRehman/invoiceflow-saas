<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #13283f;
            line-height: 1.5;
        }
        .page { padding: 48px 56px; }
        .header { width: 100%; margin-bottom: 40px; }
        .header td { vertical-align: top; }
        .brand-name { font-size: 22px; font-weight: bold; color: #13283f; }
        .doc-title {
            font-size: 26px;
            font-weight: bold;
            color: #1f5eae;
            text-align: right;
            letter-spacing: 2px;
        }
        .doc-number { text-align: right; color: #5b6b7f; font-size: 13px; margin-top: 2px; }
        .meta { width: 100%; margin-bottom: 36px; }
        .meta td { vertical-align: top; }
        .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #5b6b7f;
            margin-bottom: 4px;
        }
        .bill-to-name { font-weight: bold; font-size: 13px; }
        .meta-table td { padding: 1px 0; }
        .meta-table .key { color: #5b6b7f; padding-right: 18px; }
        .meta-table .value { text-align: right; font-weight: bold; }
        .status {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #ffffff;
            background-color: {{ match ($invoice->status->value) {
                'paid' => '#2e7d5b',
                'overdue' => '#b42318',
                'sent' => '#1f5eae',
                default => '#5b6b7f',
            } }};
        }
        .items { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .items thead th {
            background-color: #13283f;
            color: #ffffff;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 9px 12px;
            text-align: left;
        }
        .items thead th.num, .items tbody td.num { text-align: right; }
        .items tbody td {
            padding: 9px 12px;
            border-bottom: 1px solid #e4e9f1;
        }
        .items tbody tr:nth-child(even) td { background-color: #f4f6fa; }
        .totals { width: 290px; margin-left: auto; border-collapse: collapse; }
        .totals td { padding: 5px 12px; }
        .totals .key { color: #5b6b7f; }
        .totals .value { text-align: right; }
        .totals .grand td {
            border-top: 2px solid #13283f;
            font-size: 15px;
            font-weight: bold;
            padding-top: 9px;
        }
        .totals .due td { color: #b42318; font-weight: bold; }
        .notes { margin-top: 36px; padding: 14px 16px; background-color: #f4f6fa; border-radius: 6px; }
        .footer {
            margin-top: 48px;
            padding-top: 14px;
            border-top: 1px solid #e4e9f1;
            color: #5b6b7f;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $money = fn (int $minorUnits): string => \Illuminate\Support\Number::currency($minorUnits / 100, in: $invoice->currency);
    @endphp

    <div class="page">
        <table class="header">
            <tr>
                <td>
                    <div class="brand-name">{{ $invoice->tenant->name }}</div>
                </td>
                <td>
                    <div class="doc-title">INVOICE</div>
                    <div class="doc-number">{{ $invoice->number }}</div>
                </td>
            </tr>
        </table>

        <table class="meta">
            <tr>
                <td style="width: 55%;">
                    <div class="label">Billed to</div>
                    <div class="bill-to-name">{{ $invoice->client->company_name ?? $invoice->client->name }}</div>
                    @if ($invoice->client->company_name)
                        <div>{{ $invoice->client->name }}</div>
                    @endif
                    @foreach (array_filter([
                        $invoice->client->address_line1,
                        $invoice->client->address_line2,
                        trim(implode(' ', array_filter([$invoice->client->city, $invoice->client->state, $invoice->client->postal_code]))),
                        $invoice->client->country,
                    ]) as $addressLine)
                        <div>{{ $addressLine }}</div>
                    @endforeach
                    <div>{{ $invoice->client->email }}</div>
                </td>
                <td style="width: 45%;">
                    <table class="meta-table" style="width: 100%;">
                        <tr>
                            <td class="key">Status</td>
                            <td class="value"><span class="status">{{ $invoice->status->label() }}</span></td>
                        </tr>
                        <tr>
                            <td class="key">Issue date</td>
                            <td class="value">{{ $invoice->issue_date->format('M j, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="key">Due date</td>
                            <td class="value">{{ $invoice->due_date->format('M j, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="key">Amount due</td>
                            <td class="value">{{ $money($invoice->amountDue()) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th class="num" style="width: 12%;">Qty</th>
                    <th class="num" style="width: 19%;">Unit price</th>
                    <th class="num" style="width: 19%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="num">{{ rtrim(rtrim((string) $item->quantity, '0'), '.') }}</td>
                        <td class="num">{{ $money($item->unit_price) }}</td>
                        <td class="num">{{ $money($item->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="key">Subtotal</td>
                <td class="value">{{ $money($invoice->subtotal) }}</td>
            </tr>
            <tr>
                <td class="key">Tax ({{ rtrim(rtrim((string) $invoice->tax_rate, '0'), '.') }}%)</td>
                <td class="value">{{ $money($invoice->tax_amount) }}</td>
            </tr>
            <tr class="grand">
                <td class="key">Total</td>
                <td class="value">{{ $money($invoice->total) }}</td>
            </tr>
            @if ($invoice->amount_paid > 0)
                <tr>
                    <td class="key">Paid</td>
                    <td class="value">&minus;{{ $money($invoice->amount_paid) }}</td>
                </tr>
                <tr class="due">
                    <td class="key">Amount due</td>
                    <td class="value">{{ $money($invoice->amountDue()) }}</td>
                </tr>
            @endif
        </table>

        @if ($invoice->notes)
            <div class="notes">
                <div class="label">Notes</div>
                <div>{{ $invoice->notes }}</div>
            </div>
        @endif

        <div class="footer">
            {{ $invoice->tenant->name }} &middot; Invoice {{ $invoice->number }} &middot; Generated with InvoiceFlow
        </div>
    </div>
</body>
</html>
