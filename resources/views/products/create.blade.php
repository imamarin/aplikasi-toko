@extends('layouts.app')

@section('title', 'Tambah Produk Baru')

@section('content')
<div class="container-fluid px-0" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Tambah Produk Baru</h4>
            <p class="text-muted small mb-0">Lengkapi formulir berikut untuk mendaftarkan barang baru ke sistem</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    <div class="card card-custom p-4">
        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Kode SKU <span class="text-danger">*</span></label>
                    <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $suggestedSku) }}" required>
                    @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Barcode (EAN-13 / Scanner)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-upc-scan"></i></span>
                        <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror" value="{{ old('barcode') }}" placeholder="Scan atau ketik kode barcode">
                    </div>
                    @error('barcode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Contoh: Indomie Goreng Spesial 85g" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Kategori <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                        <option value="">Pilih Kategori Produk</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Satuan Unit <span class="text-danger">*</span></label>
                    <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
                        <option value="">Pilih Satuan</option>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}" {{ old('unit_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->short_name }})</option>
                        @endforeach
                    </select>
                    @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Harga Modal / Beli (HPP) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">Rp</span>
                        <input type="text" inputmode="numeric" name="cost_price" class="form-control input-rupiah @error('cost_price') is-invalid @enderror" value="{{ old('cost_price', 0) }}" required>
                    </div>
                    @error('cost_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Harga Jual Konsumen <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">Rp</span>
                        <input type="text" inputmode="numeric" name="selling_price" class="form-control input-rupiah @error('selling_price') is-invalid @enderror" value="{{ old('selling_price', 0) }}" required>
                    </div>
                    @error('selling_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Diskon Promo Bawaan (Opsional)</label>
                    <div class="input-group">
                        <select name="discount_type" id="selectDiscountType" class="form-select @error('discount_type') is-invalid @enderror" style="max-width: 110px;" onchange="toggleDiscountFormat(this)">
                            <option value="fixed" {{ old('discount_type', 'fixed') === 'fixed' ? 'selected' : '' }}>Rp</option>
                            <option value="percentage" {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>%</option>
                        </select>
                        <input type="text" inputmode="numeric" name="discount_value" id="inputDiscountValue" class="form-control input-rupiah @error('discount_value') is-invalid @enderror" value="{{ old('discount_value', 0) }}" placeholder="0">
                    </div>
                    <small class="text-muted" style="font-size: 0.75rem;">Diskon default per unit otomatis aktif di POS</small>
                    @error('discount_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Stok Awal <span class="text-danger">*</span></label>
                    <input type="number" min="0" name="stock" class="form-control @error('stock') is-invalid @enderror" value="{{ old('stock', 0) }}" required>
                    <small class="text-muted" style="font-size: 0.75rem;">Akan dicatat otomatis sebagai saldo mutasi awal</small>
                    @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Batas Minimum Stok Alert <span class="text-danger">*</span></label>
                    <input type="number" min="0" name="min_stock" class="form-control @error('min_stock') is-invalid @enderror" value="{{ old('min_stock', 5) }}" required>
                    <small class="text-muted" style="font-size: 0.75rem;">Sistem akan memberi peringatan jika stok &le; angka ini</small>
                    @error('min_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Foto Produk</label>
                    <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                    <small class="text-muted" style="font-size: 0.75rem;">Format JPG, PNG, WEBP (Maks. 2MB)</small>
                    @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="status" value="1" id="productStatus" checked>
                        <label class="form-check-label fw-semibold small" for="productStatus">Produk Aktif untuk Dijual</label>
                    </div>
                </div>

                <div class="col-12 mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                    <a href="{{ route('products.index') }}" class="btn btn-light border rounded-pill px-4">Batal</a>
                    <button type="submit" class="btn btn-primary-custom px-4 shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Produk
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleDiscountFormat(selectEl) {
        const input = document.getElementById('inputDiscountValue');
        if (!input) return;
        if (selectEl.value === 'fixed') {
            input.classList.add('input-rupiah');
            attachRupiahFormatter(input);
            if (input.value) input.value = formatRupiahString(input.value);
        } else {
            input.classList.remove('input-rupiah');
            input._hasRupiahFormatter = false;
            input.value = input.value.replace(/\./g, '');
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        const sel = document.getElementById('selectDiscountType');
        if (sel) toggleDiscountFormat(sel);
    });
</script>
@endpush
