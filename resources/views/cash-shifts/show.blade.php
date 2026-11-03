@extends('layouts.app')

@section('title', 'Hasil Rekonsiliasi Shift Kasir')

@section('content')
@php
    $completedSales = $cashShift->sales->where('status', 'completed');
    $totalGrandTotal = (float) $completedSales->sum('grand_total');
    $totalNetPaid = (float) $completedSales->sum(fn ($s) => min((float) $s->grand_total, (float) $s->paid_amount));
    $totalRemainingDebt = (float) $completedSales->sum(fn ($s) => max(0, (float) $s->grand_total - (float) $s->paid_amount));

    // Group sales by payment method
    $salesByMethod = $completedSales->groupBy('payment_method')->map(function ($group, $method) {
        return [
            'method' => $method,
            'count' => $group->count(),
            'grand_total' => (float) $group->sum('grand_total'),
            'net_paid' => (float) $group->sum(fn ($s) => min((float) $s->grand_total, (float) $s->paid_amount)),
            'remaining_debt' => (float) $group->sum(fn ($s) => max(0, (float) $s->grand_total - (float) $s->paid_amount)),
        ];
    });

    // Aggregate sold product items during this shift
    $soldProducts = collect();
    foreach ($completedSales as $sale) {
        foreach ($sale->details as $detail) {
            $key = $detail->product_id . '_' . $detail->selling_price;
            if ($soldProducts->has($key)) {
                $existing = $soldProducts->get($key);
                $existing['qty'] += (float) $detail->qty;
                $existing['discount'] += (float) $detail->discount_amount;
                $existing['subtotal'] += (float) $detail->subtotal;
                $soldProducts->put($key, $existing);
            } else {
                $soldProducts->put($key, [
                    'product_name' => $detail->product_name,
                    'unit' => $detail->product?->unit?->short_name ?? '',
                    'selling_price' => (float) $detail->selling_price,
                    'qty' => (float) $detail->qty,
                    'discount' => (float) $detail->discount_amount,
                    'subtotal' => (float) $detail->subtotal,
                ]);
            }
        }
    }
    $soldProducts = $soldProducts->sortByDesc('subtotal')->values();

    // Non-sale cash transactions (operational in/out, receivable/payable payments, etc.)
    $nonSaleCashTransactions = $cashShift->transactions->where('category', '!=', 'sale');
@endphp

