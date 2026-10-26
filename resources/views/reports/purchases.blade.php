@extends('layouts.app')

@section('title', 'Laporan Pembelian')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1">Laporan Pembelian Barang (PO)</h4>
            <p class="text-muted small mb-0">Rekapitulasi pembelanjaan barang dagang ke distributor dan riwayat pembayaran hutang</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.purchases.pdf', request()->all()) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
                <i class="bi bi-file-earmark-pdf"></i> Ekspor PDF
            </a>
            <button onclick="window.print()" class="btn btn-light border btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
                <i class="bi bi-printer me-1"></i> Print Laporan
            </button>
        </div>
    </div>

    <!-- Kop Header Toko (Khusus Tampilan Cetak / Print) -->
    <div class="d-none d-print-block mb-3 border-bottom pb-2">
        <table style="width: 100%; border: none; margin-bottom: 5px;">
            <tr style="border: none;">
                <td style="border: none; padding: 0;">
                    <h3 style="margin: 0; font-weight: bold; text-transform: uppercase;">{{ \App\Models\Setting::get('store_name', 'Kasir POS') }}</h3>
                    <div style="font-size: 9pt; color: #555;">{{ \App\Models\Setting::get('store_address', 'Jl. Toko') }} &bull; Telp: {{ \App\Models\Setting::get('store_phone', '-') }}</div>
                </td>
                <td style="border: none; padding: 0; text-align: right; font-size: 8.5pt; color: #555;">
                    Dicetak: {{ now()->format('d/m/Y H:i') }}<br>
                    Petugas: {{ auth()->user()->name }}
                </td>
            </tr>
        </table>
        <div style="text-align: center; margin-top: 8px;">
            <h4 style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">LAPORAN PEMBELIAN BARANG & HUTANG SUPPLIER</h4>
            <div style="font-size: 9pt; color: #555; margin-top: 3px;">
                Periode: <strong>{{ $start->format('d/m/Y') }}</strong> s/d <strong>{{ $end->format('d/m/Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter Card (Hanya di Layar Web) -->
    <div class="card card-custom p-3 mb-4 d-print-none">
        <form method="GET" action="{{ route('reports.purchases') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Preset Periode</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hari Ini</option>
                    <option value="yesterday" {{ $period === 'yesterday' ? 'selected' : '' }}>Kemarin</option>
                    <option value="this_week" {{ $period === 'this_week' ? 'selected' : '' }}>Minggu Ini</option>
                    <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                    <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>Tahun Ini</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Kustom</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Mulai</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Sampai</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted">Pilih Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100">Filter</button>
                <a href="{{ route('reports.purchases') }}" class="btn btn-light border btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Purchases Summary Cards (Hanya di Layar Web) -->
    <div class="row g-3 mb-4 d-print-none">
        <div class="col-6 col-md-4">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Total Dokumen PO</small>
                <h4 class="fw-bold text-dark mb-0">{{ $purchases->count() }} Faktur</h4>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Total Nilai Pembelian</small>
                <h4 class="fw-bold text-primary mb-0">Rp {{ number_format($totalPurchases, 0, ',', '.') }}</h4>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Sisa Hutang ke Supplier</small>
                <h4 class="fw-bold text-danger mb-0">Rp {{ number_format(max(0, $totalPurchases - $totalPaid), 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>

    <!-- Ringkasan Pembelian Format Tabel Cetak (Khusus Print) -->
    <div class="d-none d-print-block mb-2">
        <table>
            <thead>
                <tr>
                    <th style="width: 33.33%;">Total Dokumen PO</th>
                    <th style="width: 33.33%;">Total Belanja Pembelian</th>
                    <th style="width: 33.33%;">Sisa Hutang Belum Lunas</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold">{{ $purchases->count() }} Faktur</td>
                    <td class="fw-bold">Rp {{ number_format($totalPurchases, 0, ',', '.') }}</td>
                    <td class="fw-bold text-danger">Rp {{ number_format(max(0, $totalPurchases - $totalPaid), 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Purchases Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 5%;" class="text-center">#</th>
                        <th style="width: 18%;">No. PO</th>
                        <th style="width: 13%;">Tanggal</th>
                        <th style="width: 20%;">Supplier</th>
                        <th style="width: 12%;" class="text-center">Metode Bayar</th>
                        <th style="width: 16%;" class="text-end">Total Pembelian</th>
                        <th style="width: 16%;" class="text-end">Telah Dibayar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $index => $po)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <a href="{{ route('purchases.show', $po->id) }}" class="fw-bold text-primary font-monospace text-decoration-none">
                                    {{ $po->purchase_number }}
                                </a>
                            </td>
                            <td>{{ $po->purchase_date->format('d/m/Y') }}</td>
                            <td>{{ $po->supplier?->name ?? '-' }}</td>
                            <td class="text-center"><span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $po->payment_method }}</span></td>
                            <td class="text-end fw-bold">Rp {{ number_format($po->grand_total, 0, ',', '.') }}</td>
                            <td class="text-end text-success">Rp {{ number_format($po->paid_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Tidak ada data pembelian pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="5" class="text-end">TOTAL KESELURUHAN ({{ $purchases->count() }} Faktur):</td>
                        <td class="text-end text-primary">Rp {{ number_format($totalPurchases, 0, ',', '.') }}</td>
                        <td class="text-end text-success">Rp {{ number_format($totalPaid, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-end">SISA HUTANG KE DISTRIBUTOR:</td>
                        <td colspan="2" class="text-end text-danger">Rp {{ number_format(max(0, $totalPurchases - $totalPaid), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Signature Block (Khusus Print) -->
    <div class="d-none d-print-block mt-4">
        <table style="width: 100%; border: none;">
            <tr style="border: none;">
                <td style="width: 50%; border: none; text-align: center; padding-top: 20px;">
                    <p style="margin-bottom: 50px;">Petugas Pembelian / Logistik,</p>
                    <p class="fw-bold mb-0" style="text-decoration: underline;">{{ auth()->user()->name }}</p>
                    <small style="color: #666;">Petugas Input</small>
                </td>
                <td style="width: 50%; border: none; text-align: center; padding-top: 20px;">
                    <p style="margin-bottom: 50px;">Mengetahui & Menyetujui,</p>
                    <p class="fw-bold mb-0" style="text-decoration: underline;">( .................................................. )</p>
                    <small style="color: #666;">Manajer Keuangan / Pemilik Toko</small>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
