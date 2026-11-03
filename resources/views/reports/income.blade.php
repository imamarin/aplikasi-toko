@extends('layouts.app')

@section('title', 'Laporan Pendapatan')

@section('content')
<div class="container-fluid px-0">
    @include('reports.partials.nav')

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1">Laporan Pendapatan Harian</h4>
            <p class="text-muted small mb-0">Rekapitulasi penjualan, pembayaran real kios (Cash, TF, QRIS), piutang (Credit), modal (HPP), dan laba kotor harian</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.income.pdf', request()->all()) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
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
            <h4 style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">LAPORAN PENDAPATAN</h4>
            <div style="font-size: 9pt; color: #555; margin-top: 3px;">
                Periode: <strong>{{ $start->format('d-m-Y') }}</strong> s/d <strong>{{ $end->format('d-m-Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter Card (Hanya di Layar Web) -->
    <div class="card card-custom p-3 mb-4 d-print-none">
        <form method="GET" action="{{ route('reports.income') }}" class="row g-2 align-items-end">
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
                <a href="{{ route('reports.income') }}" class="btn btn-light border btn-sm" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards (Hanya di Layar Web) -->
    <div class="row g-3 mb-4 d-print-none">
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 bg-white h-100 border-start border-4 border-primary">
                <small class="text-muted d-block">Total Penjualan (Omset)</small>
                <h4 class="fw-bold text-primary mb-0">Rp{{ number_format($summary['total_penjualan'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">{{ number_format($summary['total_transactions'], 0, ',', '.') }} nota dalam {{ $summary['total_days'] }} hari aktif</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 bg-white h-100 border-start border-4 border-success">
                <small class="text-muted d-block">Pembayaran Real Kios</small>
                <h4 class="fw-bold text-success mb-0">Rp{{ number_format($summary['total_pembayaran_real_kios'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">
                    Cash + TF + QRIS diterima
                    @if($summary['total_debt_repayment'] > 0)
                        (termasuk pelunasan Rp{{ number_format($summary['total_debt_repayment'], 0, ',', '.') }})
                    @endif
                </small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 bg-white h-100 border-start border-4 border-danger">
                <small class="text-muted d-block">Total Credit (Piutang Baru)</small>
                <h4 class="fw-bold text-danger mb-0">Rp{{ number_format($summary['total_credit'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Akumulasi piutang transaksi periode ini</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 bg-white h-100 border-start border-4 border-info">
                <small class="text-muted d-block">Total Laba Kotor</small>
                <h4 class="fw-bold text-info mb-0">Rp{{ number_format($summary['total_laba_kotor'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Modal (HPP): Rp{{ number_format($summary['total_modal'], 0, ',', '.') }} ({{ number_format($summary['gross_margin_percentage'], 1) }}%)</small>
            </div>
        </div>
    </div>

    <!-- Tabel Utama PENDAPATAN (Sesuai Format Spreadsheet) -->
    <div class="card card-custom overflow-hidden">
        <div class="text-center py-2 fw-bold border-bottom d-print-none" style="background-color: #f8fafc; font-size: 1.05rem; letter-spacing: 0.05em;">
            PENDAPATAN
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.875rem;">
                <thead>
                    <tr class="text-center align-middle" style="background-color: #3b7ddd; color: #ffffff;">
                        <th style="width: 4%; background-color: #3b7ddd; color: #fff;">NO</th>
                        <th style="width: 10%; background-color: #3b7ddd; color: #fff;">TANGGAL</th>
                        <th style="width: 11%; background-color: #3b7ddd; color: #fff;">PENJUALAN</th>
                        <th style="width: 12%; background-color: #3b7ddd; color: #fff;">PEMBAYARAN<br>REAL KIOS</th>
                        <th style="width: 10%; background-color: #3b7ddd; color: #fff;">CASH</th>
                        <th style="width: 10%; background-color: #3b7ddd; color: #fff;">TF</th>
                        <th style="width: 10%; background-color: #3b7ddd; color: #fff;">QRIS</th>
                        <th style="width: 10%; background-color: #3b7ddd; color: #fff;">CREDIT</th>
                        <th style="width: 11%; background-color: #3b7ddd; color: #fff;">MODAL</th>
                        <th style="width: 12%; background-color: #3b7ddd; color: #fff;">LABA KOTOR</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $idx => $row)
                        @php
                            $rowDate = \Carbon\Carbon::parse($row->date);
                        @endphp
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td class="text-center fw-semibold text-nowrap">
                                {{ $rowDate->format('d-m-Y') }}
                            </td>
                            <td class="text-end text-nowrap">
                                Rp{{ number_format($row->penjualan, 0, ',', '.') }}
                            </td>
                            <td class="text-end fw-semibold text-nowrap">
                                Rp{{ number_format($row->pembayaran_real_kios, 0, ',', '.') }}
                                @if($row->debt_repayment > 0)
                                    <div class="small text-success fw-normal d-print-none" style="font-size: 0.7rem;" title="Termasuk pelunasan piutang pelanggan sebesar Rp{{ number_format($row->debt_repayment, 0, ',', '.') }}">
                                        +Pelunasan Rp{{ number_format($row->debt_repayment, 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                {{ $row->cash > 0 ? 'Rp' . number_format($row->cash, 0, ',', '.') : '' }}
                            </td>
                            <td class="text-end text-nowrap">
                                {{ $row->tf > 0 ? 'Rp' . number_format($row->tf, 0, ',', '.') : '' }}
                            </td>
                            <td class="text-end text-nowrap">
                                {{ $row->qris > 0 ? 'Rp' . number_format($row->qris, 0, ',', '.') : '' }}
                            </td>
                            <td class="text-end text-nowrap text-danger">
                                {{ $row->credit > 0 ? 'Rp' . number_format($row->credit, 0, ',', '.') : '' }}
                            </td>
                            <td class="text-end text-nowrap">
                                Rp{{ number_format($row->modal, 0, ',', '.') }}
                            </td>
                            <td class="text-end fw-bold text-nowrap {{ $row->laba_kotor >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp{{ number_format($row->laba_kotor, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                Belum ada data transaksi pendapatan pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($records->count() > 0)
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2" class="text-center">TOTAL</td>
                        <td class="text-end text-nowrap">Rp{{ number_format($summary['total_penjualan'], 0, ',', '.') }}</td>
                        <td class="text-end text-nowrap">Rp{{ number_format($summary['total_pembayaran_real_kios'], 0, ',', '.') }}</td>
                        <td class="text-end text-nowrap">{{ $summary['total_cash'] > 0 ? 'Rp' . number_format($summary['total_cash'], 0, ',', '.') : '-' }}</td>
                        <td class="text-end text-nowrap">{{ $summary['total_tf'] > 0 ? 'Rp' . number_format($summary['total_tf'], 0, ',', '.') : '-' }}</td>
                        <td class="text-end text-nowrap">{{ $summary['total_qris'] > 0 ? 'Rp' . number_format($summary['total_qris'], 0, ',', '.') : '-' }}</td>
                        <td class="text-end text-nowrap text-danger">{{ $summary['total_credit'] > 0 ? 'Rp' . number_format($summary['total_credit'], 0, ',', '.') : '-' }}</td>
                        <td class="text-end text-nowrap">Rp{{ number_format($summary['total_modal'], 0, ',', '.') }}</td>
                        <td class="text-end text-nowrap text-success">Rp{{ number_format($summary['total_laba_kotor'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Keterangan Rumus (Hanya di Layar Web) -->
    <div class="card card-custom p-3 mt-3 bg-light border-0 d-print-none">
        <div class="small text-muted">
            <strong><i class="bi bi-info-circle me-1"></i> Keterangan Perhitungan:</strong>
            <ul class="mb-0 mt-1 ps-3">
                <li><strong>PENJUALAN:</strong> Total nilai nota penjualan selesai pada tanggal tersebut (termasuk penjualan tunai, non-tunai, maupun kasbon/piutang).</li>
                <li><strong>PEMBAYARAN REAL KIOS:</strong> Total uang riil yang masuk ke toko pada tanggal tersebut (<code>CASH + TF + QRIS</code>), termasuk uang muka (DP) kasbon dan cicilan/pelunasan piutang pelanggan yang dibayarkan di tanggal tersebut.</li>
                <li><strong>CREDIT:</strong> Nilai piutang pelanggan baru yang belum terbayar pada tanggal transaksi penjualan terjadi (<code>Penjualan Kasbon - DP Awal</code>).</li>
                <li><strong>MODAL &amp; LABA KOTOR:</strong> <code>MODAL</code> adalah total Harga Pokok Penjualan (HPP) dari barang yang terjual di tanggal tersebut, sedangkan <code>LABA KOTOR = PENJUALAN - MODAL</code>.</li>
            </ul>
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

