@extends('layouts.app')

@section('title', 'Input Stok Opname Baru')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Input Stok Opname Baru</h4>
            <p class="text-muted small mb-0">Hitung fisik persediaan barang dan sesuaikan selisih secara otomatis</p>
        </div>
        <a href="{{ route('stock-opnames.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <form action="{{ route('stock-opnames.store') }}" method="POST">
        @csrf

        <div class="card card-custom p-4 mb-4">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold">Tanggal Pemeriksaan Fisik <span class="text-danger">*</span></label>
                    <input type="date" name="opname_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label small fw-semibold">Catatan / Alasan Opname</label>
                    <input type="text" name="notes" class="form-control" placeholder="Contoh: Stok opname berkala akhir bulan...">
                </div>
            </div>
        </div>

        <div class="card card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-list-check text-primary me-2"></i> Daftar Produk & Hasil Perhitungan Fisik</h6>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="addRow()">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Baris Produk
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="opnameTable">
                    <thead class="table-light small">
                        <tr>
                            <th style="min-width: 250px;">Produk</th>
                            <th style="width: 140px;" class="text-center">Stok Sistem</th>
                            <th style="width: 160px;" class="text-center">Stok Fisik Nyata <span class="text-danger">*</span></th>
                            <th style="width: 140px;" class="text-center">Selisih</th>
                            <th>Keterangan / Alasan Selisih</th>
                            <th style="width: 60px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="opnameBody">
                        <!-- Initial row -->
                        <tr class="opname-row">
                            <td>
                                <select name="items[0][product_id]" class="form-select product-select" required onchange="handleProductSelect(this, 0)">
                                    <option value="">Pilih Produk...</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-stock="{{ $p->stock }}" data-unit="{{ $p->unit?->short_name ?? 'pcs' }}">
                                            {{ $p->name }} (SKU: {{ $p->sku }}) - Stok: {{ $p->stock }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold system-stock-text">0</span>
                            </td>
                            <td>
                                <input type="number" name="items[0][physical_stock]" class="form-control text-center physical-input" value="0" min="0" required oninput="calcDiff(0)">
                            </td>
                            <td class="text-center">
                                <span class="fw-bold diff-badge badge bg-secondary">0</span>
                            </td>
                            <td>
                                <input type="text" name="items[0][notes]" class="form-control form-control-sm" placeholder="Hilang, rusak, dll.">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-light btn-sm text-danger border" onclick="removeRow(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('stock-opnames.index') }}" class="btn btn-light rounded-pill px-4">Batal</a>
                <button type="submit" class="btn btn-primary-custom px-4 shadow-sm" onclick="return confirm('Apakah Anda yakin data fisik sudah benar? Sistem akan menyesuaikan stok produk secara otomatis.')">
                    <i class="bi bi-check2-all me-1"></i> Simpan & Sesuaikan Stok Sistem
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Product options template for JS -->
<template id="productOptionsTemplate">
    <option value="">Pilih Produk...</option>
    @foreach($products as $p)
        <option value="{{ $p->id }}" data-stock="{{ $p->stock }}" data-unit="{{ $p->unit?->short_name ?? 'pcs' }}">
            {{ $p->name }} (SKU: {{ $p->sku }}) - Stok: {{ $p->stock }}
        </option>
    @endforeach
</template>

@push('scripts')
<script>
    let rowIndex = 1;

    function addRow() {
        const tbody = document.getElementById('opnameBody');
        const tr = document.createElement('tr');
        tr.className = 'opname-row';

        const productOptions = document.getElementById('productOptionsTemplate').innerHTML;

        tr.innerHTML = `
            <td>
                <select name="items[${rowIndex}][product_id]" class="form-select product-select" required onchange="handleProductSelect(this, ${rowIndex})">
                    ${productOptions}
                </select>
            </td>
            <td class="text-center">
                <span class="fw-bold system-stock-text">0</span>
            </td>
            <td>
                <input type="number" name="items[${rowIndex}][physical_stock]" class="form-control text-center physical-input" value="0" min="0" required oninput="calcDiff(${rowIndex})">
            </td>
            <td class="text-center">
                <span class="fw-bold diff-badge badge bg-secondary">0</span>
            </td>
            <td>
                <input type="text" name="items[${rowIndex}][notes]" class="form-control form-control-sm" placeholder="Hilang, rusak, dll.">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-light btn-sm text-danger border" onclick="removeRow(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        rowIndex++;
    }

    function removeRow(btn) {
        const rows = document.querySelectorAll('.opname-row');
        if (rows.length > 1) {
            btn.closest('tr').remove();
        } else {
            alert('Minimal harus ada satu produk yang diopname.');
        }
    }

    function handleProductSelect(select, index) {
        const row = select.closest('tr');
        const selectedOption = select.options[select.selectedIndex];
        const stock = parseInt(selectedOption.getAttribute('data-stock') || 0);

        row.querySelector('.system-stock-text').innerText = stock;
        const physicalInput = row.querySelector('.physical-input');
        if (physicalInput.value === '0') {
            physicalInput.value = stock; // set default to match system
        }

        calcRowDiff(row);
    }

    function calcDiff(index) {
        const rows = document.querySelectorAll('.opname-row');
        rows.forEach(r => calcRowDiff(r));
    }

    function calcRowDiff(row) {
        const systemStock = parseInt(row.querySelector('.system-stock-text').innerText || 0);
        const physicalStock = parseInt(row.querySelector('.physical-input').value || 0);
        const diff = physicalStock - systemStock;

        const badge = row.querySelector('.diff-badge');
        badge.innerText = (diff > 0 ? '+' : '') + diff;

        if (diff > 0) {
            badge.className = 'fw-bold diff-badge badge bg-success';
        } else if (diff < 0) {
            badge.className = 'fw-bold diff-badge badge bg-danger';
        } else {
            badge.className = 'fw-bold diff-badge badge bg-secondary';
        }
    }
</script>
@endpush
@endsection
