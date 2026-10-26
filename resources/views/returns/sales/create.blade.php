@extends('layouts.app')

@section('title', 'Buat Retur Penjualan')

@section('content')
<div class="container-fluid px-0" style="max-width: 950px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Formulir Retur Penjualan</h4>
            <p class="text-muted small mb-0">Pilih nota penjualan dan tentukan produk yang dikembalikan pelanggan</p>
        </div>
        <a href="{{ route('returns.sales.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <!-- Step 1: Select Sale Invoice -->
    <div class="card card-custom p-4 mb-4">
        <label class="form-label small fw-semibold">Pilih Nota Transaksi Penjualan yang Akan Diretur</label>
        <div class="d-flex gap-2">
            <select class="form-select" id="saleSelect" onchange="window.location.href='{{ route('returns.sales.create') }}?sale_id=' + this.value">
                <option value="">Pilih No. Invoice Penjualan...</option>
                @foreach($completedSales as $s)
                    <option value="{{ $s->id }}" {{ ($sale && $sale->id == $s->id) ? 'selected' : '' }}>
                        {{ $s->invoice_number }} - {{ $s->transaction_date->format('d/m/Y') }} - {{ $s->customer?->name ?? 'Umum' }} (Rp {{ number_format($s->grand_total, 0, ',', '.') }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @if($sale)
        <form action="{{ route('returns.sales.store') }}" method="POST">
            @csrf
            <input type="hidden" name="sale_id" value="{{ $sale->id }}">

            <div class="card card-custom p-4 mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Nota Penjualan Asal</small>
                        <div class="fw-bold fs-5 text-primary">{{ $sale->invoice_number }}</div>
                        <div class="small text-muted">Pelanggan: {{ $sale->customer?->name ?? 'Umum (Walk-in)' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Metode Pengembalian Dana (Refund) <span class="text-danger">*</span></label>
                        <select name="refund_method" class="form-select" required>
                            <option value="cash">Tunai Langsung (Potong Kas Laci Kasir)</option>
                            <option value="credit">Kredit Toko / Saldo Member</option>
                            <option value="none">Tanpa Refund Uang (Tukar Barang)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Catatan Retur</label>
                        <input type="text" name="notes" class="form-control" placeholder="Contoh: Barang cacat kemasan / kadaluarsa...">
                    </div>
                </div>
            </div>

            <!-- Items Table from Sale -->
            <div class="card card-custom p-4 mb-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-cart-x text-danger me-2"></i> Pilih Barang yang Diretur</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th>Produk</th>
                                <th class="text-center" style="width: 120px;">Qty Beli</th>
                                <th class="text-end" style="width: 150px;">Harga Satuan</th>
                                <th class="text-center" style="width: 140px;">Qty Retur</th>
                                <th style="width: 250px;">Alasan Retur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->details as $index => $item)
                                <tr>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                        <div class="fw-bold text-dark">{{ $item->product_name }}</div>
                                        <small class="text-muted">SKU: {{ $item->product?->sku }}</small>
                                    </td>
                                    <td class="text-center">{{ $item->qty }}</td>
                                    <td class="text-end">Rp {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                                    <td>
                                        <input type="number" name="items[{{ $index }}][qty]" class="form-control form-control-sm text-center" min="0" max="{{ $item->qty }}" value="0">
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][reason]" class="form-control form-control-sm" placeholder="Kemasan rusak, salah beli, dll.">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('returns.sales.index') }}" class="btn btn-light rounded-pill px-4">Batal</a>
                    <button type="submit" class="btn btn-primary-custom px-4 shadow-sm" onclick="return confirm('Konfirmasi proses retur penjualan ini?')">
                        <i class="bi bi-check2-circle me-1"></i> Proses Retur & Tambah Stok
                    </button>
                </div>
            </div>
        </form>
    @else
        <div class="card card-custom p-5 text-center text-muted">
            <i class="bi bi-receipt fs-1 text-secondary mb-2"></i>
            Silakan pilih nota penjualan di atas untuk memuat daftar barang.
        </div>
    @endif
</div>
@endsection
