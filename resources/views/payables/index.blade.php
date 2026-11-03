@extends('layouts.app')

@section('title', 'Hutang Supplier (Pembelian Tempo)')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-journal-minus text-danger me-2"></i>Buku Hutang Toko ke Supplier</h4>
            <p class="text-muted small mb-0">Kelola tagihan pembelian barang masuk (PO) secara tempo/kredit, bayar sekaligus per supplier (alokasi FIFO), dan cetak bukti bayar</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchases.create') }}" class="btn btn-primary-custom rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Buat Pembelian (PO) Baru
            </a>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-4 border-danger">
                <small class="text-muted d-block">Total Sisa Hutang ke Supplier</small>
                <h4 class="fw-bold text-danger mb-0">Rp {{ number_format($unpaidPurchaseStats->total_payable ?? 0, 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Kewajiban belum dibayar</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-4 border-primary">
                <small class="text-muted d-block">Jumlah Supplier</small>
                <h4 class="fw-bold text-primary mb-0">{{ number_format($unpaidPurchaseStats->total_suppliers ?? 0, 0, ',', '.') }} Supplier</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Memiliki tagihan PO aktif</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-4 border-warning">
                <small class="text-muted d-block">Faktur PO Belum Lunas</small>
                <h4 class="fw-bold text-dark mb-0">{{ number_format($unpaidPurchaseStats->total_purchases ?? 0, 0, ',', '.') }} Faktur PO</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Status hutang / cicilan</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-4 border-secondary">
                <small class="text-muted d-block">PO Lewat Jatuh Tempo</small>
                <h4 class="fw-bold {{ ($unpaidPurchaseStats->overdue_purchases ?? 0) > 0 ? 'text-danger' : 'text-success' }} mb-0">
                    {{ number_format($unpaidPurchaseStats->overdue_purchases ?? 0, 0, ',', '.') }} Faktur PO
                </h4>
                <small class="text-muted" style="font-size: 0.75rem;">Perlu segera dilunasi</small>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card card-custom p-3 mb-4">
        <form method="GET" action="{{ route('payables.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama supplier, kode, kontak, atau no. telepon..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-8 col-md-4">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="unpaid" {{ $filterStatus === 'unpaid' ? 'selected' : '' }}>Hanya Supplier dengan Tagihan Belum Lunas</option>
                    <option value="all" {{ $filterStatus === 'all' ? 'selected' : '' }}>Semua Supplier Riwayat Tempo</option>
                </select>
            </div>
            <div class="col-4 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom w-100"><i class="bi bi-filter me-1"></i> Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('payables.index') }}" class="btn btn-light border" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </div>

    <!-- Supplier Payables Table -->
    <div class="card card-custom mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="bi bi-truck text-primary me-2"></i>Daftar Hutang per Supplier</h6>
            <span class="badge bg-light text-secondary border">Pilih supplier untuk bayar sekaligus</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Nama Supplier</th>
                        <th class="text-center">PO Belum Lunas</th>
                        <th class="text-end">Total Tagihan PO</th>
                        <th class="text-end">Sudah Dibayar</th>
                        <th class="text-end">Total Sisa Hutang</th>
                        <th class="text-center">Jatuh Tempo Terdekat</th>
                        <th class="text-center" style="width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $index => $sup)
                        @php
                            $remaining = max(0, (float) $sup->unpaid_grand_total - (float) $sup->unpaid_paid_amount);
                            $isOverdue = $sup->nearest_due_date && \Carbon\Carbon::parse($sup->nearest_due_date)->isPast();
                        @endphp
                        <tr>
                            <td>{{ $suppliers->firstItem() + $index }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $sup->name }}</div>
                                <div class="small text-muted">
                                    <span class="font-monospace">{{ $sup->code }}</span>
                                    @if($sup->contact_person)
                                        &bull; PIC: {{ $sup->contact_person }}
                                    @endif
                                    @if($sup->phone)
                                        &bull; <i class="bi bi-telephone"></i> {{ $sup->phone }}
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @if($sup->unpaid_purchases_count > 0)
                                    <span class="badge bg-danger-subtle text-danger px-2 py-1 fw-bold">{{ $sup->unpaid_purchases_count }} PO</span>
                                @else
                                    <span class="badge bg-success-subtle text-success px-2 py-1">Lunas</span>
                                @endif
                            </td>
                            <td class="text-end">Rp {{ number_format($sup->unpaid_grand_total ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end text-success">Rp {{ number_format($sup->unpaid_paid_amount ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">
                                <span class="fw-bold fs-6 {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">
                                    Rp {{ number_format($remaining, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($sup->nearest_due_date)
                                    <span class="badge {{ $isOverdue ? 'bg-danger text-white' : 'bg-warning-subtle text-dark border' }}">
                                        {{ \Carbon\Carbon::parse($sup->nearest_due_date)->format('d/m/Y') }}
                                        @if($isOverdue) (Lewat) @endif
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('payables.show', $sup->id) }}" class="btn {{ $remaining > 0 ? 'btn-danger' : 'btn-outline-primary' }} btn-sm rounded-pill px-3 fw-semibold shadow-xs">
                                    <i class="bi {{ $remaining > 0 ? 'bi-cash-coin' : 'bi-eye' }} me-1"></i>
                                    {{ $remaining > 0 ? 'Bayar / Rincian' : 'Lihat Riwayat' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                                Tidak ada data hutang ke supplier yang belum lunas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())
            <div class="p-3 border-top">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>

    <!-- Recent Payable Payments Log -->
    @if($recentPayments->count() > 0)
        <div class="card card-custom">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-success me-2"></i>10 Transaksi Pembayaran Hutang Supplier Terakhir</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>No. Bukti Bayar</th>
                            <th>Tanggal Bayar</th>
                            <th>Supplier</th>
                            <th>Metode</th>
                            <th>PO yang Dilunasi / Dicicil</th>
                            <th class="text-end">Total Dibayar</th>
                            <th>Petugas</th>
                            <th class="text-center">Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentPayments as $pmt)
                            <tr>
                                <td class="fw-bold font-monospace text-primary">{{ $pmt->payment_number }}</td>
                                <td>{{ $pmt->payment_date->format('d/m/Y H:i') }}</td>
                                <td class="fw-semibold">
                                    <a href="{{ route('payables.show', $pmt->supplier_id) }}" class="text-decoration-none text-dark">
                                        {{ $pmt->supplier?->name }}
                                    </a>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $pmt->payment_method }}</span></td>
                                <td>
                                    @foreach($pmt->allocations as $alloc)
                                        <a href="{{ route('purchases.show', $alloc->purchase_id) }}" class="badge bg-light text-dark border text-decoration-none me-1">
                                            {{ $alloc->purchase?->purchase_number }} (Rp {{ number_format($alloc->amount, 0, ',', '.') }})
                                        </a>
                                    @endforeach
                                </td>
                                <td class="text-end fw-bold text-success">Rp {{ number_format($pmt->total_paid, 0, ',', '.') }}</td>
                                <td>{{ $pmt->user?->name ?? '-' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('payables.payment.receipt', $pmt->id) }}" target="_blank" class="btn btn-light border btn-sm" title="Cetak Bukti Bayar">
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

