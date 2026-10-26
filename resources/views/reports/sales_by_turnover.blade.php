@extends('layouts.app')

@section('title', 'Laporan Penjualan per Omset')

@section('content')
<div class="container-fluid px-0">
    @include('reports.partials.nav')

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1">Laporan Penjualan per Omset (Tren & Harian)</h4>
            <p class="text-muted small mb-0">Analisis rekapitulasi perolehan omset harian, diskon, pajak, dan rata-rata nilai transaksi</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.sales-by-turnover.pdf', request()->all()) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
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
            <h4 style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">LAPORAN PENJUALAN PER OMSET (HARIAN & TREN)</h4>
            <div style="font-size: 9pt; color: #555; margin-top: 3px;">
                Periode: <strong>{{ $start->format('d/m/Y') }}</strong> s/d <strong>{{ $end->format('d/m/Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter Card (Hanya di Layar Web) -->
    <div class="card card-custom p-3 mb-4 d-print-none">
        <form method="GET" action="{{ route('reports.sales-by-turnover') }}" class="row g-2 align-items-end">
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
                <label class="form-label small text-muted">Mulai</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted">Sampai</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end->format('Y-m-d') }}">
            </div>
            <div class="col-12 col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100" title="Terapkan Filter"><i class="bi bi-filter"></i> Filter</button>
                <a href="{{ route('reports.sales-by-turnover') }}" class="btn btn-light border btn-sm" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards (Hanya di Layar Web) -->
    <div class="row g-3 mb-4 d-print-none">
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Grand Total Omset Bersih</small>
                <h4 class="fw-bold text-primary mb-0">Rp {{ number_format($summary['total_grand_total'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Total penerimaan periode ini</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Total Transaksi / Nota</small>
                <h4 class="fw-bold text-dark mb-0">{{ number_format($summary['total_transactions'], 0, ',', '.') }} Nota</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Tersebar dalam {{ $summary['active_days'] }} hari penjualan</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Rata-rata Omset Harian</small>
                <h4 class="fw-bold text-success mb-0">Rp {{ number_format($summary['avg_daily_turnover'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Omset rata-rata per hari aktif</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Rata-rata per Nota (AOV)</small>
                <h4 class="fw-bold text-info mb-0">Rp {{ number_format($summary['avg_basket_size'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Nilai rata-rata per keranjang transaksi</small>
            </div>
        </div>
    </div>

    <!-- Ringkasan Format Tabel Cetak (Khusus Print) -->
    <div class="d-none d-print-block mb-2">
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Grand Total Omset</th>
                    <th style="width: 25%;">Total Transaksi</th>
                    <th style="width: 25%;">Rata-rata Omset Harian</th>
                    <th style="width: 25%;">Rata-rata Nilai per Nota</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold text-primary">Rp {{ number_format($summary['total_grand_total'], 0, ',', '.') }}</td>
                    <td class="fw-bold">{{ number_format($summary['total_transactions'], 0, ',', '.') }} Nota ({{ $summary['active_days'] }} Hari)</td>
                    <td class="fw-bold">Rp {{ number_format($summary['avg_daily_turnover'], 0, ',', '.') }} / hari</td>
                    <td class="fw-bold">Rp {{ number_format($summary['avg_basket_size'], 0, ',', '.') }} / nota</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Turnovers Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 4%;" class="text-center">#</th>
                        <th style="width: 14%;">Tanggal</th>
                        <th style="width: 11%;" class="text-center">Jumlah Nota</th>
                        <th style="width: 13%;" class="text-end">Subtotal Produk</th>
                        <th style="width: 11%;" class="text-end">Potongan Diskon</th>
                        <th style="width: 10%;" class="text-end">Ongkir</th>
                        <th style="width: 10%;" class="text-end">Pajak (PPN)</th>
                        <th style="width: 14%;" class="text-end">Grand Total</th>
                        <th style="width: 13%;" class="text-end">Rata-rata / Nota</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($turnovers as $idx => $t)
                        @php
                            $avgPerNota = $t->total_transactions > 0 ? ($t->total_grand_total / $t->total_transactions) : 0;
                            $tDate = \Carbon\Carbon::parse($t->date);
                        @endphp
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td class="fw-bold text-dark">
                                {{ $tDate->translatedFormat('d F Y') }}
                                <div class="small text-muted fw-normal">({{ $tDate->translatedFormat('l') }})</div>
                            </td>
                            <td class="text-center fw-semibold">{{ $t->total_transactions }} Nota</td>
                            <td class="text-end">Rp {{ number_format($t->total_subtotal, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">{{ $t->total_discount > 0 ? '-Rp ' . number_format($t->total_discount, 0, ',', '.') : '-' }}</td>
                            <td class="text-end text-info">{{ $t->total_shipping > 0 ? '+Rp ' . number_format($t->total_shipping, 0, ',', '.') : '-' }}</td>
                            <td class="text-end text-muted">{{ $t->total_tax > 0 ? 'Rp ' . number_format($t->total_tax, 0, ',', '.') : '-' }}</td>
                            <td class="text-end fw-bold text-primary">Rp {{ number_format($t->total_grand_total, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold text-secondary">Rp {{ number_format($avgPerNota, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">Belum ada perolehan omset pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2" class="text-end">TOTAL KESELURUHAN OMSET:</td>
                        <td class="text-center">{{ number_format($summary['total_transactions'], 0, ',', '.') }} Nota</td>
                        <td class="text-end">Rp {{ number_format($summary['total_subtotal'], 0, ',', '.') }}</td>
                        <td class="text-end text-danger">-Rp {{ number_format($summary['total_discount'], 0, ',', '.') }}</td>
                        <td class="text-end text-info">+Rp {{ number_format($summary['total_shipping'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($summary['total_tax'], 0, ',', '.') }}</td>
                        <td class="text-end text-primary">Rp {{ number_format($summary['total_grand_total'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($summary['avg_basket_size'], 0, ',', '.') }}</td>
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
                    <small style="color: #666;">Bagian Kasir / Keuangan</small>
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
