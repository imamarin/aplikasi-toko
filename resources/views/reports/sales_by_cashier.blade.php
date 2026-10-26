@extends('layouts.app')

@section('title', 'Laporan Penjualan per Kasir')

@section('content')
<div class="container-fluid px-0">
    @include('reports.partials.nav')

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1">Laporan Penjualan per Kasir</h4>
            <p class="text-muted small mb-0">Evaluasi produktivitas, pencapaian omset, dan volume transaksi per staf kasir</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.sales-by-cashier.pdf', request()->all()) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
                <i class="bi bi-file-earmark-pdf"></i> Ekspor PDF
            </a>
            <button onclick="window.print()" class="btn btn-light border btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
                <i class="bi bi-printer me-1"></i> Print Laporan
            </button>
        </div>
    </div>

    <!-- Kop Header Toko (Khusus Cetak / Print) -->
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
            <h4 style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">LAPORAN PENJUALAN PER KASIR</h4>
            <div style="font-size: 9pt; color: #555; margin-top: 3px;">
                Periode: <strong>{{ $start->format('d/m/Y') }}</strong> s/d <strong>{{ $end->format('d/m/Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter Card (Hanya di Layar Web) -->
    <div class="card card-custom p-3 mb-4 d-print-none">
        <form method="GET" action="{{ route('reports.sales-by-cashier') }}" class="row g-2 align-items-end">
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
                <label class="form-label small text-muted">Pilih Kasir</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">-- Semua Kasir / Staf --</option>
                    @foreach($cashierList as $cUser)
                        <option value="{{ $cUser->id }}" {{ $selectedUserId == $cUser->id ? 'selected' : '' }}>
                            {{ $cUser->name }} ({{ $cUser->role->name ?? 'Kasir' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100" title="Terapkan Filter"><i class="bi bi-filter"></i> Filter</button>
                <a href="{{ route('reports.sales-by-cashier') }}" class="btn btn-light border btn-sm" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards (Web View) -->
    <div class="row g-3 mb-4 d-print-none">
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Kasir Bertransaksi</small>
                <h4 class="fw-bold text-dark mb-0">{{ $summary['total_cashiers_count'] }} Staf</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Staf aktif melayani transaksi</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Total Transaksi Selesai</small>
                <h4 class="fw-bold text-dark mb-0">{{ number_format($summary['overall_transactions'], 0, ',', '.') }} Nota</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Volume seluruh nota dilayani</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white border-primary">
                <small class="text-primary fw-semibold d-block">Total Pencapaian Omset</small>
                <h4 class="fw-bold text-primary mb-0">Rp {{ number_format($summary['overall_turnover'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Penerimaan omset kotor toko</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Rata-rata Penjualan / Nota</small>
                <h4 class="fw-bold text-success mb-0">Rp {{ number_format($summary['avg_store_basket_size'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Nilai rata-rata per transaksi</small>
            </div>
        </div>
    </div>

    <!-- Ringkasan Format Tabel Cetak (Khusus Print) -->
    <div class="d-none d-print-block mb-3">
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Total Kasir Bertransaksi</th>
                    <th style="width: 25%;">Total Transaksi Dilayani</th>
                    <th style="width: 25%;">Total Pencapaian Omset</th>
                    <th style="width: 25%;">Rata-rata Nilai / Nota</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold">{{ $summary['total_cashiers_count'] }} Staf Kasir</td>
                    <td class="fw-bold">{{ number_format($summary['overall_transactions'], 0, ',', '.') }} Nota</td>
                    <td class="fw-bold text-primary">Rp {{ number_format($summary['overall_turnover'], 0, ',', '.') }}</td>
                    <td class="fw-bold text-success">Rp {{ number_format($summary['avg_store_basket_size'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Cashier Performance Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 5%;" class="text-center">#</th>
                        <th style="width: 22%;">Nama Kasir</th>
                        <th style="width: 12%;" class="text-center">Jabatan / Role</th>
                        <th style="width: 12%;" class="text-center">Jumlah Transaksi</th>
                        <th style="width: 13%;" class="text-end">Subtotal Kotor</th>
                        <th style="width: 10%;" class="text-end">Diskon Diberikan</th>
                        <th style="width: 14%;" class="text-end">Total Omset</th>
                        <th style="width: 12%;" class="text-end">Kontribusi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashiers as $idx => $c)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $c->cashier_name }}</div>
                                <small class="text-muted font-monospace">{{ '@' . $c->cashier_username }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $c->cashier_role }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold">{{ number_format($c->total_transactions, 0, ',', '.') }}</span> Nota
                            </td>
                            <td class="text-end">Rp {{ number_format($c->total_subtotal, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">
                                {{ $c->total_discount > 0 ? '-Rp ' . number_format($c->total_discount, 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-end fw-bold text-primary">
                                Rp {{ number_format($c->total_grand_total, 0, ',', '.') }}
                            </td>
                            <td class="text-end">
                                <div class="fw-semibold">{{ number_format($c->contribution_percentage, 1) }}%</div>
                                <div class="progress mt-1 d-print-none" style="height: 4px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $c->contribution_percentage }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Belum ada transaksi yang dilakukan oleh kasir pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($cashiers->count() > 0)
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="3" class="text-end">TOTAL KESELURUHAN:</td>
                            <td class="text-center">{{ number_format($summary['overall_transactions'], 0, ',', '.') }} Nota</td>
                            <td class="text-end">Rp {{ number_format($cashiers->sum('total_subtotal'), 0, ',', '.') }}</td>
                            <td class="text-end text-danger">-Rp {{ number_format($cashiers->sum('total_discount'), 0, ',', '.') }}</td>
                            <td class="text-end text-primary">Rp {{ number_format($summary['overall_turnover'], 0, ',', '.') }}</td>
                            <td class="text-end">100.0%</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Tanda Tangan Khusus Print -->
    <div class="d-none d-print-block mt-4">
        <table style="width: 100%; border: none; margin-top: 30px;">
            <tr style="border: none;">
                <td style="width: 50%; text-align: center; border: none; vertical-align: top;">
                    <p style="margin-bottom: 50px;">Disiapkan Oleh,</p>
                    <p style="font-weight: bold; text-decoration: underline; margin-bottom: 2px;">{{ auth()->user()->name }}</p>
                    <span style="color: #666; font-size: 8.5pt;">Supervisor Kasir</span>
                </td>
                <td style="width: 50%; text-align: center; border: none; vertical-align: top;">
                    <p style="margin-bottom: 50px;">Mengetahui & Menyetujui,</p>
                    <p style="font-weight: bold; text-decoration: underline; margin-bottom: 2px;">( .................................................. )</p>
                    <span style="color: #666; font-size: 8.5pt;">Owner / Manajer Toko</span>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
