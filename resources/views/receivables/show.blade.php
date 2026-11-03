@extends('layouts.app')

@section('title', 'Piutang Pelanggan: ' . $customer->name)

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Rincian & Pelunasan Piutang: {{ $customer->name }}</h4>
            <p class="text-muted small mb-0">
                Kode: <span class="font-monospace fw-semibold">{{ $customer->code }}</span>
                @if($customer->phone) &bull; Telp/WA: {{ $customer->phone }} @endif
                @if($customer->address) &bull; Alamat: {{ $customer->address }} @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @if(session('last_payment_id'))
                <a href="{{ route('receivables.payment.receipt', session('last_payment_id')) }}" target="_blank" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm fw-semibold">
                    <i class="bi bi-printer-fill me-1"></i> Cetak Kwitansi Terakhir
                </a>
            @endif
            <a href="{{ route('receivables.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Piutang
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Bulk Payment Form & FIFO Allocation Preview -->
        <div class="col-12 col-lg-5">
            <div class="card card-custom p-4 border-top border-4 {{ $totalRemainingDebt > 0 ? 'border-danger' : 'border-success' }}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <small class="text-muted d-block">Total Sisa Hutang Saat Ini</small>
                        <h3 class="fw-bold {{ $totalRemainingDebt > 0 ? 'text-danger' : 'text-success' }} mb-0">
                            Rp {{ number_format($totalRemainingDebt, 0, ',', '.') }}
                        </h3>
                    </div>
                    <span class="badge {{ $totalRemainingDebt > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} px-3 py-2 rounded-pill">
                        {{ $unpaidSales->count() }} Nota Belum Lunas
                    </span>
                </div>

                @if($totalRemainingDebt > 0)
                    <hr>
                    <h6 class="fw-bold mb-3"><i class="bi bi-cash-coin text-success me-2"></i>Form Bayar Hutang Sekaligus</h6>
                    <form action="{{ route('receivables.pay', $customer->id) }}" method="POST" id="bulkPayForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Target Alokasi Pembayaran</label>
                            <select name="sale_id" id="selectTargetSale" class="form-select" onchange="updateFifoPreview()">
                                <option value="" data-max="{{ $totalRemainingDebt }}">Semua Nota Belum Lunas (Otomatis Nota Terlama / FIFO)</option>
                                @foreach($unpaidSales as $s)
                                    <option value="{{ $s->id }}" data-max="{{ $s->remaining_debt }}">
                                        Hanya Nota {{ $s->invoice_number }} (Sisa: Rp {{ number_format($s->remaining_debt, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold mb-0">Nominal Uang Diterima (Rp) <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" onclick="fillFullDebt()">
                                    Bayar Lunas Semua
                                </button>
                            </div>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light fw-bold">Rp</span>
                                <input type="text" name="amount" id="inputPayAmount" class="form-control fw-bold text-end fs-4" placeholder="0" required oninput="formatPayInput(this)">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Metode Pembayaran</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="cash">Tunai (Masuk Kas Laci)</option>
                                    <option value="transfer">Transfer Bank</option>
                                    <option value="qris">QRIS</option>
                                    <option value="debit">Kartu Debit</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Tanggal Bayar</label>
                                <input type="datetime-local" name="payment_date" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Catatan / Keterangan</label>
                            <input type="text" name="notes" class="form-control" placeholder="Contoh: Cicilan ke-1 / Pelunasan via BCA...">
                        </div>

                        <!-- Live FIFO Allocation Simulation Box -->
                        <div class="p-3 bg-light rounded-3 border mb-3" id="fifoPreviewContainer">
                            <div class="small fw-bold text-secondary mb-2">
                                <i class="bi bi-magic me-1 text-primary"></i> Simulasi Alokasi ke Nota:
                            </div>
                            <div id="fifoPreviewList" class="small text-muted">
                                Ketik nominal pembayaran untuk melihat nota mana saja yang akan terpotong otomatis.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm rounded-3">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Pembayaran Piutang
                        </button>
                    </form>
                @else
                    <div class="alert alert-success border-0 mb-0 mt-2 d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                        <div>Seluruh tagihan piutang pelanggan ini sudah <strong>LUNAS</strong>.</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column: Unpaid Invoices & Payment History -->
        <div class="col-12 col-lg-7">
            <!-- Unpaid / Partial Invoices List -->
            <div class="card card-custom mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-receipt-cutoff text-danger me-2"></i>Daftar Nota Belum Lunas (Urutan FIFO)</h6>
                    <span class="badge bg-light text-secondary border">Diprioritaskan dari nota terlama</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>No. Nota</th>
                                <th>Tgl Transaksi</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-end">Total Nota</th>
                                <th class="text-end">Sudah Dibayar</th>
                                <th class="text-end">Sisa Hutang</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($unpaidSales as $sale)
                                @php
                                    $isOverdue = $sale->due_date && $sale->due_date->isPast();
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.show', $sale->id) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                                            {{ $sale->invoice_number }}
                                        </a>
                                    </td>
                                    <td>{{ $sale->transaction_date->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($sale->due_date)
                                            <span class="badge {{ $isOverdue ? 'bg-danger text-white' : 'bg-light text-dark border' }}">
                                                {{ $sale->due_date->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                                    <td class="text-end text-success">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-danger">Rp {{ number_format($sale->remaining_debt, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if($sale->payment_status === 'partial')
                                            <span class="badge bg-warning-subtle text-warning">Cicilan</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Belum Bayar</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Tidak ada nota yang belum lunas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment History -->
            <div class="card card-custom mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Pembayaran Piutang Pelanggan Ini</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>No. Kwitansi</th>
                                <th>Tanggal</th>
                                <th>Metode</th>
                                <th>Alokasi Nota</th>
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
                                                <a href="{{ route('sales.show', $alloc->sale_id) }}" class="text-decoration-none font-monospace">{{ $alloc->sale?->invoice_number }}</a>:
                                                <strong class="text-dark">Rp {{ number_format($alloc->amount, 0, ',', '.') }}</strong>
                                            </div>
                                        @endforeach
                                        @if($pmt->notes)
                                            <small class="text-muted d-block">Catatan: {{ $pmt->notes }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-success">Rp {{ number_format($pmt->total_paid, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('receivables.payment.receipt', $pmt->id) }}" target="_blank" class="btn btn-light border btn-sm" title="Cetak Kwitansi">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat pembayaran piutang.</td>
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
    $unpaidInvoicesData = $unpaidSales->map(fn($s) => [
        'id' => $s->id,
        'invoice_number' => $s->invoice_number,
        'date' => $s->transaction_date->format('d/m/Y'),
        'remaining' => (float) $s->remaining_debt,
    ])->values();
@endphp
@push('scripts')
<script>
    const unpaidInvoices = @json($unpaidInvoicesData);

    function formatRupiah(num) {
        return new Intl.NumberFormat('id-ID').format(Math.round(num));
    }

    function formatPayInput(el) {
        let raw = el.value.replace(/[^0-9]/g, '');
        if (!raw) {
            el.value = '';
            updateFifoPreview();
            return;
        }
        el.value = formatRupiah(parseInt(raw, 10));
        updateFifoPreview();
    }

    function fillFullDebt() {
        const select = document.getElementById('selectTargetSale');
        const max = parseFloat(select.options[select.selectedIndex].getAttribute('data-max')) || 0;
        const input = document.getElementById('inputPayAmount');
        input.value = formatRupiah(max);
        updateFifoPreview();
    }

    function updateFifoPreview() {
        const input = document.getElementById('inputPayAmount');
        const listEl = document.getElementById('fifoPreviewList');
        const select = document.getElementById('selectTargetSale');
        if (!input || !listEl) return;

        const targetSaleId = select.value ? parseInt(select.value, 10) : null;
        let amount = parseFloat((input.value || '').replace(/\./g, '')) || 0;

        if (amount <= 0) {
            listEl.innerHTML = 'Ketik nominal pembayaran untuk melihat nota mana saja yang akan terpotong otomatis.';
            return;
        }

        const invoices = targetSaleId
            ? unpaidInvoices.filter(i => i.id === targetSaleId)
            : unpaidInvoices;

        let remainingMoney = amount;
        let html = '<ul class="list-unstyled mb-0">';

        invoices.forEach(inv => {
            if (remainingMoney <= 0) return;
            const alloc = Math.min(remainingMoney, inv.remaining);
            const afterDebt = inv.remaining - alloc;
            const statusBadge = afterDebt <= 0
                ? '<span class="badge bg-success-subtle text-success ms-1">Jadi LUNAS</span>'
                : `<span class="badge bg-warning-subtle text-warning ms-1">Sisa Rp ${formatRupiah(afterDebt)}</span>`;

            html += `
                <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                    <span><strong class="font-monospace text-dark">${inv.invoice_number}</strong> <small class="text-muted">(${inv.date})</small></span>
                    <span>Dibayar <strong>Rp ${formatRupiah(alloc)}</strong> ${statusBadge}</span>
                </li>
            `;
            remainingMoney -= alloc;
        });

        html += '</ul>';

        if (remainingMoney > 0) {
            html += `<div class="text-danger fw-bold mt-2"><i class="bi bi-exclamation-circle me-1"></i>Nominal berlebih Rp ${formatRupiah(remainingMoney)} dari total tagihan!</div>`;
        }

        listEl.innerHTML = html;
    }
</script>
@endpush
@endif
@endsection
