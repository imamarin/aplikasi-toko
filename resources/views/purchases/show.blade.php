@extends('layouts.app')

@section('title', 'Detail Pembelian #' . $purchase->purchase_number)

@section('content')
<div class="container-fluid px-0" style="max-width: 950px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Faktur Pembelian: {{ $purchase->purchase_number }}</h4>
            <p class="text-muted small mb-0">Informasi pesanan barang masuk dari supplier dan histori penerimaan stok</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-printer me-1"></i> Cetak Dokumen
            </button>
            <a href="{{ route('purchases.index') }}" class="btn btn-primary-custom btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Header Summary Card -->
    <div class="card card-custom p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">Tanggal Transaksi</small>
                <div class="fw-bold">{{ $purchase->purchase_date->format('d F Y') }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Supplier Mitra</small>
                <div class="fw-bold">{{ $purchase->supplier?->name ?? '-' }}</div>
                <small class="text-muted">{{ $purchase->supplier?->phone }}</small>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Metode & Status Bayar</small>
                <div class="mt-1">
                    <span class="badge bg-secondary text-uppercase">{{ $purchase->payment_method }}</span>
                    @if($purchase->payment_status === 'paid')
                        <span class="badge bg-success-subtle text-success">Lunas</span>
                    @elseif($purchase->payment_status === 'partial')
                        <span class="badge bg-warning-subtle text-warning">Sebagian</span>
                    @else
                        <span class="badge bg-danger-subtle text-danger">Hutang</span>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Petugas Penerima</small>
                <div class="fw-bold">{{ $purchase->user?->name ?? 'Gudang' }}</div>
            </div>
        </div>
    </div>

    <!-- Purchase Details Table -->
    <div class="card card-custom p-4 mb-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-boxes text-primary me-2"></i> Rincian Barang Masuk</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Kode SKU</th>
                        <th>Nama Produk</th>
                        <th class="text-end">Harga Beli Satuan</th>
                        <th class="text-center">Kuantitas Masuk</th>
                        <th class="text-end">Diskon Item</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase->details as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><span class="badge bg-light text-dark border font-monospace">{{ $item->product?->sku }}</span></td>
                            <td class="fw-bold text-dark">{{ $item->product?->name }}</td>
                            <td class="text-end">Rp {{ number_format($item->cost_price, 0, ',', '.') }}</td>
                            <td class="text-center fw-bold">{{ $item->qty }} {{ $item->product?->unit?->short_name }}</td>
                            <td class="text-end text-muted">{{ $item->discount_amount > 0 ? '-Rp ' . number_format($item->discount_amount, 0, ',', '.') : '-' }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row justify-content-end mt-4">
            <div class="col-md-5">
                <div class="list-group list-group-flush border-top">
                    <div class="list-group-item d-flex justify-content-between px-0 py-2 small">
                        <span class="text-muted">Subtotal:</span>
                        <span class="fw-semibold">Rp {{ number_format($purchase->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($purchase->discount_amount > 0)
                        <div class="list-group-item d-flex justify-content-between px-0 py-2 small text-danger">
                            <span>Potongan Diskon PO:</span>
                            <span>-Rp {{ number_format($purchase->discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="list-group-item d-flex justify-content-between px-0 py-2 fs-5 fw-bold text-dark">
                        <span>Grand Total PO:</span>
                        <span class="text-primary">Rp {{ number_format($purchase->grand_total, 0, ',', '.') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2 small">
                        <span class="text-muted">Jumlah Dibayar:</span>
                        <span class="fw-semibold text-success">Rp {{ number_format($purchase->paid_amount, 0, ',', '.') }}</span>
                    </div>
                    @if($purchase->grand_total > $purchase->paid_amount)
                        <div class="list-group-item d-flex justify-content-between px-0 py-2 small text-danger fw-bold">
                            <span>Sisa Hutang:</span>
                            <span>Rp {{ number_format($purchase->grand_total - $purchase->paid_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
