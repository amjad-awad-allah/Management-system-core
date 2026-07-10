<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->id }}</title>
    <style>
        body { font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; color: #555; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); font-size: 16px; line-height: 24px; }
        .invoice-box table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        .invoice-box table td { padding: 5px; vertical-align: top; }
        .invoice-box table tr td:nth-child(2) { text-align: right; }
        .invoice-box table tr.top table td { padding-bottom: 20px; }
        .invoice-box table tr.top table td.title { font-size: 45px; line-height: 45px; color: #333; }
        .invoice-box table tr.information table td { padding-bottom: 40px; }
        .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
        .invoice-box table tr.details td { padding-bottom: 20px; }
        .invoice-box table tr.item td { border-bottom: 1px solid #eee; }
        .invoice-box table tr.item.last td { border-bottom: none; }
        .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table>
            <tr class="top">
                <td colspan="4">
                    <table>
                        <tr>
                            <td class="title">
                                <!-- Place logo here -->
                                <h1 style="color: #6366f1;">Nachhilfe</h1>
                            </td>
                            <td>
                                Invoice #: {{ substr($invoice->id, 0, 8) }}<br>
                                Created: {{ $invoice->created_at->format('M d, Y') }}<br>
                                Due: {{ $invoice->due_date->format('M d, Y') }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="information">
                <td colspan="4">
                    <table>
                        <tr>
                            <td>
                                Nachhilfe Center GmbH.<br>
                                Musterstr. 123<br>
                                12345 Berlin, Germany
                            </td>
                            <td>
                                Reference Type: {{ $invoice->reference_type }}<br>
                                Ref ID: {{ substr($invoice->reference_id, 0, 8) }}<br>
                                Status: {{ strtoupper($invoice->status) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="heading">
                <td>Item</td>
                <td class="text-center">Quantity</td>
                <td class="text-center">Unit Price</td>
                <td class="text-right">Total</td>
            </tr>

            @foreach ($invoice->items as $item)
            <tr class="item {{ $loop->last ? 'last' : '' }}">
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-center">€{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">€{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach

            <tr class="total">
                <td colspan="3" class="text-right">Subtotal:</td>
                <td class="text-right">€{{ number_format($invoice->amount, 2) }}</td>
            </tr>
            
            @if($invoice->total_paid > 0)
            <tr class="total">
                <td colspan="3" class="text-right">Amount Paid:</td>
                <td class="text-right" style="color: green;">- €{{ number_format($invoice->total_paid, 2) }}</td>
            </tr>
            <tr class="total">
                <td colspan="3" class="text-right">Balance Due:</td>
                <td class="text-right">€{{ number_format($invoice->balance, 2) }}</td>
            </tr>
            @endif
        </table>
    </div>
</body>
</html>
