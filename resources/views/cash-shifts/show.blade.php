@extends('layouts.app')

@section('title', 'Hasil Rekonsiliasi Shift Kasir')

@section('content')
<div class="container-fluid px-0" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Hasil Audit & Rekonsiliasi Shift Kasir</h4>
            <p class="text-muted small mb-0">Petugas: <strong>{{ $cashShift->user?->name }}</strong> &bull; Status: {{ ucfirst($cashShift->status) }}</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-printer me-1"></i> Cetak Bukti Closing
            </button>
            <a href="{{ route('cash-shifts.index') }}" class="btn btn-primary-custom btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Shift Period Box -->
    <div class="card card-custom p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">Waktu Buka Shift</small>
                <div class="fw-bold">{{ $cashShift->opened_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Waktu Tutup Shift</small>
                <div class="fw-bold">{{ $cashShift->closed_at ? $cashShift->closed_at->format('d/m/Y H:i') : 'Masih Terbuka' }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Total Transaksi Selesai</small>
                <div class="fw-bold">{{ $cashShift->sales->count() }} Penjualan</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Catatan Shift</small>
                <div class="text-secondary small">{{ $cashShift->notes ?? '-' }}</div>
            </div>
        </div>
    </div>

    <!-- Financial Reconciliation Card -->
    <div class="card card-custom p-4 mb-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-calculator text-primary me-2"></i> Rekonsiliasi Kas Laci Fisik</h6>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <tbody>
                    <tr>
                        <td class="text-muted">1. Saldo Modal Awal Laci:</td>
                        <td class="text-end fw-semibold">Rp {{ number_format($cashShift->opening_cash, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">2. Penjualan Tunai (+):</td>
                        <td class="text-end fw-semibold text-success">+ Rp {{ number_format($summary['cash_sales'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">3. Kas Masuk Operasional (+):</td>
                        <td class="text-end fw-semibold text-success">+ Rp {{ number_format($summary['cash_in'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">4. Pengeluaran Kas / Biaya (-):</td>
                        <td class="text-end fw-semibold text-danger">- Rp {{ number_format($summary['cash_out'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">5. Refund Retur Tunai (-):</td>
                        <td class="text-end fw-semibold text-danger">- Rp {{ number_format($summary['cash_refunds'], 0, ',', '.') }}</td>
                    </tr>
                    <tr class="table-light fw-bold fs-6">
                        <td>Saldo Kas Sistem yang Seharusnya (Expected):</td>
                        <td class="text-end text-primary">Rp {{ number_format($cashShift->closing_cash_system ?? $summary['expected_cash'], 0, ',', '.') }}</td>
                    </tr>
                    <tr class="table-light fw-bold fs-6">
                        <td>Saldo Kas Fisik Nyata di Tangan (Actual):</td>
                        <td class="text-end text-dark">Rp {{ number_format($cashShift->closing_cash_actual ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="table-warning fw-bold fs-5">
                        <td>Selisih Kas (Discrepancy):</td>
                        <td class="text-end {{ $cashShift->difference < 0 ? 'text-danger' : ($cashShift->difference > 0 ? 'text-primary' : 'text-success') }}">
                            @if($cashShift->difference == 0)
                                Rp 0 (Cocok)
                            @elseif($cashShift->difference > 0)
                                + Rp {{ number_format($cashShift->difference, 0, ',', '.') }} (Kelebihan)
                            @else
                                - Rp {{ number_format(abs($cashShift->difference), 0, ',', '.') }} (Kekurangan)
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
