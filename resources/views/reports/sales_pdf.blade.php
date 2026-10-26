<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <style>
        @page {
            margin: 12mm 10mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #222;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #222;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .store-title {
            font-size: 16px;
            font-weight: bold;
            color: #111;
            text-transform: uppercase;
        }
        .store-sub {
            font-size: 9px;
            color: #555;
            margin-top: 2px;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-top: 6px;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }
        .report-meta {
            text-align: center;
            font-size: 9.5px;
            color: #555;
            margin-bottom: 15px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #111;
            padding: 6px 5px;
            text-align: left;
            border: 1px solid #94a3b8;
            font-size: 9.5px;
        }
        table.data-table td {
            padding: 5px;
            border: 1px solid #cbd5e1;
            font-size: 9px;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        
        .summary-box {
            width: 100%;
            margin-bottom: 20px;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .signature-table td {
            text-align: center;
            vertical-align: top;
            font-size: 9.5px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                <div class="store-title">{{ $settings['store_name'] }}</div>
                <div class="store-sub">{{ $settings['store_address'] }} &bull; Telp: {{ $settings['store_phone'] }}</div>
            </td>
            <td style="text-align: right; vertical-align: middle; font-size: 9px; color: #666;">
                Dicetak: {{ now()->format('d/m/Y H:i') }}<br>
                Petugas: {{ auth()->user()->name ?? 'Admin' }}
            </td>
        </tr>
    </table>

    <div class="report-title">Laporan Rekapitulasi Penjualan</div>
    <div class="report-meta">
        Periode: <strong>{{ $startDate->format('d/m/Y') }}</strong> s/d <strong>{{ $endDate->format('d/m/Y') }}</strong>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 15%;">No. Invoice</th>
                <th style="width: 12%;">Tanggal</th>
                <th style="width: 13%;">Kasir</th>
                <th style="width: 13%;">Pelanggan</th>
                <th style="width: 8%;" class="text-center">Metode</th>
                <th style="width: 12%;" class="text-right">Subtotal</th>
                <th style="width: 7%;" class="text-right">Diskon</th>
                <th style="width: 7%;" class="text-right">Ongkir</th>
                <th style="width: 11%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $idx => $s)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="fw-bold">{{ $s->invoice_number }}</td>
                    <td>{{ $s->transaction_date->format('d/m/Y H:i') }}</td>
                    <td>{{ $s->user?->name ?? 'Kasir' }}</td>
                    <td>{{ $s->customer?->name ?? 'Pelanggan Umum' }}</td>
                    <td class="text-center">{{ strtoupper($s->payment_method) }}</td>
                    <td class="text-right">Rp {{ number_format($s->subtotal, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $s->discount_amount > 0 ? '-Rp ' . number_format($s->discount_amount, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $s->shipping_cost > 0 ? '+Rp ' . number_format($s->shipping_cost, 0, ',', '.') : '-' }}</td>
                    <td class="text-right fw-bold">Rp {{ number_format($s->grand_total, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 15px; color: #888;">Tidak ada transaksi penjualan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="fw-bold" style="background-color: #f1f5f9;">
                <td colspan="6" class="text-right">TOTAL KESELURUHAN ({{ $summary['total_transactions'] }} Transaksi):</td>
                <td class="text-right">Rp {{ number_format($summary['total_subtotal'], 0, ',', '.') }}</td>
                <td class="text-right">-Rp {{ number_format($summary['total_discount'], 0, ',', '.') }}</td>
                <td class="text-right">+Rp {{ number_format($summary['total_shipping'] ?? 0, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($summary['total_grand_total'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <p style="margin-bottom: 50px;">Disiapkan Oleh,</p>
                <p class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">{{ auth()->user()->name ?? 'Kasir' }}</p>
                <span style="color: #666;">Petugas Kasir</span>
            </td>
            <td style="width: 50%;">
                <p style="margin-bottom: 50px;">Mengetahui,</p>
                <p class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">( .................................................. )</p>
                <span style="color: #666;">Manajer / Pemilik Toko</span>
            </td>
        </tr>
    </table>
</body>
</html>
