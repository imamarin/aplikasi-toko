@extends('layouts.app')

@section('title', 'Detail Nota #' . $sale->invoice_number)

@section('content')
<div class="container-fluid px-0" style="max-width: 950px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Invoice Penjualan: {{ $sale->invoice_number }}</h4>
            <p class="text-muted small mb-0">Rincian produk yang dibeli, pembayaran, dan informasi kasir pelaksana</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sales.receipt', $sale->id) }}" target="_blank" class="btn btn-primary-custom btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-printer me-1"></i> Cetak Struk
            </a>
            <a href="{{ route('sales.pdf', $sale->id) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
            </a>
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Invoice Header Box -->
    <div class="card card-custom p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">Tanggal Transaksi</small>
                <div class="fw-bold">{{ $sale->transaction_date->format('d F Y, H:i') }} WIB</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Kasir Pelaksana</small>
                <div class="fw-bold">{{ $sale->user?->name ?? 'Kasir' }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Pelanggan</small>
                <div class="fw-bold">{{ $sale->customer?->name ?? 'Umum (Walk-in)' }}</div>
                <small class="text-muted">{{ $sale->customer?->phone ?? '' }}</small>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Metode Pembayaran</small>
                <span class="badge bg-secondary text-uppercase px-3 py-1 mt-1">{{ $sale->payment_method }}</span>
            </div>
        </div>
    </div>

    <!-- Purchased Items Table -->
    <div class="card card-custom p-4 mb-4">
        <h6 class="fw-bold mb-3">Item Pembelian</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Nama Produk</th>
                        <th class="text-end">Harga Satuan</th>
                        <th class="text-center">Kuantitas</th>
                        <th class="text-end">Diskon Item</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->details as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $item->product_name }}</div>
                                <small class="text-muted">{{ $item->product?->category?->name ?? '' }}</small>
                            </td>
                            <td class="text-end">Rp {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                            <td class="text-center fw-bold">{{ rtrim(rtrim(number_format((float) $item->qty, 3, ',', '.'), '0'), ',') }} {{ $item->product?->unit?->short_name ?? '' }}</td>
                            <td class="text-end text-muted">
                                {{ $item->discount_amount > 0 ? '-Rp ' . number_format($item->discount_amount, 0, ',', '.') : '-' }}
                            </td>
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
                        <span class="fw-semibold">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($sale->discount_amount > 0)
                        <div class="list-group-item d-flex justify-content-between px-0 py-2 small text-danger">
                            <span>Diskon Nota:</span>
                            <span>-Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($sale->tax_amount > 0)
                        <div class="list-group-item d-flex justify-content-between px-0 py-2 small">
                            <span class="text-muted">PPN ({{ $sale->tax_percentage }}%):</span>
                            <span>+Rp {{ number_format($sale->tax_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="list-group-item d-flex justify-content-between px-0 py-2 fs-5 fw-bold text-primary">
                        <span>Grand Total:</span>
                        <span>Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2 small">
                        <span class="text-muted">Uang Dibayar:</span>
                        <span class="fw-semibold">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2 small text-success fw-bold">
                        <span>Kembalian:</span>
                        <span>Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Return History if Exists -->
    @if($sale->returns->count() > 0)
        <div class="card card-custom p-4 border-warning">
            <h6 class="fw-bold mb-3 text-warning"><i class="bi bi-arrow-counterclockwise me-2"></i> Riwayat Retur untuk Transaksi Ini</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>No. Retur</th>
                            <th>Tanggal</th>
                            <th>Total Refund</th>
                            <th>Metode Refund</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->returns as $ret)
                            <tr>
                                <td class="fw-bold font-monospace">{{ $ret->return_number }}</td>
                                <td>{{ $ret->return_date->format('d/m/Y H:i') }}</td>
                                <td class="fw-bold text-danger">Rp {{ number_format($ret->total_refund, 0, ',', '.') }}</td>
                                <td><span class="badge bg-light text-dark border text-uppercase">{{ $ret->refund_method }}</span></td>
                                <td class="small text-muted">{{ $ret->notes ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
