<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pembelian Barang (PO)</title>
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

    <div class="report-title">Laporan Pembelian Barang & Hutang Supplier</div>
    <div class="report-meta">
        Periode: <strong>{{ $startDate->format('d/m/Y') }}</strong> s/d <strong>{{ $endDate->format('d/m/Y') }}</strong>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 18%;">No. PO Pembelian</th>
                <th style="width: 14%;">Tanggal Pembelian</th>
                <th style="width: 20%;">Supplier / Distributor</th>
                <th style="width: 12%;" class="text-center">Status Bayar</th>
                <th style="width: 16%;" class="text-right">Total Belanja</th>
                <th style="width: 16%;" class="text-right">Jumlah Dibayar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $idx => $p)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="fw-bold">{{ $p->purchase_number }}</td>
                    <td>{{ $p->purchase_date->format('d/m/Y') }}</td>
                    <td>{{ $p->supplier?->name ?? 'Supplier Umum' }}</td>
                    <td class="text-center">{{ strtoupper($p->payment_status) }}</td>
                    <td class="text-right fw-bold">Rp {{ number_format($p->grand_total, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($p->paid_amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #888;">Tidak ada data pembelian pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="fw-bold" style="background-color: #f1f5f9;">
                <td colspan="5" class="text-right">TOTAL KESELURUHAN ({{ $purchases->count() }} Faktur):</td>
                <td class="text-right">Rp {{ number_format($totalPurchases, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalPaid, 0, ',', '.') }}</td>
            </tr>
            <tr class="fw-bold" style="background-color: #f8fafc;">
                <td colspan="5" class="text-right">SISA HUTANG BELUM DIBAYAR:</td>
                <td colspan="2" class="text-right" style="color: #b91c1c;">Rp {{ number_format($totalPurchases - $totalPaid, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <p style="margin-bottom: 50px;">Bagian Logistik / Pembelian,</p>
                <p class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">{{ auth()->user()->name ?? 'Admin' }}</p>
                <span style="color: #666;">Petugas Input</span>
            </td>
            <td style="width: 50%;">
                <p style="margin-bottom: 50px;">Menyetujui,</p>
                <p class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">( .................................................. )</p>
                <span style="color: #666;">Manajer Keuangan / Pemilik</span>
            </td>
        </tr>
    </table>
</body>
</html>
