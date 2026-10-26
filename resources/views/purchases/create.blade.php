@extends('layouts.app')

@section('title', 'Tambah Pembelian (PO) Baru')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Tambah Pembelian Barang (PO) Baru</h4>
            <p class="text-muted small mb-0">Catat faktur masuk dari supplier, pembaruan HPP produk, dan penambahan stok</p>
        </div>
        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <form action="{{ route('purchases.store') }}" method="POST">
        @csrf

        <!-- Header Info Card -->
        <div class="card card-custom p-4 mb-4">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold">Pilih Supplier / Distributor <span class="text-danger">*</span></label>
                    <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                        <option value="">Pilih Supplier...</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" {{ old('supplier_id') == $sup->id ? 'selected' : '' }}>
                                {{ $sup->name }} ({{ $sup->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold">Tanggal Faktur Pembelian <span class="text-danger">*</span></label>
                    <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select" id="selectPaymentMethod" onchange="toggleDueDate(this.value)">
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Tunai Langsung (Cash)</option>
                        <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                        <option value="credit" {{ old('payment_method') === 'credit' ? 'selected' : '' }}>Hutang / Kredit (Tempo)</option>
                    </select>
                </div>

                <div class="col-12 col-md-6" id="dueDateContainer" style="display: none;">
                    <label class="form-label small fw-semibold">Tanggal Jatuh Tempo Pembayaran</label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Catatan Transaksi / No. Surat Jalan</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Nomor faktur supplier, keterangan barang...">
                </div>
            </div>
        </div>

        <!-- Line Items Card -->
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-boxes text-primary me-2"></i> Rincian Produk yang Dibeli</h6>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="addPurchaseRow()">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Item Produk
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="purchaseTable">
                    <thead class="table-light small">
                        <tr>
                            <th style="min-width: 250px;">Produk</th>
                            <th style="width: 130px;" class="text-center">Kuantitas Masuk</th>
                            <th style="width: 170px;" class="text-end">Harga Beli Baru (HPP)</th>
                            <th style="width: 140px;" class="text-end">Diskon Item</th>
                            <th style="width: 170px;" class="text-end">Subtotal</th>
                            <th style="width: 50px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseBody">
                        <tr class="purchase-row">
                            <td>
                                <select name="items[0][product_id]" class="form-select product-select" required onchange="handleProductChange(this, 0)">
                                    <option value="">Pilih Produk...</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-cost="{{ $p->cost_price }}" data-unit="{{ $p->unit?->short_name ?? 'pcs' }}">
                                            {{ $p->name }} (SKU: {{ $p->sku }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="items[0][qty]" min="1" value="1" class="form-control text-center qty-input" required oninput="calcRow(this)">
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="text" inputmode="numeric" name="items[0][cost_price]" value="0" class="form-control text-end cost-input input-rupiah" required oninput="calcRow(this)">
                                </div>
                            </td>
                            <td>
                                <input type="text" inputmode="numeric" name="items[0][discount]" value="0" class="form-control text-end discount-input input-rupiah" oninput="calcRow(this)">
                            </td>
                            <td class="text-end fw-bold">
                                <span class="row-subtotal-text">Rp 0</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-light btn-sm text-danger border" onclick="removePurchaseRow(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Financial Calculations -->
            <div class="row justify-content-end mt-4">
                <div class="col-md-5">
                    <div class="list-group list-group-flush border-top">
                        <div class="list-group-item d-flex justify-content-between px-0 py-2 small">
                            <span class="text-muted">Subtotal Pembelian:</span>
                            <span class="fw-bold" id="totalSubtotalText">Rp 0</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 small">
                            <span class="text-muted">Potongan Diskon PO:</span>
                            <div class="input-group input-group-sm" style="width: 150px;">
                                <span class="input-group-text bg-light">Rp</span>
                                <input type="text" inputmode="numeric" name="discount_amount" id="inputOverallDiscount" value="0" class="form-control text-end input-rupiah" oninput="calcAll()">
                            </div>
                        </div>
                        <div class="list-group-item d-flex justify-content-between px-0 py-2 fs-5 fw-bold text-dark">
                            <span>Grand Total PO:</span>
                            <span class="text-primary" id="grandTotalText">Rp 0</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 small">
                            <span class="fw-semibold">Uang Dibayarkan Saat Ini:</span>
                            <div class="input-group input-group-sm" style="width: 170px;">
                                <span class="input-group-text bg-light">Rp</span>
                                <input type="text" inputmode="numeric" name="paid_amount" id="inputPaidAmount" value="0" class="form-control text-end input-rupiah">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('purchases.index') }}" class="btn btn-light rounded-pill px-4">Batal</a>
                <button type="submit" class="btn btn-primary-custom px-4 shadow-sm">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Pembelian & Tambah Stok
                </button>
            </div>
        </div>
    </form>
</div>

<template id="purchaseProductOptionsTemplate">
    <option value="">Pilih Produk...</option>
    @foreach($products as $p)
        <option value="{{ $p->id }}" data-cost="{{ $p->cost_price }}" data-unit="{{ $p->unit?->short_name ?? 'pcs' }}">
            {{ $p->name }} (SKU: {{ $p->sku }})
        </option>
    @endforeach
</template>

@push('scripts')
<script>
    let pRowIndex = 1;

    function toggleDueDate(method) {
        document.getElementById('dueDateContainer').style.display = method === 'credit' ? 'block' : 'none';
        if (method === 'credit') {
            document.getElementById('inputPaidAmount').value = 0;
        } else {
            document.getElementById('inputPaidAmount').value = currentGrandTotal;
        }
    }

    function addPurchaseRow() {
        const tbody = document.getElementById('purchaseBody');
        const tr = document.createElement('tr');
        tr.className = 'purchase-row';

        const productOptions = document.getElementById('purchaseProductOptionsTemplate').innerHTML;

        tr.innerHTML = `
            <td>
                <select name="items[${pRowIndex}][product_id]" class="form-select product-select" required onchange="handleProductChange(this, ${pRowIndex})">
                    ${productOptions}
                </select>
            </td>
            <td>
                <input type="number" name="items[${pRowIndex}][qty]" min="1" value="1" class="form-control text-center qty-input" required oninput="calcRow(this)">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light">Rp</span>
                    <input type="text" inputmode="numeric" name="items[${pRowIndex}][cost_price]" value="0" class="form-control text-end cost-input input-rupiah" required oninput="calcRow(this)">
                </div>
            </td>
            <td>
                <input type="text" inputmode="numeric" name="items[${pRowIndex}][discount]" value="0" class="form-control text-end discount-input input-rupiah" oninput="calcRow(this)">
            </td>
            <td class="text-end fw-bold">
                <span class="row-subtotal-text">Rp 0</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-light btn-sm text-danger border" onclick="removePurchaseRow(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        initRupiahInputs(tr);
        pRowIndex++;
    }

    function removePurchaseRow(btn) {
        const rows = document.querySelectorAll('.purchase-row');
        if (rows.length > 1) {
            btn.closest('tr').remove();
            calcAll();
        } else {
            alert('Minimal 1 barang pembelian harus disertakan.');
        }
    }

    function handleProductChange(select, index) {
        const row = select.closest('tr');
        const selectedOption = select.options[select.selectedIndex];
        const cost = parseFloat(selectedOption.getAttribute('data-cost') || 0);

        row.querySelector('.cost-input').value = formatRupiahString(cost);
        calcRow(select);
    }

    function calcRow(el) {
        const row = el.closest('tr');
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const cost = parseFloat((row.querySelector('.cost-input').value || '').replace(/\./g, '')) || 0;
        const discount = parseFloat((row.querySelector('.discount-input').value || '').replace(/\./g, '')) || 0;

        const subtotal = Math.max(0, (qty * cost) - discount);
        row.querySelector('.row-subtotal-text').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal);

        calcAll();
    }

    let currentGrandTotal = 0;

    function calcAll() {
        const rows = document.querySelectorAll('.purchase-row');
        let totalSubtotal = 0;

        rows.forEach(r => {
            const qty = parseFloat(r.querySelector('.qty-input').value) || 0;
            const cost = parseFloat((r.querySelector('.cost-input').value || '').replace(/\./g, '')) || 0;
            const discount = parseFloat((r.querySelector('.discount-input').value || '').replace(/\./g, '')) || 0;
            totalSubtotal += Math.max(0, (qty * cost) - discount);
        });

        const overallDiscount = parseFloat((document.getElementById('inputOverallDiscount').value || '').replace(/\./g, '')) || 0;
        currentGrandTotal = Math.max(0, totalSubtotal - overallDiscount);

        document.getElementById('totalSubtotalText').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalSubtotal);
        document.getElementById('grandTotalText').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(currentGrandTotal);

        const method = document.getElementById('selectPaymentMethod').value;
        if (method !== 'credit') {
            document.getElementById('inputPaidAmount').value = formatRupiahString(currentGrandTotal);
        }
    }
</script>
@endpush
@endsection
