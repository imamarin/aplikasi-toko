@extends('layouts.app')

@section('title', 'Laporan Stok (Penyusutan)')

@section('content')
<div class="container-fluid px-0">
    @include('reports.partials.nav')

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1">Laporan Penyusutan & Kerugian Stok</h4>
            <p class="text-muted small mb-0">Audit kehilangan, kerusakan, barang kadaluarsa, dan selisih kurang hasil opname fisik</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.stock-shrinkage.pdf', request()->all()) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
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
            <h4 style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">LAPORAN PENYUSUTAN & KERUGIAN STOK BARANG</h4>
            <div style="font-size: 9pt; color: #555; margin-top: 3px;">
                Periode: <strong>{{ $start->format('d/m/Y') }}</strong> s/d <strong>{{ $end->format('d/m/Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter Card (Hanya di Layar Web) -->
    <div class="card card-custom p-3 mb-4 d-print-none">
        <form method="GET" action="{{ route('reports.stock-shrinkage') }}" class="row g-2 align-items-end">
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
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted">Kategori Produk</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Kata Kunci / Catatan</label>
                <input type="text" name="reason" class="form-control form-control-sm" placeholder="Rusak / hilang..." value="{{ request('reason') }}">
            </div>
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100" title="Terapkan Filter"><i class="bi bi-filter"></i></button>
                <a href="{{ route('reports.stock-shrinkage') }}" class="btn btn-light border btn-sm" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards (Hanya di Layar Web) -->
    <div class="row g-3 mb-4 d-print-none">
        <div class="col-12 col-md-4">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Frekuensi Kejadian Penyusutan</small>
                <h4 class="fw-bold text-dark mb-0">{{ $summary['total_events'] }} Catatan</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Total insiden opname & penyesuaian berkurang</small>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Total Unit Fisik Menyusut / Hilang</small>
                <h4 class="fw-bold text-danger mb-0">{{ number_format($summary['total_items'], 0, ',', '.') }} Pcs</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Total kuantitas barang yang terbuang/hilang</small>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card card-custom p-3 bg-white border-danger">
                <small class="text-danger fw-semibold d-block">Total Nilai Kerugian Toko (HPP)</small>
                <h4 class="fw-bold text-danger mb-0">Rp {{ number_format($summary['total_loss'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Valuasi kerugian modal pokok inventaris</small>
            </div>
        </div>
    </div>

    <!-- Ringkasan Format Tabel Cetak (Khusus Print) -->
    <div class="d-none d-print-block mb-2">
        <table>
            <thead>
                <tr>
                    <th style="width: 33.33%;">Total Kejadian Penyusutan</th>
                    <th style="width: 33.33%;">Total Unit Fisik Menyusut</th>
                    <th style="width: 33.33%;">Total Nilai Kerugian HPP</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold">{{ $summary['total_events'] }} Transaksi</td>
                    <td class="fw-bold text-danger">{{ number_format($summary['total_items'], 0, ',', '.') }} Pcs</td>
                    <td class="fw-bold text-danger">Rp {{ number_format($summary['total_loss'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Shrinkage Records Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 5%;" class="text-center">#</th>
                        <th style="width: 14%;">Tanggal & Waktu</th>
                        <th style="width: 12%;">Kode SKU</th>
                        <th style="width: 22%;">Nama Produk</th>
                        <th style="width: 12%;">Kategori</th>
                        <th style="width: 9%;" class="text-center">Penyusutan</th>
                        <th style="width: 12%;" class="text-end">HPP Satuan</th>
                        <th style="width: 14%;" class="text-end">Total Kerugian (HPP)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $idx => $item)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ $item->created_at->format('d/m/Y H:i') }}</div>
                                <small class="text-muted">{{ $item->notes ?? 'Penyesuaian stok' }}</small>
                            </td>
                            <td><span class="badge bg-light text-dark border font-monospace">{{ $item->product?->sku ?? '-' }}</span></td>
                            <td class="fw-bold text-dark">{{ $item->product?->name ?? 'Produk Dihapus' }}</td>
                            <td>{{ $item->product?->category?->name ?? '-' }}</td>
                            <td class="text-center fw-bold text-danger">-{{ $item->qty_out }} {{ $item->product?->unit?->short_name ?? 'pcs' }}</td>
                            <td class="text-end text-muted">Rp {{ number_format($item->cost_price, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold text-danger">Rp {{ number_format($item->total_loss, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Tidak ada data penyusutan atau kehilangan stok pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="5" class="text-end">TOTAL KESELURUHAN KERUGIAN PENYUSUTAN:</td>
                        <td class="text-center text-danger">{{ number_format($summary['total_items'], 0, ',', '.') }} Pcs</td>
                        <td class="text-end">-</td>
                        <td class="text-end text-danger">Rp {{ number_format($summary['total_loss'], 0, ',', '.') }}</td>
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
                    <p style="margin-bottom: 50px;">Petugas Gudang / Pemeriksa,</p>
                    <p class="fw-bold mb-0" style="text-decoration: underline;">{{ auth()->user()->name }}</p>
                    <small style="color: #666;">Petugas Inventaris</small>
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
