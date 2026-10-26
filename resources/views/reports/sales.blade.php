@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1">Laporan Penjualan</h4>
            <p class="text-muted small mb-0">Rekapitulasi omset, potongan diskon, dan transaksi kasir berdasarkan periode</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.sales.pdf', request()->all()) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
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
            <h4 style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">LAPORAN REKAPITULASI PENJUALAN</h4>
            <div style="font-size: 9pt; color: #555; margin-top: 3px;">
                Periode: <strong>{{ $startDate->format('d/m/Y') }}</strong> s/d <strong>{{ $endDate->format('d/m/Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter Card (Hanya di Layar Web) -->
    <div class="card card-custom p-3 mb-4 d-print-none">
        <form method="GET" action="{{ route('reports.sales') }}" class="row g-2 align-items-end">
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
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Sampai</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted">Kasir Pelaksana</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">Semua Kasir</option>
                    @foreach($cashiers as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100">Terapkan Filter</button>
                <a href="{{ route('reports.sales') }}" class="btn btn-light border btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards (Hanya di Layar Web) -->
    <div class="row g-3 mb-4 d-print-none">
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Total Transaksi</small>
                <h4 class="fw-bold text-dark mb-0">{{ $summary['total_transactions'] }} Nota</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Subtotal Penjualan</small>
                <h4 class="fw-bold text-dark mb-0">Rp {{ number_format($summary['total_subtotal'], 0, ',', '.') }}</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Potongan Diskon</small>
                <h4 class="fw-bold text-danger mb-0">-Rp {{ number_format($summary['total_discount'], 0, ',', '.') }}</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white border-primary">
                <small class="text-primary fw-semibold d-block">Grand Total Omset</small>
                <h4 class="fw-bold text-primary mb-0">Rp {{ number_format($summary['total_grand_total'], 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>

    <!-- Ringkasan Penjualan Format Tabel Cetak (Khusus Print) -->
    <div class="d-none d-print-block mb-2">
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Total Transaksi</th>
                    <th style="width: 25%;">Subtotal Penjualan</th>
                    <th style="width: 25%;">Potongan Diskon</th>
                    <th style="width: 25%;">Grand Total Omset</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold">{{ $summary['total_transactions'] }} Nota</td>
                    <td class="fw-bold">Rp {{ number_format($summary['total_subtotal'], 0, ',', '.') }}</td>
                    <td class="fw-bold text-danger">-Rp {{ number_format($summary['total_discount'], 0, ',', '.') }}</td>
                    <td class="fw-bold">Rp {{ number_format($summary['total_grand_total'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Sales Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 5%;" class="text-center">#</th>
                        <th style="width: 15%;">No. Invoice</th>
                        <th style="width: 13%;">Tanggal Transaksi</th>
                        <th style="width: 13%;">Kasir</th>
                        <th style="width: 14%;">Pelanggan</th>
                        <th style="width: 10%;" class="text-center">Metode Bayar</th>
                        <th style="width: 13%;" class="text-end">Subtotal</th>
                        <th style="width: 7%;" class="text-end">Diskon</th>
                        <th style="width: 13%;" class="text-end">Grand Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $index => $s)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <a href="{{ route('sales.show', $s->id) }}" class="fw-bold text-primary font-monospace text-decoration-none">
                                    {{ $s->invoice_number }}
                                </a>
                            </td>
                            <td>{{ $s->transaction_date->format('d/m/Y H:i') }}</td>
                            <td>{{ $s->user?->name ?? 'Kasir' }}</td>
                            <td>{{ $s->customer?->name ?? 'Umum' }}</td>
                            <td class="text-center"><span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $s->payment_method }}</span></td>
                            <td class="text-end">Rp {{ number_format($s->subtotal, 0, ',', '.') }}</td>
                            <td class="text-end text-muted">{{ $s->discount_amount > 0 ? '-Rp ' . number_format($s->discount_amount, 0, ',', '.') : '-' }}</td>
                            <td class="text-end fw-bold text-dark">Rp {{ number_format($s->grand_total, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">Tidak ada transaksi penjualan pada rentang periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="6" class="text-end">TOTAL KESELURUHAN ({{ $summary['total_transactions'] }} Transaksi):</td>
                        <td class="text-end">Rp {{ number_format($summary['total_subtotal'], 0, ',', '.') }}</td>
                        <td class="text-end">-Rp {{ number_format($summary['total_discount'], 0, ',', '.') }}</td>
                        <td class="text-end text-primary">Rp {{ number_format($summary['total_grand_total'], 0, ',', '.') }}</td>
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
                    <p style="margin-bottom: 50px;">Petugas Kasir,</p>
                    <p class="fw-bold mb-0" style="text-decoration: underline;">{{ auth()->user()->name }}</p>
                    <small style="color: #666;">Pelaksana Transaksi</small>
                </td>
                <td style="width: 50%; border: none; text-align: center; padding-top: 20px;">
                    <p style="margin-bottom: 50px;">Mengetahui & Menyetujui,</p>
                    <p class="fw-bold mb-0" style="text-decoration: underline;">( .................................................. )</p>
                    <small style="color: #666;">Manajer / Pemilik Toko</small>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
