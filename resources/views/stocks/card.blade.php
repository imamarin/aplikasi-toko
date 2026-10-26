@extends('layouts.app')

@section('title', 'Kartu Stok - ' . $product->name)

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Kartu Stok: {{ $product->name }}</h4>
            <p class="text-muted small mb-0">Buku pembantu persediaan barang untuk mencatat seluruh pergerakan masuk dan keluar</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-printer me-1"></i> Cetak Kartu
            </button>
            <a href="{{ route('stocks.index') }}" class="btn btn-primary-custom btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Stok
            </a>
        </div>
    </div>

    <!-- Product Summary Header -->
    <div class="card card-custom p-4 mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <small class="text-muted d-block">SKU / Barcode</small>
                <div class="fw-bold font-monospace">{{ $product->sku }} {{ $product->barcode ? '(' . $product->barcode . ')' : '' }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Kategori & Satuan</small>
                <div class="fw-bold">{{ $product->category?->name ?? '-' }} &bull; {{ $product->unit?->name ?? 'Pcs' }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Harga Beli / Jual</small>
                <div class="fw-bold text-primary">Rp {{ number_format($product->cost_price, 0, ',', '.') }} / Rp {{ number_format($product->selling_price, 0, ',', '.') }}</div>
            </div>
            <div class="col-md-3 text-md-end">
                <small class="text-muted d-block">Saldo Stok Saat Ini</small>
                <h3 class="fw-bold text-success mb-0">{{ $product->stock }} <small class="fs-6 fw-normal text-muted">{{ $product->unit?->short_name }}</small></h3>
            </div>
        </div>
    </div>

    <!-- Stock Movements Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Tanggal & Jam</th>
                        <th>Jenis Transaksi</th>
                        <th>Saldo Awal</th>
                        <th class="text-center text-success">Masuk (+)</th>
                        <th class="text-center text-danger">Keluar (-)</th>
                        <th class="text-center fw-bold">Saldo Akhir</th>
                        <th>User Pelaksana</th>
                        <th>Catatan / No. Referensi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $index => $m)
                        <tr>
                            <td>{{ $movements->firstItem() + $index }}</td>
                            <td>{{ $m->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>
                                @switch($m->type)
                                    @case('initial')
                                        <span class="badge bg-secondary-subtle text-secondary">Stok Awal</span>
                                        @break
                                    @case('purchase')
                                        <span class="badge bg-primary-subtle text-primary">Pembelian PO</span>
                                        @break
                                    @case('sale')
                                        <span class="badge bg-success-subtle text-success">Penjualan Kasir</span>
                                        @break
                                    @case('sale_return')
                                        <span class="badge bg-info-subtle text-info">Retur Penjualan</span>
                                        @break
                                    @case('purchase_return')
                                        <span class="badge bg-danger-subtle text-danger">Retur Supplier</span>
                                        @break
                                    @case('opname')
                                    @case('adjustment')
                                        <span class="badge bg-warning-subtle text-warning">Stok Opname</span>
                                        @break
                                    @default
                                        <span class="badge bg-light text-dark">{{ $m->type }}</span>
                                @endswitch
                            </td>
                            <td>{{ $m->stock_before }}</td>
                            <td class="text-center fw-bold text-success">
                                {{ $m->qty_in > 0 ? '+' . $m->qty_in : '-' }}
                            </td>
                            <td class="text-center fw-bold text-danger">
                                {{ $m->qty_out > 0 ? '-' . $m->qty_out : '-' }}
                            </td>
                            <td class="text-center fw-bold text-dark fs-6">
                                {{ $m->stock_after }}
                            </td>
                            <td>{{ $m->user?->name ?? 'Sistem' }}</td>
                            <td class="small text-muted">{{ $m->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">Belum ada catatan mutasi fisik untuk produk ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
            <div class="p-3 border-top">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
