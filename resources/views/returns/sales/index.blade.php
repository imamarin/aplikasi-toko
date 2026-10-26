@extends('layouts.app')

@section('title', 'Retur Penjualan Konsumen')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Retur Penjualan (Customer Returns)</h4>
            <p class="text-muted small mb-0">Pengembalian barang dari pelanggan, pemulihan stok inventori, dan pengembalian dana</p>
        </div>
        <a href="{{ route('returns.sales.create') }}" class="btn btn-primary-custom d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-plus-circle-fill"></i> Buat Retur Penjualan Baru
        </a>
    </div>

    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>No. Dokumen Retur</th>
                        <th>No. Invoice Asal</th>
                        <th>Tanggal Retur</th>
                        <th>Pelanggan</th>
                        <th>Metode Refund</th>
                        <th class="text-end">Total Nilai Refund</th>
                        <th class="text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $index => $ret)
                        <tr>
                            <td>{{ $returns->firstItem() + $index }}</td>
                            <td>
                                <a href="{{ route('returns.sales.show', $ret->id) }}" class="fw-bold text-primary font-monospace text-decoration-none">
                                    {{ $ret->return_number }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('sales.show', $ret->sale_id) }}" class="font-monospace text-secondary text-decoration-none">
                                    {{ $ret->sale?->invoice_number }}
                                </a>
                            </td>
                            <td>{{ $ret->return_date->format('d/m/Y H:i') }}</td>
                            <td>{{ $ret->sale?->customer?->name ?? 'Umum' }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $ret->refund_method }}</span>
                            </td>
                            <td class="text-end fw-bold text-danger">
                                Rp {{ number_format($ret->total_refund, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <a href="{{ route('returns.sales.show', $ret->id) }}" class="btn btn-sm btn-light border" title="Lihat Rincian Retur">
                                    <i class="bi bi-eye text-primary"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Belum ada transaksi retur penjualan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($returns->hasPages())
            <div class="p-3 border-top">
                {{ $returns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
