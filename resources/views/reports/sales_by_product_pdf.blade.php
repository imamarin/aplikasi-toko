<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan per Produk</title>
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.3; }
        .header-table { width: 100%; border-bottom: 2px solid #222; padding-bottom: 8px; margin-bottom: 12px; }
        .store-title { font-size: 16px; font-weight: bold; color: #111; text-transform: uppercase; }
        .store-sub { font-size: 9px; color: #555; margin-top: 2px; }
        .report-title { font-size: 13px; font-weight: bold; text-align: center; text-transform: uppercase; margin-top: 6px; margin-bottom: 3px; letter-spacing: 0.5px; }
        .report-meta { text-align: center; font-size: 9.5px; color: #555; margin-bottom: 15px; }
        table.summary-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.summary-table td { padding: 6px 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-size: 9.5px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.data-table th { background-color: #f1f5f9; color: #111; padding: 6px 5px; text-align: left; border: 1px solid #94a3b8; font-size: 9.5px; }
        table.data-table td { padding: 5px; border: 1px solid #cbd5e1; font-size: 9px; }
        table.data-table tr:nth-child(even) td { background-color: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .signature-table { width: 100%; margin-top: 30px; page-break-inside: avoid; }
        .signature-table td { text-align: center; vertical-align: top; font-size: 9.5px; }
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

    <div class="report-title">Laporan Penjualan per Item Produk</div>
    <div class="report-meta">
        Periode: <strong>{{ $startDate->format('d/m/Y') }}</strong> s/d <strong>{{ $endDate->format('d/m/Y') }}</strong>
    </div>

    <table class="summary-table">
        <tr>
            <td style="width: 25%;">Total Volume: <strong>{{ number_format($summary['overall_qty'], 0, ',', '.') }} Pcs</strong></td>
            <td style="width: 25%;">Total Omset: <strong>Rp {{ number_format($summary['overall_sales'], 0, ',', '.') }}</strong></td>
            <td style="width: 25%;">Total Modal HPP: <strong>Rp {{ number_format($summary['overall_cogs'], 0, ',', '.') }}</strong></td>
            <td style="width: 25%;">Keuntungan Kotor: <strong style="color: #047857;">Rp {{ number_format($summary['overall_profit'], 0, ',', '.') }} ({{ number_format($summary['overall_margin'], 1) }}%)</strong></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 12%;">Kode SKU</th>
                <th style="width: 26%;">Nama Produk</th>
                <th style="width: 14%;">Kategori</th>
                <th style="width: 10%;" class="text-center">Terjual</th>
                <th style="width: 12%;" class="text-right">Total Omset</th>
                <th style="width: 11%;" class="text-right">Total HPP</th>
                <th style="width: 11%;" class="text-right">Laba Kotor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $idx => $p)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td style="font-family: monospace;">{{ $p->sku }}</td>
                    <td class="fw-bold">{{ $p->product_name }}</td>
                    <td>{{ $p->category_name }}</td>
                    <td class="text-center fw-bold">{{ number_format($p->total_qty, 0, ',', '.') }} {{ $p->unit_name }}</td>
                    <td class="text-right">Rp {{ number_format($p->total_sales, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($p->total_cogs, 0, ',', '.') }}</td>
                    <td class="text-right fw-bold" style="color: #047857;">
                        Rp {{ number_format($p->total_profit, 0, ',', '.') }}
                        <div style="font-size: 7.5px; color: #555;">({{ number_format($p->margin_percentage, 1) }}%)</div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px; color: #888;">Belum ada transaksi penjualan produk pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="fw-bold" style="background-color: #f1f5f9;">
                <td colspan="4" class="text-end">TOTAL KESELURUHAN:</td>
                <td class="text-center">{{ number_format($summary['overall_qty'], 0, ',', '.') }} Pcs</td>
                <td class="text-right">Rp {{ number_format($summary['overall_sales'], 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($summary['overall_cogs'], 0, ',', '.') }}</td>
                <td class="text-right" style="color: #047857;">Rp {{ number_format($summary['overall_profit'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <p style="margin-bottom: 50px;">Disiapkan Oleh,</p>
                <p class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">{{ auth()->user()->name ?? 'Admin' }}</p>
                <span style="color: #666;">Petugas Kasir / Administrasi</span>
            </td>
            <td style="width: 50%;">
                <p style="margin-bottom: 50px;">Mengetahui & Menyetujui,</p>
                <p class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">( .................................................. )</p>
                <span style="color: #666;">Owner / Manajer Toko</span>
            </td>
        </tr>
    </table>
</body>
</html>
