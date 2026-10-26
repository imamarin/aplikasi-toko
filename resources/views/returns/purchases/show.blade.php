@extends('layouts.app')

@section('title', 'Detail Retur Pembelian #' . $purchaseReturn->return_number)

@section('content')
<div class="container-fluid px-0" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Bukti Retur Supplier: {{ $purchaseReturn->return_number }}</h4>
            <p class="text-muted small mb-0">Rincian barang dagangan yang dikembalikan ke pihak distributor/supplier</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-printer me-1"></i> Cetak Dokumen
            </button>
            <a href="{{ route('returns.purchases.index') }}" class="btn btn-primary-custom btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card card-custom p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">Tanggal Retur</small>
                <div class="fw-bold">{{ $purchaseReturn->return_date->format('d/m/Y') }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">No. Faktur PO Asal</small>
                <a href="{{ route('purchases.show', $purchaseReturn->purchase_id) }}" class="fw-bold font-monospace text-decoration-none">
                    {{ $purchaseReturn->purchase?->purchase_number }}
                </a>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Supplier Tujuan</small>
                <div class="fw-bold">{{ $purchaseReturn->supplier?->name ?? '-' }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Status Penyesuaian</small>
                <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">Selesai (Stok Dipotong)</span>
            </div>
        </div>
    </div>

    <div class="card card-custom p-4 mb-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-box-arrow-right text-danger me-2"></i> Barang yang Dikembalikan</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th class="text-center">Kuantitas</th>
                        <th class="text-end">Harga Beli</th>
                        <th class="text-end">Subtotal</th>
                        <th>Alasan Retur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseReturn->details as $idx => $d)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td class="fw-bold text-dark">{{ $d->product?->name }}</td>
                            <td class="text-center fw-bold">{{ $d->qty }} {{ $d->product?->unit?->short_name }}</td>
                            <td class="text-end">Rp {{ number_format($d->cost_price, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($d->subtotal, 0, ',', '.') }}</td>
                            <td class="small text-muted">{{ $d->reason ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end">Total Nilai Barang Retur:</td>
                        <td class="text-end text-dark fs-5">Rp {{ number_format($purchaseReturn->total_amount, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
