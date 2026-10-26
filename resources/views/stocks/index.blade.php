@extends('layouts.app')

@section('title', 'Manajemen Stok')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Manajemen Stok & Persediaan</h4>
            <p class="text-muted small mb-0">Pantau pergerakan fisik barang, kartu mutasi stok, dan peringatan batas minimum</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('stock-opnames.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-clipboard-check me-1"></i> Riwayat Stok Opname
            </a>
            <a href="{{ route('stock-opnames.create') }}" class="btn btn-primary-custom d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-plus-circle-fill"></i> Input Stok Opname Baru
            </a>
        </div>
    </div>

    <!-- Quick Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-4">
            <a href="{{ route('stocks.index') }}" class="text-decoration-none">
                <div class="card-custom p-3 bg-white d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Total Unit Barang</div>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($totalStockUnits) }} <small class="fs-6 fw-normal text-muted">Item Fisik</small></h4>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-box-seam fs-4"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-4">
            <a href="{{ route('stocks.index', ['filter' => 'low']) }}" class="text-decoration-none">
                <div class="card-custom p-3 bg-white d-flex align-items-center justify-content-between border-warning">
                    <div>
                        <div class="text-warning small fw-semibold">Produk Stok Menipis (&le; Min)</div>
                        <h4 class="fw-bold text-warning mb-0">{{ $lowStockCount }} <small class="fs-6 fw-normal text-muted">Produk</small></h4>
                    </div>
                    <div class="rounded-circle bg-warning-subtle text-warning p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-exclamation-triangle fs-4"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-sm-4">
            <a href="{{ route('stocks.index', ['filter' => 'empty']) }}" class="text-decoration-none">
                <div class="card-custom p-3 bg-white d-flex align-items-center justify-content-between border-danger">
                    <div>
                        <div class="text-danger small fw-semibold">Produk Stok Habis (0)</div>
                        <h4 class="fw-bold text-danger mb-0">{{ $emptyStockCount }} <small class="fs-6 fw-normal text-muted">Produk</small></h4>
                    </div>
                    <div class="rounded-circle bg-danger-subtle text-danger p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-x-octagon fs-4"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card card-custom p-3 mb-4">
        <form method="GET" action="{{ route('stocks.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama barang, SKU, barcode..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="filter" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="low" {{ request('filter') === 'low' ? 'selected' : '' }}>Stok Menipis</option>
                    <option value="empty" {{ request('filter') === 'empty' ? 'selected' : '' }}>Stok Habis</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom w-100">Filter</button>
                @if(request()->hasAny(['search', 'category_id', 'filter']))
                    <a href="{{ route('stocks.index') }}" class="btn btn-light border"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </div>

    <!-- Stock Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Kode / SKU</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th class="text-center">Batas Min</th>
                        <th class="text-center">Stok Saat Ini</th>
                        <th class="text-center">Kondisi Status</th>
                        <th class="text-center" style="width: 140px;">Kartu Stok</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $prod)
                        <tr>
                            <td>{{ $products->firstItem() + $index }}</td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">{{ $prod->sku }}</span>
                                @if($prod->barcode)
                                    <small class="d-block text-muted font-monospace"><i class="bi bi-upc-scan me-1"></i>{{ $prod->barcode }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $prod->name }}</div>
                                <small class="text-muted">Satuan: {{ $prod->unit?->name ?? 'Pcs' }}</small>
                            </td>
                            <td>{{ $prod->category?->name ?? 'Uncategorized' }}</td>
                            <td class="text-center">{{ $prod->min_stock }} {{ $prod->unit?->short_name }}</td>
                            <td class="text-center">
                                <span class="fw-bold fs-6">{{ $prod->stock }}</span> {{ $prod->unit?->short_name }}
                            </td>
                            <td class="text-center">
                                @if($prod->stock <= 0)
                                    <span class="badge bg-danger rounded-pill px-3 py-1">Stok Habis</span>
                                @elseif($prod->stock <= $prod->min_stock)
                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1">Menipis (&le; {{ $prod->min_stock }})</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">Aman</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('stocks.card', $prod->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm">
                                    <i class="bi bi-journal-text me-1"></i> Kartu Stok
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                Tidak ada data stok yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="p-3 border-top">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
