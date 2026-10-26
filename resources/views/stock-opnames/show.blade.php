@extends('layouts.app')

@section('title', 'Detail Stok Opname #' . $stockOpname->opname_number)

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Dokumen Stok Opname: {{ $stockOpname->opname_number }}</h4>
            <p class="text-muted small mb-0">Rincian perbandingan persediaan fisik dan hasil rekonsiliasi stok</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-printer me-1"></i> Cetak Hasil Opname
            </button>
            <a href="{{ route('stock-opnames.index') }}" class="btn btn-primary-custom btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Header Summary Card -->
    <div class="card card-custom p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">Tanggal Pelaksanaan</small>
                <div class="fw-bold">{{ $stockOpname->opname_date->format('d F Y') }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Petugas Pelaksana</small>
                <div class="fw-bold">{{ $stockOpname->user?->name ?? 'Admin' }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Status Penyesuaian</small>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                    Telah Disesuaikan ke Stok Fisik
                </span>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Catatan Dokumen</small>
                <div class="text-secondary small">{{ $stockOpname->notes ?? '-' }}</div>
            </div>
        </div>
    </div>

    <!-- Details Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Kode SKU</th>
                        <th>Nama Produk</th>
                        <th class="text-center">Stok Sistem</th>
                        <th class="text-center">Stok Fisik Nyata</th>
                        <th class="text-center">Selisih Unit</th>
                        <th class="text-end">Harga Modal (HPP)</th>
                        <th class="text-end">Estimasi Nilai Selisih</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalDiffValue = 0;
                    @endphp
                    @foreach($stockOpname->details as $index => $det)
                        @php
                            $diffValue = $det->difference * $det->cost_price;
                            $totalDiffValue += $diffValue;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><span class="badge bg-light text-dark border font-monospace">{{ $det->product?->sku }}</span></td>
                            <td class="fw-semibold text-dark">{{ $det->product?->name }}</td>
                            <td class="text-center">{{ $det->system_stock }}</td>
                            <td class="text-center fw-bold">{{ $det->physical_stock }}</td>
                            <td class="text-center">
                                @if($det->difference > 0)
                                    <span class="badge bg-success px-3 py-1">+{{ $det->difference }}</span>
                                @elseif($det->difference < 0)
                                    <span class="badge bg-danger px-3 py-1">{{ $det->difference }}</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-1">Cocok (0)</span>
                                @endif
                            </td>
                            <td class="text-end text-muted small">Rp {{ number_format($det->cost_price, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold {{ $diffValue < 0 ? 'text-danger' : ($diffValue > 0 ? 'text-success' : 'text-muted') }}">
                                {{ $diffValue > 0 ? '+' : '' }}Rp {{ number_format($diffValue, 0, ',', '.') }}
                            </td>
                            <td class="small text-muted">{{ $det->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="7" class="text-end">Total Dampak Nilai Finansial Selisih:</td>
                        <td class="text-end {{ $totalDiffValue < 0 ? 'text-danger' : ($totalDiffValue > 0 ? 'text-success' : 'text-dark') }}">
                            {{ $totalDiffValue > 0 ? '+' : '' }}Rp {{ number_format($totalDiffValue, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
