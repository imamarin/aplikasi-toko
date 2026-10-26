@extends('layouts.app')

@section('title', 'Riwayat Transaksi Penjualan')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Riwayat Transaksi Penjualan</h4>
            <p class="text-muted small mb-0">Daftar seluruh nota penjualan, status pembayaran, dan cetak ulang bukti transaksi</p>
        </div>
        <a href="{{ route('pos.index') }}" class="btn btn-warning fw-bold text-dark px-4 shadow-sm rounded-pill">
            <i class="bi bi-cart-plus me-1"></i> Buka Kasir (POS)
        </a>
    </div>

    <!-- Filter Card -->
    <div class="card card-custom p-3 mb-4">
        <form method="GET" action="{{ route('sales.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nomor nota, pelanggan..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" placeholder="Dari tanggal">
            </div>
            <div class="col-6 col-md-3">
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" placeholder="Sampai tanggal">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom w-100">Filter</button>
                @if(request()->hasAny(['search', 'start_date', 'end_date']))
                    <a href="{{ route('sales.index') }}" class="btn btn-light border"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </div>

    <!-- Sales Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>No. Invoice</th>
                        <th>Tanggal Transaksi</th>
                        <th>Kasir</th>
                        <th>Pelanggan</th>
                        <th>Metode Bayar</th>
                        <th class="text-end">Total Belanja</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $index => $sale)
                        <tr>
                            <td>{{ $sales->firstItem() + $index }}</td>
                            <td>
                                <a href="{{ route('sales.show', $sale->id) }}" class="fw-bold text-primary font-monospace text-decoration-none">
                                    {{ $sale->invoice_number }}
                                </a>
                            </td>
                            <td>{{ $sale->transaction_date->format('d/m/Y H:i') }}</td>
                            <td>{{ $sale->user?->name ?? 'Kasir' }}</td>
                            <td>{{ $sale->customer?->name ?? 'Umum (Walk-in)' }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary text-uppercase px-3 py-1">
                                    {{ $sale->payment_method }}
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                Rp {{ number_format($sale->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                @if($sale->status === 'completed')
                                    <span class="badge bg-success-subtle text-success">Selesai</span>
                                @elseif($sale->status === 'returned')
                                    <span class="badge bg-warning-subtle text-warning">Ada Retur</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">{{ $sale->status }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-light border" title="Lihat Rincian">
                                        <i class="bi bi-eye text-primary"></i>
                                    </a>
                                    <a href="{{ route('sales.receipt', $sale->id) }}" target="_blank" class="btn btn-light border" title="Cetak Struk">
                                        <i class="bi bi-printer text-dark"></i>
                                    </a>
                                    <a href="{{ route('sales.pdf', $sale->id) }}" class="btn btn-light border" title="Download PDF">
                                        <i class="bi bi-file-earmark-pdf text-danger"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                Belum ada data transaksi penjualan yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sales->hasPages())
            <div class="p-3 border-top">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