<div class="container-fluid px-0" style="max-width: 1050px;">
    <!-- Top Action Bar (Web Only) -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1">Hasil Audit & Rekonsiliasi Shift Kasir</h4>
            <p class="text-muted small mb-0">
                Petugas Kasir: <strong>{{ $cashShift->user?->name }}</strong> &bull;
                Status Shift:
                @if($cashShift->status === 'closed')
                    <span class="badge bg-secondary">Closed (Ditutup)</span>
                @else
                    <span class="badge bg-success">Open (Masih Aktif)</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary-custom btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-printer-fill me-1"></i> Cetak Bukti Closing & Rincian Penjualan
            </button>
            <a href="{{ route('cash-shifts.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Kop Header Toko (Khusus Cetak / Print) -->
    <div class="d-none d-print-block mb-3 border-bottom pb-2">
        <table style="width: 100%; border: none; margin-bottom: 5px;">
            <tr style="border: none;">
                <td style="border: none; padding: 0;">
                    <h3 style="margin: 0; font-weight: bold; text-transform: uppercase;">{{ \App\Models\Setting::get('store_name', 'Kasir POS') }}</h3>
                    <div style="font-size: 9pt; color: #555;">{{ \App\Models\Setting::get('store_address', 'Jl. Toko') }} &bull; Telp: {{ \App\Models\Setting::get('store_phone', '-') }}</div>
                </td>
                <td style="border: none; padding: 0; text-align: right; font-size: 8.5pt; color: #555;">
                    Dicetak: {{ now()->format('d/m/Y H:i') }}<br>
                    Oleh: {{ auth()->user()->name }}
                </td>
            </tr>
        </table>
        <div style="text-align: center; margin-top: 6px;">
            <h4 style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">LAPORAN AUDIT & REKONSILIASI SHIFT KASIR</h4>
            <div style="font-size: 9pt; color: #333; margin-top: 3px;">
                Kasir: <strong>{{ $cashShift->user?->name }}</strong> &bull;
                Buka: <strong>{{ $cashShift->opened_at->format('d/m/Y H:i') }}</strong> &bull;
                Tutup: <strong>{{ $cashShift->closed_at ? $cashShift->closed_at->format('d/m/Y H:i') : 'Masih Terbuka' }}</strong>
            </div>
        </div>
    </div>

    <!-- Shift Period Box (Web View) -->
    <div class="card card-custom p-4 mb-4 d-print-none">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">Waktu Buka Shift</small>
                <div class="fw-bold">{{ $cashShift->opened_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">Waktu Tutup Shift</small>
                <div class="fw-bold">{{ $cashShift->closed_at ? $cashShift->closed_at->format('d/m/Y H:i') : 'Masih Terbuka' }}</div>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">Total Transaksi Penjualan</small>
                <div class="fw-bold">{{ $completedSales->count() }} Nota (Rp {{ number_format($totalGrandTotal, 0, ',', '.') }})</div>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">Catatan Shift</small>
                <div class="text-secondary small">{{ $cashShift->notes ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- 1. Financial Reconciliation Card -->
        <div class="col-12 col-lg-7">
            <div class="card card-custom p-4 h-100">
                <h6 class="fw-bold mb-3"><i class="bi bi-calculator text-primary me-2"></i>1. Rekonsiliasi Kas Laci Fisik</h6>

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
                                <td class="text-muted">3. Kas Masuk Operasional / DP / Pelunasan Piutang Tunai (+):</td>
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
                            <tr class="table-light fw-bold">
                                <td>Saldo Kas Sistem yang Seharusnya (Expected):</td>
                                <td class="text-end text-primary">Rp {{ number_format($cashShift->closing_cash_system ?? $summary['expected_cash'], 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-light fw-bold">
                                <td>Saldo Kas Fisik Nyata di Tangan (Actual):</td>
                                <td class="text-end text-dark">Rp {{ number_format($cashShift->closing_cash_actual ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-warning fw-bold fs-6">
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

        <!-- 2. Sales Breakdown by Payment Method -->
        <div class="col-12 col-lg-5">
            <div class="card card-custom p-4 h-100">
                <h6 class="fw-bold mb-3"><i class="bi bi-wallet2 text-success me-2"></i>2. Rekap Penjualan per Metode Bayar</h6>

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Metode</th>
                                <th class="text-center">Nota</th>
                                <th class="text-end">Total Nilai</th>
                                <th class="text-end">Diterima</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($salesByMethod as $m)
                                @php
                                    $methodLabel = match($m['method']) {
                                        'cash' => 'Tunai (Cash)',
                                        'qris' => 'QRIS',
                                        'transfer' => 'Transfer Bank',
                                        'debit' => 'Kartu Debit',
                                        'credit' => 'Kartu Kredit',
                                        'debt' => 'Hutang / Tempo',
                                        default => strtoupper($m['method']),
                                    };
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $methodLabel }}</td>
                                    <td class="text-center">{{ $m['count'] }}</td>
                                    <td class="text-end">Rp {{ number_format($m['grand_total'], 0, ',', '.') }}</td>
                                    <td class="text-end fw-semibold text-success">Rp {{ number_format($m['net_paid'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Belum ada penjualan pada shift ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($salesByMethod->count() > 0)
                            <tfoot class="table-light fw-bold small">
                                <tr>
                                    <td>TOTAL OMSET SHIFT</td>
                                    <td class="text-center">{{ $completedSales->count() }}</td>
                                    <td class="text-end text-primary">Rp {{ number_format($totalGrandTotal, 0, ',', '.') }}</td>
                                    <td class="text-end text-success">Rp {{ number_format($totalNetPaid, 0, ',', '.') }}</td>
                                </tr>
                                @if($totalRemainingDebt > 0)
                                    <tr>
                                        <td colspan="3" class="text-danger">Total Sisa Piutang (Belum Dibayar):</td>
                                        <td class="text-end text-danger">Rp {{ number_format($totalRemainingDebt, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Rincian Nota & Item Penjualan Selama Shift -->
    <div class="card card-custom p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-receipt text-primary me-2"></i>3. Rincian Transaksi Penjualan Selama Shift ({{ $cashShift->sales->count() }} Nota)</h6>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 40px;" class="text-center">#</th>
                        <th style="width: 145px;">No. Nota & Waktu</th>
                        <th style="width: 140px;">Pelanggan</th>
                        <th>Rincian Item Produk Terjual</th>
                        <th style="width: 120px;" class="text-center">Metode & Status</th>
                        <th style="width: 115px;" class="text-end">Total Nota</th>
                        <th style="width: 115px;" class="text-end">Dibayar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashShift->sales as $idx => $sale)
                        @php
                            $netPaid = min((float) $sale->grand_total, (float) $sale->paid_amount);
                            $remDebt = max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
                        @endphp
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td>
                                <a href="{{ route('sales.show', $sale->id) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                                    {{ $sale->invoice_number }}
                                </a>
                                <div class="small text-muted">{{ $sale->transaction_date->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $sale->customer?->name ?? 'Umum' }}</div>
                                @if($sale->customer?->phone)
                                    <div class="small text-muted">{{ $sale->customer->phone }}</div>
                                @endif
                            </td>
                            <td>
                                <ul class="list-unstyled mb-0 small">
                                    @foreach($sale->details as $item)
                                        <li>
                                            <span class="fw-semibold text-dark">{{ $item->product_name }}</span>
                                            <span class="text-muted">
                                                ({{ rtrim(rtrim(number_format((float) $item->qty, 3, ',', '.'), '0'), ',') }} {{ $item->product?->unit?->short_name ?? '' }} &times; Rp {{ number_format($item->selling_price, 0, ',', '.') }}
                                                @if($item->discount_amount > 0)
                                                    - Diskon Rp {{ number_format($item->discount_amount, 0, ',', '.') }}
                                                @endif
                                                = <strong>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</strong>)
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                                @if($sale->discount_amount > 0 || $sale->shipping_cost > 0 || $sale->tax_amount > 0)
                                    <div class="small text-muted mt-1 border-top pt-1">
                                        @if($sale->discount_amount > 0)
                                            <span class="text-danger me-2">Diskon Nota: -Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</span>
                                        @endif
                                        @if($sale->tax_amount > 0)
                                            <span class="me-2">PPN: +Rp {{ number_format($sale->tax_amount, 0, ',', '.') }}</span>
                                        @endif
                                        @if($sale->shipping_cost > 0)
                                            <span class="text-primary">Ongkir: +Rp {{ number_format($sale->shipping_cost, 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary text-uppercase">
                                    {{ $sale->payment_method === 'debt' ? 'TEMPO' : $sale->payment_method }}
                                </span>
                                <div class="mt-1">
                                    @if($sale->payment_status === 'paid')
                                        <span class="badge bg-success-subtle text-success">Lunas</span>
                                    @elseif($sale->payment_status === 'partial')
                                        <span class="badge bg-warning-subtle text-warning">Cicilan</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Hutang</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                Rp {{ number_format($sale->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="text-end">
                                <div class="fw-semibold text-success">Rp {{ number_format($netPaid, 0, ',', '.') }}</div>
                                @if($remDebt > 0)
                                    <div class="small text-danger fw-bold">Hutang: Rp {{ number_format($remDebt, 0, ',', '.') }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Tidak ada transaksi penjualan selama shift ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($cashShift->sales->count() > 0)
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="5" class="text-end">TOTAL PENJUALAN SHIFT:</td>
                            <td class="text-end text-primary">Rp {{ number_format($totalGrandTotal, 0, ',', '.') }}</td>
                            <td class="text-end text-success">
                                Rp {{ number_format($totalNetPaid, 0, ',', '.') }}
                                @if($totalRemainingDebt > 0)
                                    <div class="text-danger">Piutang: Rp {{ number_format($totalRemainingDebt, 0, ',', '.') }}</div>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- 4. Rekapitulasi Produk Terjual Selama Shift -->
    @if($soldProducts->count() > 0)
        <div class="card card-custom p-4 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-box-seam text-primary me-2"></i>4. Rekapitulasi Item Produk Terjual Selama Shift</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 45px;" class="text-center">#</th>
                            <th>Nama Produk</th>
                            <th class="text-end">Harga Jual</th>
                            <th class="text-center">Total Qty Terjual</th>
                            <th class="text-end">Total Diskon Item</th>
                            <th class="text-end">Total Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($soldProducts as $index => $prod)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td class="fw-semibold text-dark">{{ $prod['product_name'] }}</td>
                                <td class="text-end">Rp {{ number_format($prod['selling_price'], 0, ',', '.') }}</td>
                                <td class="text-center fw-bold">
                                    {{ rtrim(rtrim(number_format($prod['qty'], 3, ',', '.'), '0'), ',') }} {{ $prod['unit'] }}
                                </td>
                                <td class="text-end text-muted">
                                    {{ $prod['discount'] > 0 ? '-Rp ' . number_format($prod['discount'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-end fw-bold">Rp {{ number_format($prod['subtotal'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="5" class="text-end">TOTAL SUBTOTAL PRODUK TERJUAL:</td>
                            <td class="text-end text-primary">Rp {{ number_format($soldProducts->sum('subtotal'), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    <!-- 5. Riwayat Kas Masuk / Keluar Operasional -->
    @if($nonSaleCashTransactions->count() > 0)
        <div class="card card-custom p-4 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-journal-text text-warning me-2"></i>5. Riwayat Mutasi Kas Operasional / Lainnya Selama Shift</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 130px;">Waktu</th>
                            <th style="width: 100px;">Tipe Kas</th>
                            <th style="width: 140px;">Kategori</th>
                            <th>Keterangan</th>
                            <th style="width: 140px;" class="text-end">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nonSaleCashTransactions as $tx)
                            <tr>
                                <td>{{ $tx->transaction_date ? $tx->transaction_date->format('d/m/Y H:i') : $tx->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($tx->type === 'in')
                                        <span class="badge bg-success-subtle text-success">Kas Masuk</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Kas Keluar</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-light text-dark border text-uppercase">{{ $tx->category }}</span></td>
                                <td class="small">{{ $tx->description ?? '-' }}</td>
                                <td class="text-end fw-bold {{ $tx->type === 'in' ? 'text-success' : 'text-danger' }}">
                                    {{ $tx->type === 'in' ? '+' : '-' }}Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Signature Block (Print Only) -->
    <div class="d-none d-print-block mt-4 pt-2">
        <table style="width: 100%; border: none; text-align: center;">
            <tr style="border: none;">
                <td style="width: 50%; border: none; padding: 10px;">
                    <div>Diserahkan Oleh (Kasir),</div>
                    <div style="height: 55px;"></div>
                    <div style="font-weight: bold; text-decoration: underline;">{{ $cashShift->user?->name ?? 'Kasir' }}</div>
                </td>
                <td style="width: 50%; border: none; padding: 10px;">
                    <div>Diperiksa & Diterima Oleh (Owner / Admin),</div>
                    <div style="height: 55px;"></div>
                    <div style="font-weight: bold; text-decoration: underline;">( ........................................ )</div>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
