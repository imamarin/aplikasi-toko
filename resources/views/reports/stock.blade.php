@extends('layouts.app')

@section('title', 'Laporan Nilai Persediaan Stok')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1">Laporan Nilai Persediaan Stok</h4>
            <p class="text-muted small mb-0">Valuasi aset barang dagangan berdasarkan harga pokok pembelian (HPP) dan estimasi harga jual</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.stock.pdf', request()->all()) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
                <i class="bi bi-file-earmark-pdf"></i> Ekspor PDF
            </a>
            <button onclick="window.print()" class="btn btn-light border btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
                <i class="bi bi-printer me-1"></i> Print Laporan
            </button>
        </div>
    </div>

    <!-- Kop Header Toko (Khusus Tampilan Cetak / Print) -->
    <div class="d-none d-print-block mb-3 border-bottom pb-2">
        <table style="width: 100%; border: none; margin-bottom: 5px;">
            <tr style="border: none;">
                <td style="border: none; padding: 0;">
                    <h3 style="margin: 0; font-weight: bold; text-transform: uppercase;">{{ \App\Models\Setting::get('store_name', 'Kasir POS') }}</h3>
                    <div style="font-size: 9pt; color: #555;">{{ \App\Models\Setting::get('store_address', 'Jl. Toko') }} &bull; Telp: {{ \App\Models\Setting::get('store_phone', '-') }}</div>
                </td>
                <td style="border: none; padding: 0; text-align: right; font-size: 8.5pt; color: #555;">
                    Dicetak: {{ now()->format('d/m/Y H:i') }}<br>
                    Petugas: {{ auth()->user()->name }}
                </td>
            </tr>
        </table>
        <div style="text-align: center; margin-top: 8px;">
            <h4 style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">LAPORAN NILAI PERSEDIAAN STOK (VALUASI ASET)</h4>
        </div>
    </div>

    <!-- Summary Valuation Cards (Hanya di Layar Web) -->
    <div class="row g-3 mb-4 d-print-none">
        <div class="col-12 col-md-4">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Total Aset Modal Persediaan (HPP)</small>
                <h4 class="fw-bold text-dark mb-0">Rp {{ number_format($totalInventoryCogs, 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Total nilai uang terikat dalam stok</small>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Potensi Nilai Penjualan (Omset)</small>
                <h4 class="fw-bold text-primary mb-0">Rp {{ number_format($totalInventorySalesValue, 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Jika seluruh stok habis terjual</small>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Estimasi Potensi Keuntungan Kotor</small>
                <h4 class="fw-bold text-success mb-0">+ Rp {{ number_format($totalInventorySalesValue - $totalInventoryCogs, 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Selisih potensi omset dikurangi HPP</small>
            </div>
        </div>
    </div>

    <!-- Ringkasan Valuasi Format Tabel Cetak (Khusus Print) -->
    <div class="d-none d-print-block mb-2">
        <table>
            <thead>
                <tr>
                    <th style="width: 33.33%;">Total Aset Modal Persediaan (HPP)</th>
                    <th style="width: 33.33%;">Potensi Nilai Penjualan (Omset)</th>
                    <th style="width: 33.33%;">Estimasi Margin Keuntungan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold">Rp {{ number_format($totalInventoryCogs, 0, ',', '.') }}</td>
                    <td class="fw-bold">Rp {{ number_format($totalInventorySalesValue, 0, ',', '.') }}</td>
                    <td class="fw-bold">+ Rp {{ number_format($totalInventorySalesValue - $totalInventoryCogs, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Filter Kategori di Layar Web -->
    <div class="card card-custom p-3 mb-3 d-print-none">
        <form method="GET" action="{{ route('reports.stock') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted">Filter Berdasarkan Kategori</label>
                <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <a href="{{ route('reports.stock') }}" class="btn btn-light border btn-sm"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</a>
            </div>
        </form>
    </div>

    <!-- Products Valuation Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 5%;" class="text-center">#</th>
                        <th style="width: 13%;">Kode SKU</th>
                        <th style="width: 25%;">Nama Produk</th>
                        <th style="width: 14%;">Kategori</th>
                        <th style="width: 11%;" class="text-center">Stok Fisik</th>
                        <th style="width: 11%;" class="text-end">HPP Satuan</th>
                        <th style="width: 11%;" class="text-end">Harga Jual</th>
                        <th style="width: 15%;" class="text-end">Total Nilai HPP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $idx => $p)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td><span class="badge bg-light text-dark border font-monospace">{{ $p->sku }}</span></td>
                            <td class="fw-semibold text-dark">{{ $p->name }}</td>
                            <td>{{ $p->category?->name ?? '-' }}</td>
                            <td class="text-center fw-bold">{{ $p->stock }} {{ $p->unit?->short_name }}</td>
                            <td class="text-end text-muted">Rp {{ number_format($p->cost_price, 0, ',', '.') }}</td>
                            <td class="text-end text-muted">Rp {{ number_format($p->selling_price, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold text-dark">Rp {{ number_format($p->cost_price * $p->stock, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Belum ada data barang persediaan.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="7" class="text-end">TOTAL KESELURUHAN ASET MODAL (HPP):</td>
                        <td class="text-end text-dark">Rp {{ number_format($totalInventoryCogs, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td colspan="7" class="text-end">TOTAL POTENSI NILAI PENJUALAN:</td>
                        <td class="text-end text-primary">Rp {{ number_format($totalInventorySalesValue, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Signature Block (Khusus Print) -->
    <div class="d-none d-print-block mt-4">
        <table style="width: 100%; border: none;">
            <tr style="border: none;">
                <td style="width: 50%; border: none; text-align: center; padding-top: 20px;">
                    <p style="margin-bottom: 50px;">Petugas Pengelola Stok,</p>
                    <p class="fw-bold mb-0" style="text-decoration: underline;">{{ auth()->user()->name }}</p>
                    <small style="color: #666;">Petugas Gudang / Kasir</small>
                </td>
                <td style="width: 50%; border: none; text-align: center; padding-top: 20px;">
                    <p style="margin-bottom: 50px;">Menyetujui & Mengesahkan,</p>
                    <p class="fw-bold mb-0" style="text-decoration: underline;">( .................................................. )</p>
                    <small style="color: #666;">Owner / Manajer Toko</small>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
