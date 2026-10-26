@extends('layouts.app')

@section('title', 'Laporan Keuangan Cash & Transfer')

@section('content')
<div class="container-fluid px-0">
    @include('reports.partials.nav')

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1">Laporan Keuangan Cash & Transfer</h4>
            <p class="text-muted small mb-0">Audit arus kas fisik laci kasir vs penerimaan non-tunai (Transfer Bank & QRIS) serta mutasi operasional</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.finance-cash-bank.pdf', request()->all()) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
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
            <h4 style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">LAPORAN KEUANGAN CASH & TRANSFER (KAS & BANK)</h4>
            <div style="font-size: 9pt; color: #555; margin-top: 3px;">
                Periode: <strong>{{ $start->format('d/m/Y') }}</strong> s/d <strong>{{ $end->format('d/m/Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter Card (Hanya di Layar Web) -->
    <div class="card card-custom p-3 mb-4 d-print-none">
        <form method="GET" action="{{ route('reports.finance-cash-bank') }}" class="row g-2 align-items-end">
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
                <button type="submit" class="btn btn-primary-custom btn-sm w-100" title="Terapkan Filter"><i class="bi bi-filter"></i> Terapkan Filter</button>
                <a href="{{ route('reports.finance-cash-bank') }}" class="btn btn-light border btn-sm" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards (Web View) -->
    <div class="row g-3 mb-4 d-print-none">
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white border-start border-4 border-success">
                <small class="text-muted d-block">Penjualan Tunai (Cash)</small>
                <h4 class="fw-bold text-success mb-0">Rp {{ number_format($summary['cash_sales'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Penerimaan fisik laci kasir</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white border-start border-4 border-primary">
                <small class="text-muted d-block">Penerimaan Bank / Non-Tunai</small>
                <h4 class="fw-bold text-primary mb-0">Rp {{ number_format($summary['total_non_cash_sales'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">TF: Rp {{ number_format($summary['transfer_sales'], 0, ',', '.') }} &bull; QRIS: Rp {{ number_format($summary['qris_sales'], 0, ',', '.') }}</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white border-start border-4 border-warning">
                <small class="text-muted d-block">Mutasi Kas Masuk / Keluar</small>
                <h4 class="fw-bold text-dark mb-0">
                    <span class="text-success">+{{ number_format($summary['cash_in'], 0, ',', '.') }}</span> / 
                    <span class="text-danger">-{{ number_format($summary['cash_out'], 0, ',', '.') }}</span>
                </h4>
                <small class="text-muted" style="font-size: 0.75rem;">Refund tunai: Rp {{ number_format($summary['cash_refunds'], 0, ',', '.') }}</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white border-start border-4 border-info">
                <small class="text-muted d-block">Posisi Bersih Kas Fisik (Laci)</small>
                <h4 class="fw-bold text-info mb-0">Rp {{ number_format($summary['net_cash_drawer'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Cash Sales + In - Out - Refund</small>
            </div>
        </div>
    </div>

    <!-- Ringkasan Format Tabel Cetak (Khusus Print) -->
    <div class="d-none d-print-block mb-3">
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Total Omset Penjualan</th>
                    <th style="width: 25%;">Penerimaan Tunai (Cash)</th>
                    <th style="width: 25%;">Penerimaan Bank & QRIS</th>
                    <th style="width: 25%;">Posisi Bersih Kas Fisik (Laci)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold">Rp {{ number_format($summary['total_sales'], 0, ',', '.') }}</td>
                    <td class="fw-bold text-success">Rp {{ number_format($summary['cash_sales'], 0, ',', '.') }}</td>
                    <td class="fw-bold text-primary">Rp {{ number_format($summary['total_non_cash_sales'], 0, ',', '.') }}</td>
                    <td class="fw-bold text-info">Rp {{ number_format($summary['net_cash_drawer'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Breakdown Penjualan per Metode Pembayaran -->
    <div class="card card-custom mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="fw-bold mb-0"><i class="bi bi-wallet2 text-primary me-2"></i>1. Rincian Penerimaan Penjualan per Metode Bayar</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 5%;" class="text-center">#</th>
                        <th style="width: 30%;">Metode Pembayaran</th>
                        <th style="width: 20%;" class="text-center">Kategori Jalur</th>
                        <th style="width: 15%;" class="text-center">Jumlah Transaksi</th>
                        <th style="width: 15%;" class="text-end">Total Penerimaan</th>
                        <th style="width: 15%;" class="text-end">Porsi (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totMethodTx = $salesByMethod->sum('total_transactions');
                        $totMethodAmount = $salesByMethod->sum('total_amount');
                    @endphp
                    @forelse($salesByMethod as $idx => $method)
                        @php
                            $percentage = $totMethodAmount > 0 ? ($method->total_amount / $totMethodAmount) * 100 : 0;
                            $badgeClass = match($method->payment_method) {
                                'cash' => 'bg-success-subtle text-success',
                                'transfer' => 'bg-primary-subtle text-primary',
                                'qris' => 'bg-info-subtle text-info',
                                default => 'bg-secondary-subtle text-secondary',
                            };
                            $typeLabel = match($method->payment_method) {
                                'cash' => 'Fisik Laci (Cash Drawer)',
                                'transfer' => 'Bank Rekening Toko',
                                'qris' => 'Digital Merchant (E-Wallet/QRIS)',
                                default => 'Non-Tunai Lainnya',
                            };
                        @endphp
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }} text-uppercase px-2 py-1 me-2 fw-bold">{{ $method->payment_method }}</span>
                                <span class="fw-semibold">{{ ucfirst($method->payment_method) }}</span>
                            </td>
                            <td class="text-center text-muted small">{{ $typeLabel }}</td>
                            <td class="text-center">{{ number_format($method->total_transactions, 0, ',', '.') }} Transaksi</td>
                            <td class="text-end fw-bold">Rp {{ number_format($method->total_amount, 0, ',', '.') }}</td>
                            <td class="text-end">
                                <span class="fw-semibold">{{ number_format($percentage, 1) }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada transaksi penjualan pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold small">
                    <tr>
                        <td colspan="3" class="text-end">TOTAL PENERIMAAN PENJUALAN:</td>
                        <td class="text-center">{{ number_format($totMethodTx, 0, ',', '.') }} Transaksi</td>
                        <td class="text-end text-primary">Rp {{ number_format($totMethodAmount, 0, ',', '.') }}</td>
                        <td class="text-end">100.0%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Log Mutasi Kas Kasir (Cash In / Out) -->
    <div class="card card-custom">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="bi bi-arrow-left-right text-warning me-2"></i>2. Mutasi Kas Operasional (Kas Masuk & Keluar)</h6>
            <span class="badge bg-light text-dark border small">{{ $cashLogs->count() }} Data Tercatat</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 5%;" class="text-center">#</th>
                        <th style="width: 15%;">Waktu Transaksi</th>
                        <th style="width: 12%;" class="text-center">Tipe Mutasi</th>
                        <th style="width: 15%;">Kategori Alasan</th>
                        <th style="width: 15%;" class="text-end">Nominal</th>
                        <th style="width: 15%;">Petugas / Kasir</th>
                        <th style="width: 23%;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashLogs as $idx => $log)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td>
                                <div>{{ $log->transaction_date ? \Carbon\Carbon::parse($log->transaction_date)->format('d/m/Y H:i') : '-' }}</div>
                            </td>
                            <td class="text-center">
                                @if($log->type === 'in')
                                    <span class="badge bg-success-subtle text-success fw-bold"><i class="bi bi-arrow-down-left me-1"></i>Kas Masuk</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger fw-bold"><i class="bi bi-arrow-up-right me-1"></i>Kas Keluar</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ ucfirst($log->category ?? 'Lain-lain') }}</td>
                            <td class="text-end fw-bold {{ $log->type === 'in' ? 'text-success' : 'text-danger' }}">
                                {{ $log->type === 'in' ? '+' : '-' }}Rp {{ number_format($log->amount, 0, ',', '.') }}
                            </td>
                            <td>{{ $log->user->name ?? 'Kasir' }}</td>
                            <td class="text-muted small">{{ $log->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Tidak ada catatan transaksi kas operasional (in/out) pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($cashLogs->count() > 0)
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="4" class="text-end">TOTAL MUTASI:</td>
                            <td class="text-end">
                                <span class="text-success">+Rp {{ number_format($summary['cash_in'], 0, ',', '.') }}</span><br>
                                <span class="text-danger">-Rp {{ number_format($summary['cash_out'], 0, ',', '.') }}</span>
                            </td>
                            <td colspan="2"></td>
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
                    <span style="color: #666; font-size: 8.5pt;">Kasir / Bendahara</span>
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
