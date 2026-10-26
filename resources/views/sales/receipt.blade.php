<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran #{{ $sale->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
            size: 58mm auto; /* 58mm roll width default */
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.35;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 8px;
            width: 58mm;
            max-width: 58mm;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .double-divider {
            border-top: 2px dashed #000;
            margin: 6px 0;
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
        }
        .table-items td {
            vertical-align: top;
            padding: 2px 0;
        }
        .no-print {
            margin-bottom: 12px;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print text-center" style="padding: 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px;">
        <button onclick="window.print()" style="padding: 6px 16px; background: #4f46e5; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">
            Cetak Ulang Struk
        </button>
    </div>

    <!-- Store Header -->
    <div class="text-center">
        <div class="fw-bold" style="font-size: 13px;">{{ $settings['store_name'] }}</div>
        <div>{{ $settings['store_address'] }}</div>
        <div>Telp: {{ $settings['store_phone'] }}</div>
    </div>

    <div class="double-divider"></div>

    <!-- Transaction Meta -->
    <div>
        <div>Nota : {{ $sale->invoice_number }}</div>
        <div>Tgl  : {{ $sale->transaction_date->format('d/m/Y H:i') }}</div>
        <div>Kasir: {{ $sale->user?->name ?? 'Kasir' }}</div>
        <div>Plg  : {{ $sale->customer?->name ?? 'Umum' }}</div>
    </div>

    <div class="divider"></div>

    <!-- Items List -->
    <table class="table-items">
        @foreach($sale->details as $item)
            <tr>
                <td colspan="3" class="fw-bold">{{ $item->product_name }}</td>
            </tr>
            <tr>
                <td style="width: 50%;">{{ rtrim(rtrim(number_format((float) $item->qty, 3, ',', '.'), '0'), ',') }} x {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                <td class="text-end" style="width: 20%;">
                    @if($item->discount_amount > 0)
                        -{{ number_format($item->discount_amount, 0, ',', '.') }}
                    @endif
                </td>
                <td class="text-end fw-bold" style="width: 30%;">
                    {{ number_format($item->subtotal, 0, ',', '.') }}
                </td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <!-- Totals -->
    <table class="table-items">
        <tr>
            <td>Subtotal:</td>
            <td class="text-end">{{ number_format($sale->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($sale->discount_amount > 0)
            <tr>
                <td>Diskon Nota:</td>
                <td class="text-end">-{{ number_format($sale->discount_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if($sale->tax_amount > 0)
            <tr>
                <td>PPN ({{ $sale->tax_percentage }}%):</td>
                <td class="text-end">+{{ number_format($sale->tax_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr class="fw-bold" style="font-size: 12px;">
            <td>GRAND TOTAL:</td>
            <td class="text-end">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Bayar ({{ strtoupper($sale->payment_method) }}):</td>
            <td class="text-end">{{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
        </tr>
        <tr class="fw-bold">
            <td>Kembalian:</td>
            <td class="text-end">{{ number_format($sale->change_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="double-divider"></div>

    <!-- Footer Note -->
    <div class="text-center" style="font-size: 10px;">
        {!! nl2br(e($settings['receipt_footer'])) !!}
    </div>

    <div style="height: 15px;"></div>
</body>
</html>
