@extends('layouts.app')

@section('title', 'Laporan Laba Rugi')

@section('content')
<div class="container-fluid px-0" style="max-width: 950px;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1">Laporan Laba Rugi (Profit & Loss)</h4>
            <p class="text-muted small mb-0">Analisis margin keuntungan kotor, HPP barang terjual, dan laba operasional bersih</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.profit-loss.pdf', request()->all()) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
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
            <h4 style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">LAPORAN LABA RUGI (PROFIT & LOSS)</h4>
            <div style="font-size: 9pt; color: #555; margin-top: 3px;">
                Periode: <strong>{{ $start->format('d/m/Y') }}</strong> s/d <strong>{{ $end->format('d/m/Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter Card (Hanya di Layar Web) -->
    <div class="card card-custom p-3 mb-4 d-print-none">
        <form method="GET" action="{{ route('reports.profit-loss') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
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
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted">Tanggal Selesai</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100">Filter</button>
                <a href="{{ route('reports.profit-loss') }}" class="btn btn-light border btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Income Statement Table -->
    <div class="card card-custom p-4 mb-4">
        <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-cash-stack text-primary me-2"></i> Laporan Laba Rugi Komprehensif</h5>

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Komponen Keuangan</th>
                        <th class="text-end" style="width: 250px;">Nominal (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Pendapatan -->
                    <tr class="table-light fw-bold">
                        <td colspan="2">1. PENDAPATAN OPERASIONAL</td>
                    </tr>
                    <tr>
                        <td class="ps-4">Subtotal Penjualan Kotor (Gross Sales)</td>
                        <td class="text-end fw-semibold">Rp {{ number_format($profitLoss['total_subtotal'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted">Potongan Diskon Penjualan (-)</td>
                        <td class="text-end text-danger">- Rp {{ number_format($profitLoss['total_discounts'], 0, ',', '.') }}</td>
                    </tr>
                    <tr class="fw-bold">
                        <td class="ps-4">Total Pendapatan Bersih Penjualan</td>
                        <td class="text-end text-dark">Rp {{ number_format($profitLoss['total_revenue'], 0, ',', '.') }}</td>
                    </tr>

                    <!-- HPP -->
                    <tr class="table-light fw-bold">
                        <td colspan="2">2. HARGA POKOK PENJUALAN (HPP / COGS)</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted">Total Modal Barang Terjual (Cost of Goods Sold) (-)</td>
                        <td class="text-end text-danger">- Rp {{ number_format($profitLoss['total_cogs'], 0, ',', '.') }}</td>
                    </tr>

                    <!-- Laba Kotor -->
                    <tr class="table-primary fw-bold fs-6">
                        <td>LABA KOTOR (GROSS PROFIT)</td>
                        <td class="text-end text-primary">Rp {{ number_format($profitLoss['gross_profit'], 0, ',', '.') }}</td>
                    </tr>

                    <!-- Beban Operasional -->
                    <tr class="table-light fw-bold">
                        <td colspan="2">3. BEBAN OPERASIONAL KAS</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-muted">Beban Operasional & Pengeluaran Kasir (-)</td>
                        <td class="text-end text-danger">- Rp {{ number_format($profitLoss['expenses'], 0, ',', '.') }}</td>
                    </tr>

                    <!-- Laba Bersih -->
                    <tr class="table-success fw-bold fs-5">
                        <td>LABA BERSIH OPERASIONAL (NET PROFIT)</td>
                        <td class="text-end text-success">Rp {{ number_format($profitLoss['net_profit'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top 10 Profit Contributors -->
    <div class="card card-custom p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-trophy text-warning me-2"></i> 10 Produk Kontributor Margin Keuntungan Terbesar</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>#</th>
                        <th>Nama Produk</th>
                        <th class="text-center">Qty Terjual</th>
                        <th class="text-end">Total Omset</th>
                        <th class="text-end">Total HPP</th>
                        <th class="text-end text-success fw-bold">Keuntungan Bersih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bestSellers as $idx => $item)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td class="fw-semibold text-dark">{{ $item->product_name }}</td>
                            <td class="text-center"><span class="badge bg-primary rounded-pill">{{ $item->total_qty }}</span></td>
                            <td class="text-end">Rp {{ number_format($item->total_sales, 0, ',', '.') }}</td>
                            <td class="text-end text-muted">Rp {{ number_format($item->total_cogs, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold text-success">+ Rp {{ number_format($item->total_profit, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada data kontributor laba untuk periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Signature Block (Khusus Print) -->
    <div class="d-none d-print-block mt-4">
        <table style="width: 100%; border: none;">
            <tr style="border: none;">
                <td style="width: 50%; border: none; text-align: center; padding-top: 20px;">
                    <p style="margin-bottom: 50px;">Dibuat Oleh,</p>
                    <p class="fw-bold mb-0" style="text-decoration: underline;">{{ auth()->user()->name }}</p>
                    <small style="color: #666;">Bagian Keuangan / Kasir</small>
                </td>
                <td style="width: 50%; border: none; text-align: center; padding-top: 20px;">
                    <p style="margin-bottom: 50px;">Menyetujui & Mengesahkan,</p>
                    <p class="fw-bold mb-0" style="text-decoration: underline;">( .................................................. )</p>
                    <small style="color: #666;">Owner / Pemilik Toko</small>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
