@extends('layouts.app')

@section('title', 'Retur Pembelian Supplier')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Retur Pembelian ke Supplier</h4>
            <p class="text-muted small mb-0">Daftar pengembalian barang rusak atau cacat ke distributor dan pengurangan stok</p>
        </div>
        <a href="{{ route('returns.purchases.create') }}" class="btn btn-primary-custom d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-plus-circle-fill"></i> Buat Retur Pembelian Baru
        </a>
    </div>

    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>No. Retur</th>
                        <th>No. PO Asal</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th class="text-end">Total Nilai Barang</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $index => $ret)
                        <tr>
                            <td>{{ $returns->firstItem() + $index }}</td>
                            <td>
                                <a href="{{ route('returns.purchases.show', $ret->id) }}" class="fw-bold text-primary font-monospace text-decoration-none">
                                    {{ $ret->return_number }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('purchases.show', $ret->purchase_id) }}" class="font-monospace text-secondary text-decoration-none">
                                    {{ $ret->purchase?->purchase_number }}
                                </a>
                            </td>
                            <td>{{ $ret->return_date->format('d/m/Y') }}</td>
                            <td>{{ $ret->supplier?->name ?? '-' }}</td>
                            <td class="text-end fw-bold text-dark">
                                Rp {{ number_format($ret->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">Selesai</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('returns.purchases.show', $ret->id) }}" class="btn btn-sm btn-light border" title="Lihat Rincian">
                                    <i class="bi bi-eye text-primary"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Belum ada transaksi retur pembelian ke supplier.</td>
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
