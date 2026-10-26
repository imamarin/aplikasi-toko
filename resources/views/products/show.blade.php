@extends('layouts.app')

@section('title', 'Detail Produk & Kartu Stok')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $product->name }}</h4>
            <p class="text-muted small mb-0">Informasi detail produk dan kartu audit riwayat pergerakan stok</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary-custom btn-sm rounded-pill px-3">
                <i class="bi bi-pencil-square me-1"></i> Edit Produk
            </a>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Product Details Card -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-4">
            <div class="card card-custom p-4 text-center h-100">
                @if($product->image)
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="rounded-4 mb-3 mx-auto shadow-sm" style="max-height: 200px; width: 100%; object-fit: cover;">
                @else
                    <div class="rounded-4 bg-light text-muted d-flex align-items-center justify-content-center mb-3 mx-auto" style="height: 180px; width: 100%;">
                        <i class="bi bi-box-seam fs-1"></i>
                    </div>
                @endif
                <h5 class="fw-bold text-dark mb-1">{{ $product->name }}</h5>
                <p class="text-muted small mb-2">{{ $product->category?->name }} &bull; {{ $product->unit?->name }}</p>

                <div class="border-top pt-3 mt-2">
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">SKU:</span>
                        <span class="fw-bold font-monospace">{{ $product->sku }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Barcode:</span>
                        <span class="font-monospace">{{ $product->barcode ?? '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Status:</span>
                        <span>{!! $product->status ? '<span class="badge bg-success-subtle text-success">Aktif</span>' : '<span class="badge bg-danger-subtle text-danger">Nonaktif</span>' !!}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <div class="card card-custom p-4 h-100">
                <h6 class="fw-bold mb-3"><i class="bi bi-cash-coin text-primary me-2"></i> Rincian Finansial & Inventori</h6>

                <div class="row g-3 mb-4">
                    <div class="col-6 col-sm-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <small class="text-muted d-block mb-1">Harga Beli (HPP)</small>
                            <h5 class="fw-bold text-dark mb-0">Rp {{ number_format($product->cost_price, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <small class="text-muted d-block mb-1">Harga Jual</small>
                            <h5 class="fw-bold text-primary mb-0">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <small class="text-muted d-block mb-1">Margin Keuntungan</small>
                            <h5 class="fw-bold text-success mb-0">+ Rp {{ number_format($product->profit_margin, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <small class="text-muted d-block mb-1">Stok Fisik Tersedia</small>
                            <h4 class="fw-bold text-dark mb-0">{{ $product->stock }} <small class="fs-6 fw-normal text-muted">{{ $product->unit?->short_name }}</small></h4>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <small class="text-muted d-block mb-1">Batas Minimum Alert</small>
                            <h4 class="fw-bold text-warning mb-0">{{ $product->min_stock }} <small class="fs-6 fw-normal text-muted">{{ $product->unit?->short_name }}</small></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Movements Table (Kartu Stok) -->
    <div class="card card-custom">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i> Kartu Stok & Histori Mutasi</h6>
            <span class="badge bg-light text-dark border">Total {{ $movements->total() }} Catatan</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Waktu Transaksi</th>
                        <th>Jenis Perubahan</th>
                        <th class="text-center">Masuk (+)</th>
                        <th class="text-center">Keluar (-)</th>
                        <th class="text-center">Saldo Akhir</th>
                        <th>User Pelaksana</th>
                        <th>Keterangan / Referensi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                        <tr>
                            <td>{{ $m->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @switch($m->type)
                                    @case('initial')
                                        <span class="badge bg-secondary-subtle text-secondary">Stok Awal</span>
                                        @break
                                    @case('purchase')
                                        <span class="badge bg-primary-subtle text-primary">Pembelian</span>
                                        @break
                                    @case('sale')
                                        <span class="badge bg-success-subtle text-success">Penjualan</span>
                                        @break
                                    @case('sale_return')
                                        <span class="badge bg-info-subtle text-info">Retur Jual</span>
                                        @break
                                    @case('purchase_return')
                                        <span class="badge bg-danger-subtle text-danger">Retur Beli</span>
                                        @break
                                    @case('adjustment')
                                    @case('opname')
                                        <span class="badge bg-warning-subtle text-warning">Penyesuaian/Opname</span>
                                        @break
                                    @default
                                        <span class="badge bg-light text-dark">{{ $m->type }}</span>
                                @endswitch
                            </td>
                            <td class="text-center fw-bold text-success">
                                {{ $m->qty_in > 0 ? '+' . $m->qty_in : '-' }}
                            </td>
                            <td class="text-center fw-bold text-danger">
                                {{ $m->qty_out > 0 ? '-' . $m->qty_out : '-' }}
                            </td>
                            <td class="text-center fw-bold">
                                {{ $m->stock_after }}
                            </td>
                            <td>{{ $m->user?->name ?? 'Sistem' }}</td>
                            <td class="text-muted small">{{ $m->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada riwayat mutasi stok untuk produk ini.</td>
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
