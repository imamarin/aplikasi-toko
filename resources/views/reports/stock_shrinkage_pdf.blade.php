<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penyusutan Stok</title>
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
        .text-danger { color: #b91c1c; }
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

    <div class="report-title">Laporan Penyusutan & Kerugian Stok Barang</div>
    <div class="report-meta">
        Periode: <strong>{{ $startDate->format('d/m/Y') }}</strong> s/d <strong>{{ $endDate->format('d/m/Y') }}</strong>
    </div>

    <table class="summary-table">
        <tr>
            <td style="width: 33.33%;">Total Kejadian Penyusutan: <strong>{{ $summary['total_events'] }} Catatan</strong></td>
            <td style="width: 33.33%;">Total Unit Fisik Menyusut: <strong class="text-danger">{{ number_format($summary['total_items'], 0, ',', '.') }} Pcs</strong></td>
            <td style="width: 33.33%;">Total Kerugian Modal (HPP): <strong class="text-danger">Rp {{ number_format($summary['total_loss'], 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 14%;">Tanggal & Waktu</th>
                <th style="width: 12%;">Kode SKU</th>
                <th style="width: 24%;">Nama Produk</th>
                <th style="width: 12%;">Kategori</th>
                <th style="width: 10%;" class="text-center">Qty Kurang</th>
                <th style="width: 12%;" class="text-right">HPP Satuan</th>
                <th style="width: 12%;" class="text-right">Kerugian HPP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $item->created_at->format('d/m/Y H:i') }}<br><small style="color: #666;">{{ $item->notes ?? 'Penyesuaian' }}</small></td>
                    <td style="font-family: monospace;">{{ $item->product?->sku ?? '-' }}</td>
                    <td class="fw-bold">{{ $item->product?->name ?? 'Produk Dihapus' }}</td>
                    <td>{{ $item->product?->category?->name ?? '-' }}</td>
                    <td class="text-center fw-bold text-danger">-{{ $item->qty_out }} {{ $item->product?->unit?->short_name ?? 'pcs' }}</td>
                    <td class="text-right">Rp {{ number_format($item->cost_price, 0, ',', '.') }}</td>
                    <td class="text-right fw-bold text-danger">Rp {{ number_format($item->total_loss, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px; color: #888;">Tidak ada data penyusutan stok pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="fw-bold" style="background-color: #f1f5f9;">
                <td colspan="5" class="text-right">TOTAL KERUGIAN PENYUSUTAN:</td>
                <td class="text-center text-danger">{{ number_format($summary['total_items'], 0, ',', '.') }} Pcs</td>
                <td class="text-right">-</td>
                <td class="text-right text-danger">Rp {{ number_format($summary['total_loss'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <p style="margin-bottom: 50px;">Petugas Gudang / Pemeriksa,</p>
                <p class="fw-bold" style="text-decoration: underline; margin-bottom: 2px;">{{ auth()->user()->name ?? 'Admin' }}</p>
                <span style="color: #666;">Petugas Inventaris</span>
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
