@extends('layouts.app')

@section('title', 'Daftar Pembelian Supplier')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Daftar Pembelian Barang (PO)</h4>
            <p class="text-muted small mb-0">Kelola pesanan pembelian supplier, penerimaan stok masuk, dan status pembayaran</p>
        </div>
        <a href="{{ route('purchases.create') }}" class="btn btn-primary-custom d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-plus-circle-fill"></i> Tambah Pembelian (PO) Baru
        </a>
    </div>

    <!-- Filter Card -->
    <div class="card card-custom p-3 mb-4">
        <form method="GET" action="{{ route('purchases.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari no. PO, nama supplier..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="supplier_id" class="form-select">
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="payment_status" class="form-select">
                    <option value="">Semua Status Bayar</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Lunas (Paid)</option>
                    <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Sebagian (Partial)</option>
                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Belum Lunas (Hutang)</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom w-100">Filter</button>
                @if(request()->hasAny(['search', 'supplier_id', 'payment_status']))
                    <a href="{{ route('purchases.index') }}" class="btn btn-light border"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </div>

    <!-- Purchases Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>No. Pembelian (PO)</th>
                        <th>Tanggal PO</th>
                        <th>Nama Supplier</th>
                        <th>Metode Bayar</th>
                        <th class="text-end">Total Pembelian</th>
                        <th class="text-end">Dibayarkan</th>
                        <th class="text-center">Status Bayar</th>
                        <th class="text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $index => $po)
                        <tr>
                            <td>{{ $purchases->firstItem() + $index }}</td>
                            <td>
                                <a href="{{ route('purchases.show', $po->id) }}" class="fw-bold text-primary font-monospace text-decoration-none">
                                    {{ $po->purchase_number }}
                                </a>
                            </td>
                            <td>{{ $po->purchase_date->format('d/m/Y') }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $po->supplier?->name ?? '-' }}</div>
                                <small class="text-muted">{{ $po->supplier?->phone }}</small>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $po->payment_method }}</span>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                Rp {{ number_format($po->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="text-end fw-semibold text-muted">
                                Rp {{ number_format($po->paid_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($po->payment_status === 'paid')
                                    <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">Lunas</span>
                                @elseif($po->payment_status === 'partial')
                                    <span class="badge bg-warning-subtle text-warning px-3 py-1 rounded-pill">Sebagian</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill">Hutang (Unpaid)</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('purchases.show', $po->id) }}" class="btn btn-sm btn-light border" title="Lihat Rincian">
                                    <i class="bi bi-eye text-primary"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">Belum ada transaksi pembelian barang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($purchases->hasPages())
            <div class="p-3 border-top">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
