@extends('layouts.app')

@section('title', 'Hutang Supplier: ' . $supplier->name)

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Rincian & Pembayaran Hutang Supplier: {{ $supplier->name }}</h4>
            <p class="text-muted small mb-0">
                Kode: <span class="font-monospace fw-semibold">{{ $supplier->code }}</span>
                @if($supplier->contact_person) &bull; PIC: {{ $supplier->contact_person }} @endif
                @if($supplier->phone) &bull; Telp: {{ $supplier->phone }} @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @if(session('last_payment_id'))
                <a href="{{ route('payables.payment.receipt', session('last_payment_id')) }}" target="_blank" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm fw-semibold">
                    <i class="bi bi-printer-fill me-1"></i> Cetak Bukti Bayar Terakhir
                </a>
            @endif
            <a href="{{ route('payables.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Hutang Supplier
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Bulk Payment Form & FIFO Allocation Preview -->
        <div class="col-12 col-lg-5">
            <div class="card card-custom p-4 border-top border-4 {{ $totalRemainingDebt > 0 ? 'border-danger' : 'border-success' }}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <small class="text-muted d-block">Total Sisa Hutang ke Supplier Ini</small>
                        <h3 class="fw-bold {{ $totalRemainingDebt > 0 ? 'text-danger' : 'text-success' }} mb-0">
                            Rp {{ number_format($totalRemainingDebt, 0, ',', '.') }}
                        </h3>
                    </div>
                    <span class="badge {{ $totalRemainingDebt > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} px-3 py-2 rounded-pill">
                        {{ $unpaidPurchases->count() }} PO Belum Lunas
                    </span>
                </div>

                @if($totalRemainingDebt > 0)
                    <hr>
                    <h6 class="fw-bold mb-3"><i class="bi bi-cash-coin text-success me-2"></i>Form Bayar Hutang Supplier Sekaligus</h6>
                    <form action="{{ route('payables.pay', $supplier->id) }}" method="POST" id="bulkPaySupplierForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Target Alokasi Pembayaran PO</label>
                            <select name="purchase_id" id="selectTargetPurchase" class="form-select" onchange="updateSupplierFifoPreview()">
                                <option value="" data-max="{{ $totalRemainingDebt }}">Semua PO Belum Lunas (Otomatis PO Terlama / FIFO)</option>
                                @foreach($unpaidPurchases as $p)
                                    <option value="{{ $p->id }}" data-max="{{ $p->remaining_debt }}">
                                        Hanya PO {{ $p->purchase_number }} (Sisa: Rp {{ number_format($p->remaining_debt, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold mb-0">Nominal Pembayaran (Rp) <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" onclick="fillFullSupplierDebt()">
                                    Bayar Lunas Semua
                                </button>
                            </div>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light fw-bold">Rp</span>
                                <input type="text" name="amount" id="inputSupplierPayAmount" class="form-control fw-bold text-end fs-4" placeholder="0" required oninput="formatSupplierPayInput(this)">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Metode Pembayaran</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="transfer">Transfer Bank</option>
                                    <option value="cash">Tunai (Keluar dari Kas Laci)</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Tanggal Bayar</label>
                                <input type="datetime-local" name="payment_date" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Catatan / Referensi Bukti Transfer</label>
                            <input type="text" name="notes" class="form-control" placeholder="Contoh: Transfer Mandiri Ref #12345...">
                        </div>

                        <!-- Live FIFO Allocation Simulation Box -->
                        <div class="p-3 bg-light rounded-3 border mb-3" id="supplierFifoPreviewContainer">
                            <div class="small fw-bold text-secondary mb-2">
                                <i class="bi bi-magic me-1 text-primary"></i> Simulasi Alokasi ke Faktur PO:
                            </div>
                            <div id="supplierFifoPreviewList" class="small text-muted">
                                Ketik nominal pembayaran untuk melihat PO mana saja yang akan terpotong otomatis.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm rounded-3">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Pembayaran Hutang Supplier
                        </button>
                    </form>
                @else
                    <div class="alert alert-success border-0 mb-0 mt-2 d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                        <div>Seluruh tagihan PO ke supplier ini sudah <strong>LUNAS</strong>.</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column: Unpaid POs & Payment History -->
        <div class="col-12 col-lg-7">
            <div class="card card-custom mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-bag-x text-danger me-2"></i>Daftar PO Belum Lunas (Urutan FIFO)</h6>
                    <span class="badge bg-light text-secondary border">Diprioritaskan dari PO terlama</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>No. PO</th>
                                <th>Tgl Pembelian</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-end">Total PO</th>
                                <th class="text-end">Sudah Dibayar</th>
                                <th class="text-end">Sisa Hutang</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($unpaidPurchases as $po)
                                @php
                                    $isOverdue = $po->due_date && $po->due_date->isPast();
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('purchases.show', $po->id) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                                            {{ $po->purchase_number }}
                                        </a>
                                    </td>
                                    <td>{{ $po->purchase_date->format('d/m/Y') }}</td>
                                    <td>
                                        @if($po->due_date)
                                            <span class="badge {{ $isOverdue ? 'bg-danger text-white' : 'bg-light text-dark border' }}">
                                                {{ $po->due_date->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">Rp {{ number_format($po->grand_total, 0, ',', '.') }}</td>
                                    <td class="text-end text-success">Rp {{ number_format($po->paid_amount, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-danger">Rp {{ number_format($po->remaining_debt, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if($po->payment_status === 'partial')
                                            <span class="badge bg-warning-subtle text-warning">Cicilan</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Hutang</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Tidak ada PO yang belum lunas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment History -->
            <div class="card card-custom mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Pembayaran Hutang ke Supplier Ini</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>No. Bukti</th>
                                <th>Tanggal</th>
                                <th>Metode</th>
                                <th>Alokasi PO</th>
                                <th class="text-end">Total Bayar</th>
                                <th class="text-center">Cetak</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $pmt)
                                <tr>
                                    <td class="fw-bold font-monospace text-primary">{{ $pmt->payment_number }}</td>
                                    <td>{{ $pmt->payment_date->format('d/m/Y H:i') }}</td>
                                    <td><span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $pmt->payment_method }}</span></td>
                                    <td>
                                        @foreach($pmt->allocations as $alloc)
                                            <div class="small">
                                                <a href="{{ route('purchases.show', $alloc->purchase_id) }}" class="text-decoration-none font-monospace">{{ $alloc->purchase?->purchase_number }}</a>:
                                                <strong class="text-dark">Rp {{ number_format($alloc->amount, 0, ',', '.') }}</strong>
                                            </div>
                                        @endforeach
                                        @if($pmt->notes)
                                            <small class="text-muted d-block">Catatan: {{ $pmt->notes }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-success">Rp {{ number_format($pmt->total_paid, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('payables.payment.receipt', $pmt->id) }}" target="_blank" class="btn btn-light border btn-sm" title="Cetak Bukti Bayar">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat pembayaran hutang ke supplier ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($payments->hasPages())
                    <div class="p-3 border-top">
                        {{ $payments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($totalRemainingDebt > 0)
@php
    $unpaidPOsData = $unpaidPurchases->map(fn($p) => [
        'id' => $p->id,
        'purchase_number' => $p->purchase_number,
        'date' => $p->purchase_date->format('d/m/Y'),
        'remaining' => (float) $p->remaining_debt,
    ])->values();
@endphp
@push('scripts')
<script>
    const unpaidPOs = @json($unpaidPOsData);

    function formatRupiahSup(num) {
        return new Intl.NumberFormat('id-ID').format(Math.round(num));
    }

    function formatSupplierPayInput(el) {
        let raw = el.value.replace(/[^0-9]/g, '');
        if (!raw) {
            el.value = '';
            updateSupplierFifoPreview();
            return;
        }
        el.value = formatRupiahSup(parseInt(raw, 10));
        updateSupplierFifoPreview();
    }

    function fillFullSupplierDebt() {
        const select = document.getElementById('selectTargetPurchase');
        const max = parseFloat(select.options[select.selectedIndex].getAttribute('data-max')) || 0;
        const input = document.getElementById('inputSupplierPayAmount');
        input.value = formatRupiahSup(max);
        updateSupplierFifoPreview();
    }

    function updateSupplierFifoPreview() {
        const input = document.getElementById('inputSupplierPayAmount');
        const listEl = document.getElementById('supplierFifoPreviewList');
        const select = document.getElementById('selectTargetPurchase');
        if (!input || !listEl) return;

        const targetPoId = select.value ? parseInt(select.value, 10) : null;
        let amount = parseFloat((input.value || '').replace(/\./g, '')) || 0;

        if (amount <= 0) {
            listEl.innerHTML = 'Ketik nominal pembayaran untuk melihat PO mana saja yang akan terpotong otomatis.';
            return;
        }

        const pos = targetPoId
            ? unpaidPOs.filter(p => p.id === targetPoId)
            : unpaidPOs;

        let remainingMoney = amount;
        let html = '<ul class="list-unstyled mb-0">';

        pos.forEach(po => {
            if (remainingMoney <= 0) return;
            const alloc = Math.min(remainingMoney, po.remaining);
            const afterDebt = po.remaining - alloc;
            const statusBadge = afterDebt <= 0
                ? '<span class="badge bg-success-subtle text-success ms-1">Jadi LUNAS</span>'
                : `<span class="badge bg-warning-subtle text-warning ms-1">Sisa Rp ${formatRupiahSup(afterDebt)}</span>`;

            html += `
                <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                    <span><strong class="font-monospace text-dark">${po.purchase_number}</strong> <small class="text-muted">(${po.date})</small></span>
                    <span>Dibayar <strong>Rp ${formatRupiahSup(alloc)}</strong> ${statusBadge}</span>
                </li>
            `;
            remainingMoney -= alloc;
        });

        html += '</ul>';

        if (remainingMoney > 0) {
            html += `<div class="text-danger fw-bold mt-2"><i class="bi bi-exclamation-circle me-1"></i>Nominal berlebih Rp ${formatRupiahSup(remainingMoney)} dari total tagihan!</div>`;
        }

        listEl.innerHTML = html;
    }
</script>
@endpush
@endif
@endsection
