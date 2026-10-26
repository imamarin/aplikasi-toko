<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $sale->invoice_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; line-height: 1.4; margin: 0; padding: 20px; }
        .header { margin-bottom: 25px; border-bottom: 2px solid #4f46e5; padding-bottom: 15px; }
        .store-name { font-size: 20px; font-weight: bold; color: #4f46e5; }
        .invoice-title { font-size: 18px; font-weight: bold; text-align: right; }
        .meta-table { width: 100%; margin-bottom: 20px; }
        .meta-table td { vertical-align: top; }
        .table-items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-items th { background: #f1f5f9; padding: 8px; text-align: left; border-bottom: 1px solid #cbd5e1; }
        .table-items td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-table { width: 40%; margin-left: auto; border-collapse: collapse; }
        .total-table td { padding: 5px; }
        .total-highlight { font-size: 14px; font-weight: bold; color: #4f46e5; }
    </style>
</head>
<body>
    <table class="meta-table header">
        <tr>
            <td>
                <div class="store-name">{{ $settings['store_name'] }}</div>
                <div>{{ $settings['store_address'] }}</div>
                <div>Telp: {{ $settings['store_phone'] }} | Email: {{ $settings['store_email'] }}</div>
            </td>
            <td class="text-right">
                <div class="invoice-title">INVOICE PENJUALAN</div>
                <div><strong>No: {{ $sale->invoice_number }}</strong></div>
                <div>Tanggal: {{ $sale->transaction_date->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <strong>Kepada Pelanggan:</strong><br>
                {{ $sale->customer?->name ?? 'Umum / Walk-in Customer' }}<br>
                {{ $sale->customer?->phone ?? '-' }}<br>
                {{ $sale->customer?->address ?? '' }}
            </td>
            <td style="width: 50%;" class="text-right">
                <strong>Kasir:</strong> {{ $sale->user?->name ?? 'Kasir' }}<br>
                <strong>Metode Bayar:</strong> {{ strtoupper($sale->payment_method) }}<br>
                <strong>Status:</strong> LUNAS
            </td>
        </tr>
    </table>

    <table class="table-items">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Nama Produk</th>
                <th style="width: 15%;" class="text-right">Harga</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 10%;" class="text-right">Diskon</th>
                <th style="width: 15%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->details as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="text-right">Rp {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $item->qty }}</td>
                    <td class="text-right">{{ $item->discount_amount > 0 ? 'Rp ' . number_format($item->discount_amount, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="total-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($sale->discount_amount > 0)
            <tr>
                <td>Diskon Nota:</td>
                <td class="text-right">-Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if($sale->tax_amount > 0)
            <tr>
                <td>PPN ({{ $sale->tax_percentage }}%):</td>
                <td class="text-right">+Rp {{ number_format($sale->tax_amount, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if($sale->shipping_cost > 0)
            <tr>
                <td>Ongkos Kirim:</td>
                <td class="text-right">+Rp {{ number_format($sale->shipping_cost, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr class="total-highlight">
            <td>Grand Total:</td>
            <td class="text-right">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Bayar:</td>
            <td class="text-right">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Kembalian:</td>
            <td class="text-right">Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</td>
        </tr>
    </table>
</body>
</html>
