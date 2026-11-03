<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pendapatan</title>
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9.5px; color: #111; line-height: 1.35; }
        .header-table { width: 100%; border-bottom: 2px solid #222; padding-bottom: 8px; margin-bottom: 10px; }
        .store-title { font-size: 16px; font-weight: bold; color: #111; text-transform: uppercase; }
        .store-sub { font-size: 9px; color: #555; margin-top: 2px; }
        .report-title { font-size: 14px; font-weight: bold; text-align: center; text-transform: uppercase; margin-top: 4px; margin-bottom: 2px; letter-spacing: 0.5px; }
        .report-meta { text-align: center; font-size: 9.5px; color: #555; margin-bottom: 12px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.data-table th { background-color: #3b7ddd; color: #ffffff; padding: 7px 5px; text-align: center; border: 1px solid #1e293b; font-size: 9px; font-weight: bold; }
        table.data-table td { padding: 5px 6px; border: 1px solid #64748b; font-size: 9px; }
        table.data-table tr:nth-child(even) td { background-color: #f8fafc; }
        table.data-table tfoot td { background-color: #e2e8f0; font-weight: bold; border: 1px solid #1e293b; padding: 6px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .signature-table { width: 100%; margin-top: 25px; page-break-inside: avoid; }
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

    <div class="report-title">PENDAPATAN</div>
    <div class="report-meta">
        Periode: <strong>{{ $startDate->format('d-m-Y') }}</strong> s/d <strong>{{ $endDate->format('d-m-Y') }}</strong>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">NO</th>
                <th style="width: 10%;">TANGGAL</th>
                <th style="width: 11%;">PENJUALAN</th>
                <th style="width: 12%;">PEMBAYARAN<br>REAL KIOS</th>
                <th style="width: 10%;">CASH</th>
                <th style="width: 10%;">TF</th>
                <th style="width: 10%;">QRIS</th>
                <th style="width: 10%;">CREDIT</th>
                <th style="width: 11%;">MODAL</th>
                <th style="width: 12%;">LABA KOTOR</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $idx => $row)
                @php
                    $rowDate = \Carbon\Carbon::parse($row->date);
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center">{{ $rowDate->format('d-m-Y') }}</td>
                    <td class="text-right">Rp{{ number_format($row->penjualan, 0, ',', '.') }}</td>
                    <td class="text-right">Rp{{ number_format($row->pembayaran_real_kios, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $row->cash > 0 ? 'Rp' . number_format($row->cash, 0, ',', '.') : '' }}</td>
                    <td class="text-right">{{ $row->tf > 0 ? 'Rp' . number_format($row->tf, 0, ',', '.') : '' }}</td>
                    <td class="text-right">{{ $row->qris > 0 ? 'Rp' . number_format($row->qris, 0, ',', '.') : '' }}</td>
                    <td class="text-right">{{ $row->credit > 0 ? 'Rp' . number_format($row->credit, 0, ',', '.') : '' }}</td>
                    <td class="text-right">Rp{{ number_format($row->modal, 0, ',', '.') }}</td>
                    <td class="text-right fw-bold">Rp{{ number_format($row->laba_kotor, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 15px; color: #777;">Belum ada data transaksi pendapatan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if($records->count() > 0)
        <tfoot>
            <tr>
                <td colspan="2" class="text-center">TOTAL</td>
                <td class="text-right">Rp{{ number_format($summary['total_penjualan'], 0, ',', '.') }}</td>
                <td class="text-right">Rp{{ number_format($summary['total_pembayaran_real_kios'], 0, ',', '.') }}</td>
                <td class="text-right">{{ $summary['total_cash'] > 0 ? 'Rp' . number_format($summary['total_cash'], 0, ',', '.') : '' }}</td>
                <td class="text-right">{{ $summary['total_tf'] > 0 ? 'Rp' . number_format($summary['total_tf'], 0, ',', '.') : '' }}</td>
                <td class="text-right">{{ $summary['total_qris'] > 0 ? 'Rp' . number_format($summary['total_qris'], 0, ',', '.') : '' }}</td>
                <td class="text-right">{{ $summary['total_credit'] > 0 ? 'Rp' . number_format($summary['total_credit'], 0, ',', '.') : '' }}</td>
                <td class="text-right">Rp{{ number_format($summary['total_modal'], 0, ',', '.') }}</td>
                <td class="text-right">Rp{{ number_format($summary['total_laba_kotor'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <p style="margin-bottom: 50px;">Disiapkan Oleh,</p>
                <p class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">{{ auth()->user()->name ?? 'Admin' }}</p>
                <span style="color: #666;">Bagian Keuangan / Kasir</span>
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

