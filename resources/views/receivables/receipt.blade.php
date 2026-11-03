<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi Piutang #{{ $payment->payment_number }}</title>
    <style>
        @page {
            margin: 0;
            size: 58mm auto;
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
            Cetak Kwitansi
        </button>
    </div>

    <div class="text-center">
        <div class="fw-bold" style="font-size: 13px;">{{ $settings['store_name'] }}</div>
        <div>{{ $settings['store_address'] }}</div>
        <div>Telp: {{ $settings['store_phone'] }}</div>
    </div>

    <div class="double-divider"></div>
    <div class="text-center fw-bold">BUKTI PEMBAYARAN PIUTANG</div>
    <div class="divider"></div>

    <div>
        <div>No. Bukti: {{ $payment->payment_number }}</div>
        <div>Tanggal  : {{ $payment->payment_date->format('d/m/Y H:i') }}</div>
        <div>Kasir    : {{ $payment->user?->name ?? 'Kasir' }}</div>
        <div>Pelanggan: {{ $payment->customer?->name ?? '-' }}</div>
        <div>Metode   : {{ strtoupper($payment->payment_method) }}</div>
    </div>

    <div class="divider"></div>
    <div class="fw-bold">Alokasi Nota:</div>
    <table class="table-items">
        @foreach($payment->allocations as $alloc)
            <tr>
                <td>{{ $alloc->sale?->invoice_number }}</td>
                <td class="text-end">Rp {{ number_format($alloc->amount, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <table class="table-items">
        <tr class="fw-bold" style="font-size: 12px;">
            <td>TOTAL DIBAYAR:</td>
            <td class="text-end">Rp {{ number_format($payment->total_paid, 0, ',', '.') }}</td>
        </tr>
        <tr class="fw-bold">
            <td>SISA TOTAL HUTANG:</td>
            <td class="text-end">Rp {{ number_format($remainingCustomerDebt, 0, ',', '.') }}</td>
        </tr>
    </table>

    @if($payment->notes)
        <div class="divider"></div>
        <div>Catatan: {{ $payment->notes }}</div>
    @endif

    <div class="double-divider"></div>
    <div class="text-center" style="font-size: 10px;">
        {!! nl2br(e($settings['receipt_footer'])) !!}
    </div>
    <div style="height: 15px;"></div>
</body>
</html>

