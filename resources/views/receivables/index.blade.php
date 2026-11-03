@extends('layouts.app')

@section('title', 'Piutang Pelanggan (Kasbon)')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-journal-bookmark-fill text-danger me-2"></i>Buku Piutang Pelanggan</h4>
            <p class="text-muted small mb-0">Kelola tagihan kasbon/tempo pelanggan, bayar sekaligus per pelanggan (otomatis alokasi FIFO), dan cetak kwitansi</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pos.index') }}" class="btn btn-warning fw-bold text-dark rounded-pill px-4 shadow-sm">
                <i class="bi bi-calculator me-1"></i> Buka Kasir (POS)
            </a>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-4 border-danger">
                <small class="text-muted d-block">Total Sisa Piutang Aktif</small>
                <h4 class="fw-bold text-danger mb-0">Rp {{ number_format($unpaidSalesStats->total_receivable ?? 0, 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Tagihan belum tertagih</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-4 border-primary">
                <small class="text-muted d-block">Pelanggan Berhutang</small>
                <h4 class="fw-bold text-primary mb-0">{{ number_format($unpaidSalesStats->total_customers ?? 0, 0, ',', '.') }} Pelanggan</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Memiliki saldo hutang aktif</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-4 border-warning">
                <small class="text-muted d-block">Nota Belum Lunas</small>
                <h4 class="fw-bold text-dark mb-0">{{ number_format($unpaidSalesStats->total_invoices ?? 0, 0, ',', '.') }} Nota</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Status belum bayar / cicilan</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-4 border-secondary">
                <small class="text-muted d-block">Lewat Jatuh Tempo</small>
                <h4 class="fw-bold {{ ($unpaidSalesStats->overdue_invoices ?? 0) > 0 ? 'text-danger' : 'text-success' }} mb-0">
                    {{ number_format($unpaidSalesStats->overdue_invoices ?? 0, 0, ',', '.') }} Nota
                </h4>
                <small class="text-muted" style="font-size: 0.75rem;">Perlu segera ditagih</small>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card card-custom p-3 mb-4">
        <form method="GET" action="{{ route('receivables.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama pelanggan, kode member, atau no. HP..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-8 col-md-4">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="unpaid" {{ $filterStatus === 'unpaid' ? 'selected' : '' }}>Hanya Pelanggan yang Masih Berhutang</option>
                    <option value="all" {{ $filterStatus === 'all' ? 'selected' : '' }}>Semua Pelanggan yang Pernah Kasbon</option>
                </select>
            </div>
            <div class="col-4 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom w-100"><i class="bi bi-filter me-1"></i> Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('receivables.index') }}" class="btn btn-light border" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </div>

    <!-- Customer Receivables Table -->
    <div class="card card-custom mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="bi bi-people-fill text-primary me-2"></i>Daftar Piutang per Pelanggan</h6>
            <span class="badge bg-light text-secondary border">Pilih pelanggan untuk bayar sekaligus</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Pelanggan</th>
                        <th class="text-center">Nota Belum Lunas</th>
                        <th class="text-end">Total Tagihan Nota</th>
                        <th class="text-end">Sudah Dibayar</th>
                        <th class="text-end">Total Sisa Hutang</th>
                        <th class="text-center">Jatuh Tempo Terdekat</th>
                        <th class="text-center" style="width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $index => $cust)
                        @php
                            $remaining = max(0, (float) $cust->unpaid_grand_total - (float) $cust->unpaid_paid_amount);
                            $isOverdue = $cust->nearest_due_date && \Carbon\Carbon::parse($cust->nearest_due_date)->isPast();
                        @endphp
                        <tr>
                            <td>{{ $customers->firstItem() + $index }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $cust->name }}</div>
                                <div class="small text-muted">
                                    <span class="font-monospace">{{ $cust->code }}</span>
                                    @if($cust->phone)
                                        &bull; <i class="bi bi-telephone"></i> {{ $cust->phone }}
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @if($cust->unpaid_sales_count > 0)
                                    <span class="badge bg-danger-subtle text-danger px-2 py-1 fw-bold">{{ $cust->unpaid_sales_count }} Nota</span>
                                @else
                                    <span class="badge bg-success-subtle text-success px-2 py-1">Lunas</span>
                                @endif
                            </td>
                            <td class="text-end">Rp {{ number_format($cust->unpaid_grand_total ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end text-success">Rp {{ number_format($cust->unpaid_paid_amount ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">
                                <span class="fw-bold fs-6 {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">
                                    Rp {{ number_format($remaining, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($cust->nearest_due_date)
                                    <span class="badge {{ $isOverdue ? 'bg-danger text-white' : 'bg-warning-subtle text-dark border' }}">
                                        {{ \Carbon\Carbon::parse($cust->nearest_due_date)->format('d/m/Y') }}
                                        @if($isOverdue) (Lewat) @endif
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('receivables.show', $cust->id) }}" class="btn {{ $remaining > 0 ? 'btn-danger' : 'btn-outline-primary' }} btn-sm rounded-pill px-3 fw-semibold shadow-xs">
                                    <i class="bi {{ $remaining > 0 ? 'bi-cash-coin' : 'bi-eye' }} me-1"></i>
                                    {{ $remaining > 0 ? 'Bayar / Rincian' : 'Lihat Riwayat' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                                Tidak ada data piutang pelanggan yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="p-3 border-top">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    <!-- Recent Payments Log -->
    @if($recentPayments->count() > 0)
        <div class="card card-custom">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-success me-2"></i>10 Transaksi Pembayaran Piutang Terakhir</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>No. Kwitansi</th>
                            <th>Tanggal Bayar</th>
                            <th>Pelanggan</th>
                            <th>Metode</th>
                            <th>Nota yang Dilunasi / Dicicil</th>
                            <th class="text-end">Total Dibayar</th>
                            <th>Kasir</th>
                            <th class="text-center">Kwitansi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentPayments as $pmt)
                            <tr>
                                <td class="fw-bold font-monospace text-primary">{{ $pmt->payment_number }}</td>
                                <td>{{ $pmt->payment_date->format('d/m/Y H:i') }}</td>
                                <td class="fw-semibold">
                                    <a href="{{ route('receivables.show', $pmt->customer_id) }}" class="text-decoration-none text-dark">
                                        {{ $pmt->customer?->name }}
                                    </a>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $pmt->payment_method }}</span></td>
                                <td>
                                    @foreach($pmt->allocations as $alloc)
                                        <a href="{{ route('sales.show', $alloc->sale_id) }}" class="badge bg-light text-dark border text-decoration-none me-1">
                                            {{ $alloc->sale?->invoice_number }} (Rp {{ number_format($alloc->amount, 0, ',', '.') }})
                                        </a>
                                    @endforeach
                                </td>
                                <td class="text-end fw-bold text-success">Rp {{ number_format($pmt->total_paid, 0, ',', '.') }}</td>
                                <td>{{ $pmt->user?->name ?? '-' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('receivables.payment.receipt', $pmt->id) }}" target="_blank" class="btn btn-light border btn-sm" title="Cetak Kwitansi">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

