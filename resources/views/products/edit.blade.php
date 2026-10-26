@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
<div class="container-fluid px-0" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Edit Data Produk</h4>
            <p class="text-muted small mb-0">Ubah informasi produk, harga jual, dan kategori</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="card card-custom p-4">
        <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Kode SKU <span class="text-danger">*</span></label>
                    <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Barcode (EAN-13 / Scanner)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-upc-scan"></i></span>
                        <input type="text" name="barcode" class="form-control" value="{{ old('barcode', $product->barcode) }}">
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Kategori <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Satuan Unit <span class="text-danger">*</span></label>
                    <select name="unit_id" class="form-select" required>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}" {{ old('unit_id', $product->unit_id) == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->short_name }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Harga Modal / Beli (HPP) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">Rp</span>
                        <input type="number" step="1" min="0" name="cost_price" class="form-control" value="{{ old('cost_price', $product->cost_price) }}" required>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Harga Jual Konsumen <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">Rp</span>
                        <input type="number" step="1" min="0" name="selling_price" class="form-control" value="{{ old('selling_price', $product->selling_price) }}" required>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Stok Saat Ini (Hanya Lihat)</label>
                    <input type="text" class="form-control bg-light" value="{{ $product->stock }} {{ $product->unit?->short_name }}" disabled>
                    <small class="text-muted" style="font-size: 0.75rem;">Untuk mengubah stok, gunakan fitur <a href="{{ route('stock-opnames.index') }}">Stok Opname</a> atau <a href="{{ route('purchases.create') }}">Pembelian</a></small>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Batas Minimum Stok Alert <span class="text-danger">*</span></label>
                    <input type="number" min="0" name="min_stock" class="form-control" value="{{ old('min_stock', $product->min_stock) }}" required>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Foto Produk</label>
                    @if($product->image)
                        <div class="mb-2">
                            <img src="{{ asset('storage/' . $product->image) }}" alt="Preview" class="rounded-3 shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                        </div>
                    @endif
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <div class="col-12">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="status" value="1" id="productStatus" {{ $product->status ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold small" for="productStatus">Produk Aktif untuk Dijual</label>
                    </div>
                </div>

                <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                    <a href="{{ route('products.index') }}" class="btn btn-light border rounded-pill px-4">Batal</a>
                    <button type="submit" class="btn btn-primary-custom px-4 shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
