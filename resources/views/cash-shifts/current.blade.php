@extends('layouts.app')

@section('title', 'Sesi Shift Kasir Aktif')

@section('content')
<div class="container-fluid px-0" style="max-width: 1000px;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold mb-1">Sesi Shift Kasir Berjalan</h4>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                    <span class="spinner-grow spinner-grow-sm me-1" role="status"></span> Aktif
                </span>
            </div>
            <p class="text-muted small mb-0">Kasir: <strong>{{ $currentShift->user?->name }}</strong> &bull; Dimulai sejak: {{ $currentShift->opened_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#cashTransactionModal">
                <i class="bi bi-cash me-1"></i> Catat Kas Masuk/Keluar
            </button>
            <a href="{{ route('pos.index') }}" class="btn btn-warning fw-bold text-dark btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-cart me-1"></i> Kembali ke POS
            </a>
        </div>
    </div>

    <!-- Shift Financial Summary Grid -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block mb-1">Modal Awal</small>
                <h5 class="fw-bold text-dark mb-0">Rp {{ number_format($summary['opening_cash'], 0, ',', '.') }}</h5>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block mb-1">Penjualan Tunai (+)</small>
                <h5 class="fw-bold text-success mb-0">+ Rp {{ number_format($summary['cash_sales'], 0, ',', '.') }}</h5>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block mb-1">Kas Keluar / Biaya (-)</small>
                <h5 class="fw-bold text-danger mb-0">- Rp {{ number_format($summary['cash_out'], 0, ',', '.') }}</h5>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block mb-1">Refund Retur (-)</small>
                <h5 class="fw-bold text-danger mb-0">- Rp {{ number_format($summary['cash_refunds'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>

    <!-- Expected Cash in Register Banner -->
    <div class="card card-custom p-4 mb-4 border-primary bg-primary bg-opacity-10">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <small class="text-primary fw-bold text-uppercase d-block mb-1">Estimasi Saldo Kas Fisik di Laci (Expected Cash)</small>
                <h2 class="fw-bold text-primary mb-1">Rp {{ number_format($summary['expected_cash'], 0, ',', '.') }}</h2>
                <small class="text-muted">Rumus: Modal Awal + Penjualan Tunai + Kas Masuk - Kas Keluar - Refund Retur Tunai</small>
            </div>
            <div class="text-md-end">
                <span class="badge bg-light text-secondary border px-3 py-2">
                    Penjualan Non-Tunai (QRIS/Transfer): Rp {{ number_format($summary['non_cash_sales'], 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Closing Shift Form -->
    <div class="card card-custom p-4 mb-4">
        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-door-closed text-danger me-2"></i> Tutup Shift Kasir (Closing)</h5>
        <p class="text-muted small">Hitung seluruh uang fisik di laci kasir saat ini. Masukkan nominal uang yang dihitung secara nyata untuk melihat selisih kas.</p>

        <form action="{{ route('cash-shifts.close') }}" method="POST" id="closeShiftForm">
            @csrf
            <input type="hidden" name="cash_shift_id" value="{{ $currentShift->id }}">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Uang Fisik Nyata di Laci (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light text-muted">Rp</span>
                        <input type="text" inputmode="numeric" name="closing_cash_actual" id="actualCashInput" class="form-control fw-bold fs-4 text-end input-rupiah" value="{{ $summary['expected_cash'] }}" required oninput="calcDifference()">
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="setCashPas()">
                            <i class="bi bi-check2-all me-1"></i> Set Sesuai Saldo Sistem (Uang Pas)
                        </button>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Selisih Kas Real-time</label>
                    <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Status Kas:</span>
                        <span class="fw-bold fs-5 text-success" id="diffBadgeText">Cocok (Rp 0)</span>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Catatan Closing</label>
                    <input type="text" name="notes" class="form-control" placeholder="Catatan shift, kendala transaksi kasir, dll.">
                </div>

                <div class="col-12 mt-3 pt-3 border-top d-flex justify-content-end">
                    <button type="button" class="btn btn-danger px-4 py-2 fw-bold shadow-sm rounded-pill d-flex align-items-center gap-2" onclick="openConfirmCloseModal()">
                        <i class="bi bi-lock-fill"></i> Tutup Shift & Simpan Rekonsiliasi
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Shift Cash Logs -->
    @if($transactions->count() > 0)
        <div class="card card-custom p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-journal-text me-2"></i> Riwayat Arus Kas Selama Shift</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Waktu</th>
                            <th>Tipe Kas</th>
                            <th>Kategori</th>
                            <th class="text-end">Jumlah</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $t)
                            <tr>
                                <td>{{ $t->created_at->format('H:i') }}</td>
                                <td>
                                    @if($t->type === 'in')
                                        <span class="badge bg-success-subtle text-success">Masuk</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Keluar</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-light text-dark border text-uppercase">{{ $t->category }}</span></td>
                                <td class="text-end fw-bold {{ $t->type === 'in' ? 'text-success' : 'text-danger' }}">
                                    {{ $t->type === 'in' ? '+' : '-' }}Rp {{ number_format($t->amount, 0, ',', '.') }}
                                </td>
                                <td class="small text-muted">{{ $t->description ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<!-- Modal Catat Kas Masuk/Keluar Manual -->
<div class="modal fade" id="cashTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('cash-transactions.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Catat Transaksi Kas Laci</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jenis Kas</label>
                        <select name="type" class="form-select" required>
                            <option value="out">Kas Keluar (Biaya / Operasional / Pengeluaran)</option>
                            <option value="in">Kas Masuk (Tambah Modal / Penerimaan Lain)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori Kas</label>
                        <select name="category" class="form-select" required>
                            <option value="expense">Biaya Operasional Toko (Listrik, Makan, dll.)</option>
                            <option value="capital">Penambahan Modal Kasir</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="text" inputmode="numeric" name="amount" class="form-control input-rupiah" placeholder="Contoh: 25.000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Keterangan Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control" placeholder="Contoh: Beli pulsa listrik token toko" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Catatan Kas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Tutup Shift -->
<div class="modal fade" id="confirmCloseModal" tabindex="-1" aria-labelledby="confirmCloseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="confirmCloseModalLabel">
                    <i class="bi bi-shield-exclamation text-danger fs-4"></i> Konfirmasi Closing Shift
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="text-muted small mb-3">Pastikan Anda telah menghitung seluruh uang tunai fisik di laci kasir secara teliti. Setelah shift ditutup, transaksi shift ini akan dikunci dan direkonsiliasi secara permanen.</p>

                <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Saldo Kas Sistem (Expected):</span>
                        <span class="fw-bold text-primary">Rp {{ number_format($summary['expected_cash'], 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Uang Fisik Dihitung (Actual):</span>
                        <span class="fw-bold text-dark" id="modalActualCashDisplay">Rp 0</span>
                    </div>
                    <hr class="my-2 border-secondary-subtle">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold small">Status Selisih Kas:</span>
                        <span class="fw-bold" id="modalDiffCashDisplay">Rp 0</span>
                    </div>
                </div>

                <div class="alert alert-warning d-flex align-items-center gap-2 py-2 px-3 small mb-0 rounded-3">
                    <i class="bi bi-info-circle-fill fs-5"></i>
                    <div>Shift yang telah ditutup tidak dapat dibuka kembali. Kasir berikutnya harus membuka shift baru.</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Periksa Kembali</button>
                <button type="button" id="btnSubmitCloseShift" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" onclick="submitCloseShiftForm()">
                    <i class="bi bi-lock-fill me-1"></i> Ya, Tutup Shift Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const expectedCash = {{ $summary['expected_cash'] }};
    let confirmModal = null;

    function formatRupiah(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    function setCashPas() {
        const actualInput = document.getElementById('actualCashInput');
        actualInput.value = formatRupiahString(expectedCash);
        calcDifference();
    }

    function calcDifference() {
        const raw = (document.getElementById('actualCashInput').value || '').toString().replace(/\./g, '');
        const actualVal = parseFloat(raw) || 0;
        const diff = actualVal - expectedCash;
        const badge = document.getElementById('diffBadgeText');

        if (diff === 0) {
            badge.innerText = 'Cocok (Rp 0)';
            badge.className = 'fw-bold fs-5 text-success';
        } else if (diff > 0) {
            badge.innerText = 'Lebih: +Rp ' + formatRupiah(diff);
            badge.className = 'fw-bold fs-5 text-primary';
        } else {
            badge.innerText = 'Kurang: -Rp ' + formatRupiah(Math.abs(diff));
            badge.className = 'fw-bold fs-5 text-danger';
        }
        return { actualVal, diff };
    }

    function openConfirmCloseModal() {
        const actualInput = document.getElementById('actualCashInput');
        const cleanVal = (actualInput.value || '').toString().replace(/\./g, '');
        if (cleanVal === '' || isNaN(parseFloat(cleanVal))) {
            alert('Silakan masukkan jumlah uang fisik nyata di laci!');
            actualInput.focus();
            return;
        }

        const { actualVal, diff } = calcDifference();
        
        document.getElementById('modalActualCashDisplay').innerText = 'Rp ' + formatRupiah(actualVal);
        
        const modalDiffDisplay = document.getElementById('modalDiffCashDisplay');
        if (diff === 0) {
            modalDiffDisplay.innerText = 'Cocok (Rp 0)';
            modalDiffDisplay.className = 'fw-bold text-success';
        } else if (diff > 0) {
            modalDiffDisplay.innerText = '+Rp ' + formatRupiah(diff) + ' (Kelebihan)';
            modalDiffDisplay.className = 'fw-bold text-primary';
        } else {
            modalDiffDisplay.innerText = '-Rp ' + formatRupiah(Math.abs(diff)) + ' (Kekurangan)';
            modalDiffDisplay.className = 'fw-bold text-danger';
        }

        if (!confirmModal) {
            const modalEl = document.getElementById('confirmCloseModal');
            confirmModal = new bootstrap.Modal(modalEl);
        }
        confirmModal.show();
    }

    function submitCloseShiftForm() {
        const btn = document.getElementById('btnSubmitCloseShift');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses Closing...';
        document.getElementById('closeShiftForm').submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        calcDifference();
    });
</script>
@endpush
@endsection
