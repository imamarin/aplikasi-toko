@extends('layouts.app')

@section('title', 'Daftar Produk')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Katalog & Data Produk</h4>
            <p class="text-muted small mb-0">Kelola informasi produk, penetapan harga jual, SKU, barcode, dan batas stok minimum</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('products.export.excel') }}" class="btn btn-outline-success btn-sm rounded-pill px-3 d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet"></i> Ekspor CSV / Excel
            </a>
            <a href="{{ route('products.create') }}" class="btn btn-primary-custom d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-plus-circle-fill"></i> Tambah Produk Baru
            </a>
        </div>
    </div>

    <!-- Filter & Search Toolbar Card -->
    <div class="card card-custom p-3 mb-4">
        <form method="GET" action="{{ route('products.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama produk, SKU, barcode..." value="{{ request('search') }}">
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
            <div class="col-6 col-md-3">
                <select name="stock_status" class="form-select">
                    <option value="">Semua Status Stok</option>
                    <option value="low" {{ request('stock_status') === 'low' ? 'selected' : '' }}>Stok Menipis (&le; Min)</option>
                    <option value="empty" {{ request('stock_status') === 'empty' ? 'selected' : '' }}>Stok Habis (0)</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom w-100">Filter</button>
                @if(request()->hasAny(['search', 'category_id', 'stock_status']))
                    <a href="{{ route('products.index') }}" class="btn btn-light border" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </div>

    <!-- Products Data Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Foto</th>
                        <th>SKU / Barcode</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th class="text-end">Harga Modal</th>
                        <th class="text-end">Harga Jual</th>
                        <th class="text-center">Stok</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $prod)
                        <tr>
                            <td>{{ $products->firstItem() + $index }}</td>
                            <td>
                                @if($prod->image)
                                    <img src="{{ asset('storage/' . $prod->image) }}" alt="{{ $prod->name }}" class="rounded-3 object-fit-cover shadow-sm" style="width: 44px; height: 44px;">
                                @else
                                    <div class="rounded-3 bg-light text-muted d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                        <i class="bi bi-box-seam fs-5"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark font-monospace" style="font-size: 0.85rem;">{{ $prod->sku }}</div>
                                @if($prod->barcode)
                                    <small class="text-muted d-block font-monospace"><i class="bi bi-upc-scan me-1"></i>{{ $prod->barcode }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $prod->name }}</div>
                                <small class="text-muted">{{ $prod->unit?->name ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $prod->category?->name ?? 'Uncategorized' }}</span>
                            </td>
                            <td class="text-end text-muted small">
                                Rp {{ number_format($prod->cost_price, 0, ',', '.') }}
                            </td>
                            <td class="text-end fw-bold text-primary">
                                Rp {{ number_format($prod->selling_price, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($prod->stock <= 0)
                                    <span class="badge bg-danger px-3 py-1 rounded-pill">Habis (0)</span>
                                @elseif($prod->stock <= $prod->min_stock)
                                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill" title="Mendekati batas minimum ({{ $prod->min_stock }})">
                                        {{ $prod->stock }} (Menipis)
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                        {{ $prod->stock }} {{ $prod->unit?->short_name }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($prod->status)
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('products.show', $prod->id) }}" class="btn btn-light border" title="Kartu Stok & Riwayat">
                                        <i class="bi bi-clock-history text-secondary"></i>
                                    </a>
                                    <a href="{{ route('products.edit', $prod->id) }}" class="btn btn-light border" title="Edit Produk">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </a>
                                    <form action="{{ route('products.destroy', $prod->id) }}" method="POST" onsubmit="return confirm('Hapus produk {{ $prod->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light border" title="Hapus">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-1 d-block mb-2 text-secondary"></i>
                                Tidak ada produk yang sesuai dengan kriteria pencarian.
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
