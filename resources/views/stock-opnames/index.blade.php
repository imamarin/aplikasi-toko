@extends('layouts.app')

@section('title', 'Riwayat Stok Opname')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Riwayat Stok Opname</h4>
            <p class="text-muted small mb-0">Daftar pemeriksaan fisik inventori dan penyesuaian selisih stok sistem</p>
        </div>
        <a href="{{ route('stock-opnames.create') }}" class="btn btn-primary-custom d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-plus-circle-fill"></i> Buat Stok Opname Baru
        </a>
    </div>

    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>No. Dokumen Opname</th>
                        <th>Tanggal Opname</th>
                        <th>Petugas Pelaksana</th>
                        <th class="text-center">Jumlah Item Diperiksa</th>
                        <th>Catatan</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opnames as $index => $op)
                        <tr>
                            <td>{{ $opnames->firstItem() + $index }}</td>
                            <td><span class="badge bg-light text-primary border font-monospace fs-6">{{ $op->opname_number }}</span></td>
                            <td>{{ $op->opname_date->format('d/m/Y') }}</td>
                            <td>{{ $op->user?->name ?? 'Admin' }}</td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill">
                                    {{ $op->details->count() }} Produk
                                </span>
                            </td>
                            <td class="small text-muted">{{ $op->notes ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                    Terselesaikan
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('stock-opnames.show', $op->id) }}" class="btn btn-sm btn-light border" title="Lihat Rincian Selisih">
                                    <i class="bi bi-eye text-primary"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Belum ada riwayat pelaksanaan stok opname.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($opnames->hasPages())
            <div class="p-3 border-top">
                {{ $opnames->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
