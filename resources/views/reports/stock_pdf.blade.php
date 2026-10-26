<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Nilai Persediaan Stok</title>
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
        
        .valuation-summary {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .valuation-summary td {
            padding: 8px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            width: 33.33%;
        }
        .val-label {
            font-size: 8.5px;
            color: #64748b;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }
        .val-num {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
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

    <div class="report-title">Laporan Nilai Persediaan Stok (Valuasi Aset)</div>
    <div class="report-meta">
        Posisi Data Stok: <strong>{{ now()->format('d/m/Y H:i') }}</strong>
    </div>

    <!-- Summary Box -->
    <table class="valuation-summary">
        <tr>
            <td>
                <span class="val-label">Total Aset Modal Persediaan (HPP)</span>
                <span class="val-num">Rp {{ number_format($totalInventoryCogs, 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="val-label">Potensi Nilai Penjualan (Omset)</span>
                <span class="val-num" style="color: #2563eb;">Rp {{ number_format($totalInventorySalesValue, 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="val-label">Estimasi Potensi Margin Keuntungan</span>
                <span class="val-num" style="color: #059669;">+ Rp {{ number_format($totalInventorySalesValue - $totalInventoryCogs, 0, ',', '.') }}</span>
            </td>
        </tr>
    </table>

    <!-- Inventory Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 12%;">Kode SKU</th>
                <th style="width: 24%;">Nama Produk</th>
                <th style="width: 12%;">Kategori</th>
                <th style="width: 10%;" class="text-center">Stok Fisik</th>
                <th style="width: 12%;" class="text-right">HPP Satuan</th>
                <th style="width: 12%;" class="text-right">Harga Jual</th>
                <th style="width: 14%;" class="text-right">Total Nilai HPP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $idx => $p)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td style="font-family: monospace;">{{ $p->sku }}</td>
                    <td class="fw-bold">{{ $p->name }}</td>
                    <td>{{ $p->category?->name ?? '-' }}</td>
                    <td class="text-center fw-bold">{{ $p->stock }} {{ $p->unit?->short_name }}</td>
                    <td class="text-right">Rp {{ number_format($p->cost_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($p->selling_price, 0, ',', '.') }}</td>
                    <td class="text-right fw-bold">Rp {{ number_format($p->cost_price * $p->stock, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px; color: #888;">Belum ada data barang persediaan.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="fw-bold" style="background-color: #f1f5f9;">
                <td colspan="7" class="text-right">TOTAL KESELURUHAN ASET MODAL (HPP):</td>
                <td class="text-right">Rp {{ number_format($totalInventoryCogs, 0, ',', '.') }}</td>
            </tr>
            <tr class="fw-bold" style="background-color: #f8fafc;">
                <td colspan="7" class="text-right">TOTAL POTENSI NILAI PENJUALAN KASIR:</td>
                <td class="text-right" style="color: #2563eb;">Rp {{ number_format($totalInventorySalesValue, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <p style="margin-bottom: 50px;">Petugas Gudang / Inventaris,</p>
                <p class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">{{ auth()->user()->name ?? 'Admin' }}</p>
                <span style="color: #666;">Pengelola Stok</span>
            </td>
            <td style="width: 50%;">
                <p style="margin-bottom: 50px;">Menyetujui,</p>
                <p class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">( .................................................. )</p>
                <span style="color: #666;">Owner / Manajer Toko</span>
            </td>
        </tr>
    </table>
</body>
</html>
