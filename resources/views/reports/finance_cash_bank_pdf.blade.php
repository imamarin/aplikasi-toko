<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan Cash & Transfer</title>
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.3; }
        .header-table { width: 100%; border-bottom: 2px solid #222; padding-bottom: 8px; margin-bottom: 12px; }
        .store-title { font-size: 16px; font-weight: bold; color: #111; text-transform: uppercase; }
        .store-sub { font-size: 9px; color: #555; margin-top: 2px; }
        .report-title { font-size: 13px; font-weight: bold; text-align: center; text-transform: uppercase; margin-top: 6px; margin-bottom: 3px; letter-spacing: 0.5px; }
        .report-meta { text-align: center; font-size: 9.5px; color: #555; margin-bottom: 15px; }
        table.summary-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.summary-table td { padding: 6px 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-size: 9.5px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.data-table th { background-color: #f1f5f9; color: #111; padding: 6px 5px; text-align: left; border: 1px solid #94a3b8; font-size: 9.5px; }
        table.data-table td { padding: 5px; border: 1px solid #cbd5e1; font-size: 9px; }
        table.data-table tr:nth-child(even) td { background-color: #fafafa; }
        .section-heading { font-size: 11px; font-weight: bold; margin-bottom: 6px; text-transform: uppercase; color: #334155; }
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

    <div class="report-title">Laporan Keuangan Cash & Transfer (Kas & Bank)</div>
    <div class="report-meta">
        Periode: <strong>{{ $startDate->format('d/m/Y') }}</strong> s/d <strong>{{ $endDate->format('d/m/Y') }}</strong>
    </div>

    <!-- Summary Balance -->
    <table class="summary-table">
        <tr>
            <td style="width: 25%;">Total Omset Penjualan: <strong>Rp {{ number_format($summary['total_sales'], 0, ',', '.') }}</strong></td>
            <td style="width: 25%;">Penerimaan Fisik Tunai (Cash): <strong>Rp {{ number_format($summary['cash_sales'], 0, ',', '.') }}</strong></td>
            <td style="width: 25%;">Penerimaan Bank (TF & QRIS): <strong>Rp {{ number_format($summary['total_non_cash_sales'], 0, ',', '.') }}</strong></td>
            <td style="width: 25%;">Posisi Bersih Kas Fisik (Laci): <strong>Rp {{ number_format($summary['net_cash_drawer'], 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <!-- 1. Rincian Penerimaan Penjualan per Metode Bayar -->
    <div class="section-heading">1. Rincian Penerimaan Penjualan per Metode Pembayaran</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;" class="text-center">#</th>
                <th style="width: 28%;">Metode Bayar</th>
                <th style="width: 24%;" class="text-center">Jalur Penerimaan</th>
                <th style="width: 14%;" class="text-center">Jumlah Transaksi</th>
                <th style="width: 16%;" class="text-right">Total Nominal</th>
                <th style="width: 12%;" class="text-right">Porsi (%)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totMethodTx = $salesByMethod->sum('total_transactions');
                $totMethodAmount = $salesByMethod->sum('total_amount');
            @endphp
            @forelse($salesByMethod as $idx => $m)
                @php
                    $percent = $totMethodAmount > 0 ? ($m->total_amount / $totMethodAmount) * 100 : 0;
                    $typeLabel = match($m->payment_method) {
                        'cash' => 'Fisik Laci Kasir',
                        'transfer' => 'Rekening Bank Toko',
                        'qris' => 'QRIS Merchant / E-Wallet',
                        default => 'Non-Tunai Lainnya',
                    };
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="fw-bold text-uppercase">{{ $m->payment_method }}</td>
                    <td class="text-center">{{ $typeLabel }}</td>
                    <td class="text-center">{{ number_format($m->total_transactions, 0, ',', '.') }} Transaksi</td>
                    <td class="text-right fw-bold">Rp {{ number_format($m->total_amount, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($percent, 1) }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 10px; color: #888;">Tidak ada catatan penerimaan penjualan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="fw-bold" style="background-color: #f1f5f9;">
                <td colspan="3" class="text-right">TOTAL PENERIMAAN:</td>
                <td class="text-center">{{ number_format($totMethodTx, 0, ',', '.') }} Transaksi</td>
                <td class="text-right">Rp {{ number_format($totMethodAmount, 0, ',', '.') }}</td>
                <td class="text-right">100.0%</td>
            </tr>
        </tfoot>
    </table>

    <!-- 2. Ringkasan Kas Operasional -->
    <div class="section-heading" style="margin-top: 15px;">2. Ringkasan Mutasi Kas Operasional Laci (In & Out)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;" class="text-center">#</th>
                <th style="width: 18%;">Waktu</th>
                <th style="width: 14%;" class="text-center">Tipe</th>
                <th style="width: 18%;">Kategori</th>
                <th style="width: 16%;" class="text-right">Nominal</th>
                <th style="width: 14%;">Petugas</th>
                <th style="width: 14%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cashLogs as $idx => $log)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $log->transaction_date ? \Carbon\Carbon::parse($log->transaction_date)->format('d/m/Y H:i') : '-' }}</td>
                    <td class="text-center fw-bold">
                        {{ $log->type === 'in' ? 'KAS MASUK' : 'KAS KELUAR' }}
                    </td>
                    <td>{{ ucfirst($log->category ?? 'Lain-lain') }}</td>
                    <td class="text-right fw-bold">
                        {{ $log->type === 'in' ? '+' : '-' }}Rp {{ number_format($log->amount, 0, ',', '.') }}
                    </td>
                    <td>{{ $log->user->name ?? 'Kasir' }}</td>
                    <td>{{ $log->notes ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 10px; color: #888;">Tidak ada transaksi kas masuk / kas keluar pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if($cashLogs->count() > 0)
            <tfoot>
                <tr class="fw-bold" style="background-color: #f1f5f9;">
                    <td colspan="4" class="text-right">REKAP MUTASI KAS:</td>
                    <td class="text-right">
                        +Rp {{ number_format($summary['cash_in'], 0, ',', '.') }}<br>
                        -Rp {{ number_format($summary['cash_out'], 0, ',', '.') }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <p style="margin-bottom: 50px;">Disiapkan Oleh,</p>
                <p class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">{{ auth()->user()->name ?? 'Admin' }}</p>
                <span style="color: #666;">Bagian Kasir / Keuangan</span>
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
