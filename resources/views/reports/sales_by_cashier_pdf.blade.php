<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan per Kasir</title>
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

    <div class="report-title">Laporan Penjualan per Kasir (Produktivitas & Omset)</div>
    <div class="report-meta">
        Periode: <strong>{{ $startDate->format('d/m/Y') }}</strong> s/d <strong>{{ $endDate->format('d/m/Y') }}</strong>
    </div>

    <table class="summary-table">
        <tr>
            <td style="width: 25%;">Kasir Bertransaksi: <strong>{{ number_format($summary['total_cashiers_count'], 0, ',', '.') }} Staf</strong></td>
            <td style="width: 25%;">Total Transaksi Dilayani: <strong>{{ number_format($summary['overall_transactions'], 0, ',', '.') }} Nota</strong></td>
            <td style="width: 25%;">Total Pencapaian Omset: <strong>Rp {{ number_format($summary['overall_turnover'], 0, ',', '.') }}</strong></td>
            <td style="width: 25%;">Rata-rata Nilai / Nota: <strong>Rp {{ number_format($summary['avg_store_basket_size'], 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 25%;">Nama Kasir / Petugas</th>
                <th style="width: 14%;" class="text-center">Jabatan / Role</th>
                <th style="width: 14%;" class="text-center">Jumlah Transaksi</th>
                <th style="width: 14%;" class="text-right">Subtotal Kotor</th>
                <th style="width: 12%;" class="text-right">Diskon</th>
                <th style="width: 16%;" class="text-right">Total Omset Bersih</th>
                <th style="width: 10%;" class="text-right">Kontribusi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cashiers as $idx => $c)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <span class="fw-bold">{{ $c->cashier_name }}</span>
                        <br><span style="font-size: 8px; color: #666;">{{ '@' . $c->cashier_username }}</span>
                    </td>
                    <td class="text-center">{{ $c->cashier_role }}</td>
                    <td class="text-center">{{ number_format($c->total_transactions, 0, ',', '.') }} Nota</td>
                    <td class="text-right">Rp {{ number_format($c->total_subtotal, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $c->total_discount > 0 ? '-Rp ' . number_format($c->total_discount, 0, ',', '.') : '-' }}</td>
                    <td class="text-right fw-bold">Rp {{ number_format($c->total_grand_total, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($c->contribution_percentage, 1) }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px; color: #888;">Belum ada catatan penjualan per kasir pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if($cashiers->count() > 0)
            <tfoot>
                <tr class="fw-bold" style="background-color: #f1f5f9;">
                    <td colspan="3" class="text-right">TOTAL KESELURUHAN:</td>
                    <td class="text-center">{{ number_format($summary['overall_transactions'], 0, ',', '.') }} Nota</td>
                    <td class="text-right">Rp {{ number_format($cashiers->sum('total_subtotal'), 0, ',', '.') }}</td>
                    <td class="text-right">-Rp {{ number_format($cashiers->sum('total_discount'), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($summary['overall_turnover'], 0, ',', '.') }}</td>
                    <td class="text-right">100.0%</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <p style="margin-bottom: 50px;">Disiapkan Oleh,</p>
                <p class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">{{ auth()->user()->name ?? 'Admin' }}</p>
                <span style="color: #666;">Supervisor Kasir</span>
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
