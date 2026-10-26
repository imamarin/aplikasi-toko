@extends('layouts.app')

@section('title', 'Laporan Penjualan per Item Produk')

@section('content')
<div class="container-fluid px-0">
    @include('reports.partials.nav')

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1">Laporan Penjualan per Item Produk</h4>
            <p class="text-muted small mb-0">Rincian performa penjualan, volume transaksi, modal pokok, dan margin keuntungan per produk</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.sales-by-product.pdf', request()->all()) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
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
            <h4 style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">LAPORAN PENJUALAN PER ITEM PRODUK</h4>
            <div style="font-size: 9pt; color: #555; margin-top: 3px;">
                Periode: <strong>{{ $start->format('d/m/Y') }}</strong> s/d <strong>{{ $end->format('d/m/Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter Card (Hanya di Layar Web) -->
    <div class="card card-custom p-3 mb-4 d-print-none">
        <form method="GET" action="{{ route('reports.sales-by-product') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Preset Periode</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hari Ini</option>
                    <option value="yesterday" {{ $period === 'yesterday' ? 'selected' : '' }}>Kemarin</option>
                    <option value="this_week" {{ $period === 'this_week' ? 'selected' : '' }}>Minggu Ini</option>
                    <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                    <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>Tahun Ini</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Kustom</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Mulai</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Sampai</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Kategori Produk</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Urutkan Berdasarkan</label>
                <select name="sort_by" class="form-select form-select-sm">
                    <option value="qty_desc" {{ request('sort_by') == 'qty_desc' ? 'selected' : '' }}>Terlaris (Qty Terbanyak)</option>
                    <option value="sales_desc" {{ request('sort_by') == 'sales_desc' ? 'selected' : '' }}>Omset Tertinggi</option>
                    <option value="profit_desc" {{ request('sort_by') == 'profit_desc' ? 'selected' : '' }}>Keuntungan Tertinggi</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100" title="Terapkan Filter"><i class="bi bi-filter"></i> Filter</button>
                <a href="{{ route('reports.sales-by-product') }}" class="btn btn-light border btn-sm" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards (Hanya di Layar Web) -->
    <div class="row g-3 mb-4 d-print-none">
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Total Volume Terjual</small>
                <h4 class="fw-bold text-dark mb-0">{{ number_format($summary['overall_qty'], 0, ',', '.') }} Pcs</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Dari {{ $summary['total_products_count'] }} jenis produk aktif</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Total Omset Penjualan</small>
                <h4 class="fw-bold text-primary mb-0">Rp {{ number_format($summary['overall_sales'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Penjualan bersih setelah diskon item</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Total Modal Pokok (HPP)</small>
                <h4 class="fw-bold text-dark mb-0">Rp {{ number_format($summary['overall_cogs'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Modal barang yang telah terjual</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white border-success">
                <small class="text-success fw-semibold d-block">Total Keuntungan Kotor</small>
                <h4 class="fw-bold text-success mb-0">+Rp {{ number_format($summary['overall_profit'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Margin rata-rata: {{ number_format($summary['overall_margin'], 1) }}%</small>
            </div>
        </div>
    </div>

    <!-- Ringkasan Format Tabel Cetak (Khusus Print) -->
    <div class="d-none d-print-block mb-2">
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Total Volume Terjual</th>
                    <th style="width: 25%;">Total Omset Penjualan</th>
                    <th style="width: 25%;">Total Modal HPP</th>
                    <th style="width: 25%;">Total Keuntungan Kotor (Margin)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold">{{ number_format($summary['overall_qty'], 0, ',', '.') }} Pcs</td>
                    <td class="fw-bold">Rp {{ number_format($summary['overall_sales'], 0, ',', '.') }}</td>
                    <td class="fw-bold">Rp {{ number_format($summary['overall_cogs'], 0, ',', '.') }}</td>
                    <td class="fw-bold text-success">+Rp {{ number_format($summary['overall_profit'], 0, ',', '.') }} ({{ number_format($summary['overall_margin'], 1) }}%)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Products Sales Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 4%;" class="text-center">#</th>
                        <th style="width: 12%;">Kode SKU</th>
                        <th style="width: 24%;">Nama Produk</th>
                        <th style="width: 12%;">Kategori</th>
                        <th style="width: 10%;" class="text-center">Terjual</th>
                        <th style="width: 13%;" class="text-end">Total Omset</th>
                        <th style="width: 12%;" class="text-end">Total HPP</th>
                        <th style="width: 13%;" class="text-end">Laba Kotor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $idx => $p)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td><span class="badge bg-light text-dark border font-monospace">{{ $p->sku }}</span></td>
                            <td class="fw-bold text-dark">{{ $p->product_name }}</td>
                            <td>{{ $p->category_name }}</td>
                            <td class="text-center fw-bold">{{ number_format($p->total_qty, 0, ',', '.') }} {{ $p->unit_name }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format($p->total_sales, 0, ',', '.') }}</td>
                            <td class="text-end text-muted">Rp {{ number_format($p->total_cogs, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold text-success">
                                +Rp {{ number_format($p->total_profit, 0, ',', '.') }}
                                <div class="small text-muted" style="font-size: 0.7rem;">({{ number_format($p->margin_percentage, 1) }}%)</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Belum ada transaksi penjualan produk pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end">TOTAL KESELURUHAN PRODUK TERJUAL:</td>
                        <td class="text-center">{{ number_format($summary['overall_qty'], 0, ',', '.') }} Pcs</td>
                        <td class="text-end text-primary">Rp {{ number_format($summary['overall_sales'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($summary['overall_cogs'], 0, ',', '.') }}</td>
                        <td class="text-end text-success">+Rp {{ number_format($summary['overall_profit'], 0, ',', '.') }}</td>
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
                    <p style="margin-bottom: 50px;">Disiapkan Oleh,</p>
                    <p class="fw-bold mb-0" style="text-decoration: underline;">{{ auth()->user()->name }}</p>
                    <small style="color: #666;">Petugas Administrasi / Kasir</small>
                </td>
                <td style="width: 50%; border: none; text-align: center; padding-top: 20px;">
                    <p style="margin-bottom: 50px;">Mengetahui & Menyetujui,</p>
                    <p class="fw-bold mb-0" style="text-decoration: underline;">( .................................................. )</p>
                    <small style="color: #666;">Owner / Manajer Toko</small>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
