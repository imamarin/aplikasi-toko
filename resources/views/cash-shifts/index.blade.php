@extends('layouts.app')

@section('title', 'Manajemen Shift Kasir')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Manajemen Shift & Kas Laci Kasir</h4>
            <p class="text-muted small mb-0">Kelola pembukaan modal awal kasir, rekonsiliasi kas aktual, dan closing shift</p>
        </div>
        <div class="d-flex gap-2">
            @if($currentShift)
                <a href="{{ route('cash-shifts.current') }}" class="btn btn-success fw-bold rounded-pill px-4 shadow-sm d-flex align-items-center gap-2">
                    <span class="spinner-grow spinner-grow-sm" role="status"></span>
                    Kelola Shift Kasir Aktif
                </a>
            @else
                <button type="button" class="btn btn-primary-custom d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#openShiftModal">
                    <i class="bi bi-wallet2"></i> Buka Shift Kasir Baru
                </button>
            @endif
        </div>
    </div>

    <!-- Active Shift Status Card if Opened -->
    @if($currentShift)
        <div class="card card-custom p-4 mb-4 border-success bg-success-subtle bg-opacity-10">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success text-white p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-unlock-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold mb-0">Shift Kasir Aktif: {{ $currentShift->user?->name }}</h5>
                            <span class="badge bg-success">Sedang Berjalan</span>
                        </div>
                        <small class="text-muted">Dimulai sejak: {{ $currentShift->opened_at->format('d/m/Y H:i') }} | Modal Awal: Rp {{ number_format($currentShift->opening_cash, 0, ',', '.') }}</small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('pos.index') }}" class="btn btn-warning fw-bold text-dark px-3 rounded-pill">
                        <i class="bi bi-calculator me-1"></i> Buka POS
                    </a>
                    <a href="{{ route('cash-shifts.current', ['shift_id' => $currentShift->id]) }}" class="btn btn-outline-danger fw-semibold px-3 rounded-pill">
                        <i class="bi bi-box-arrow-right me-1"></i> Tutup Shift (Closing)
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Shift History Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Petugas Kasir</th>
                        <th>Waktu Buka</th>
                        <th>Waktu Tutup</th>
                        <th class="text-end">Modal Awal</th>
                        <th class="text-end">Saldo Sistem</th>
                        <th class="text-end">Fisik Nyata</th>
                        <th class="text-center">Selisih Kas</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $index => $shift)
                        <tr>
                            <td>{{ $shifts->firstItem() + $index }}</td>
                            <td class="fw-bold text-dark">{{ $shift->user?->name ?? 'Kasir' }}</td>
                            <td>{{ $shift->opened_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $shift->closed_at ? $shift->closed_at->format('d/m/Y H:i') : '-' }}</td>
                            <td class="text-end text-muted">Rp {{ number_format($shift->opening_cash, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold">
                                {{ $shift->closing_cash_system !== null ? 'Rp ' . number_format($shift->closing_cash_system, 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-end fw-bold text-dark">
                                {{ $shift->closing_cash_actual !== null ? 'Rp ' . number_format($shift->closing_cash_actual, 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-center">
                                @if($shift->difference === null)
                                    <span class="text-muted">-</span>
                                @elseif($shift->difference == 0)
                                    <span class="badge bg-success-subtle text-success">Cocok (Rp 0)</span>
                                @elseif($shift->difference > 0)
                                    <span class="badge bg-primary-subtle text-primary">+Rp {{ number_format($shift->difference, 0, ',', '.') }}</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">-Rp {{ number_format(abs($shift->difference), 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($shift->status === 'open')
                                    <span class="badge bg-success px-3 py-1 rounded-pill">Aktif</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-1 rounded-pill">Closed</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    @if($shift->status === 'open')
                                        <a href="{{ route('cash-shifts.current', ['shift_id' => $shift->id]) }}" class="btn btn-sm btn-outline-danger" title="Tutup Shift (Closing)">
                                            <i class="bi bi-box-arrow-right"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('cash-shifts.show', $shift->id) }}" class="btn btn-sm btn-light border" title="Lihat Rekonsiliasi">
                                        <i class="bi bi-eye text-primary"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">Belum ada riwayat shift kasir.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($shifts->hasPages())
            <div class="p-3 border-top">
                {{ $shifts->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Buka Shift Kasir Baru -->
<div class="modal fade" id="openShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('cash-shifts.open') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-wallet2 text-primary me-2"></i> Buka Sesi Shift Kasir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Silakan masukkan nominal uang modal awal kasir yang ada di dalam laci uang fisik saat mulai bertugas.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Saldo Kas Modal Awal (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light text-muted">Rp</span>
                            <input type="number" step="1" min="0" name="opening_cash" class="form-control fw-bold" placeholder="Contoh: 200000" value="200000" required autofocus>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Uang kembalian pecahan receh di awal shift</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan / Keterangan Shift</label>
                        <input type="text" name="notes" class="form-control" placeholder="Shift Pagi / Shift Sore...">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom px-4">
                        <i class="bi bi-unlock-fill me-1"></i> Buka Shift & Mulai POS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
