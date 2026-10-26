@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Title & Filter Periode -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Dashboard Analitik</h4>
            <p class="text-muted small mb-0">Ringkasan performa penjualan, perputaran kas, dan persediaan stok</p>
        </div>

        <!-- Filter Periode Form -->
        <form method="GET" action="{{ route('dashboard') }}" class="d-flex flex-wrap align-items-center gap-2">
            <div class="btn-group shadow-sm" role="group" aria-label="Filter Periode">
                <a href="{{ route('dashboard', ['period' => 'today']) }}" class="btn btn-sm {{ $period === 'today' ? 'btn-primary' : 'btn-light' }}">Hari Ini</a>
                <a href="{{ route('dashboard', ['period' => 'yesterday']) }}" class="btn btn-sm {{ $period === 'yesterday' ? 'btn-primary' : 'btn-light' }}">Kemarin</a>
                <a href="{{ route('dashboard', ['period' => 'this_week']) }}" class="btn btn-sm {{ $period === 'this_week' ? 'btn-primary' : 'btn-light' }}">Minggu Ini</a>
                <a href="{{ route('dashboard', ['period' => 'this_month']) }}" class="btn btn-sm {{ $period === 'this_month' ? 'btn-primary' : 'btn-light' }}">Bulan Ini</a>
                <a href="{{ route('dashboard', ['period' => 'this_year']) }}" class="btn btn-sm {{ $period === 'this_year' ? 'btn-primary' : 'btn-light' }}">Tahun Ini</a>
            </div>

            <!-- Custom Date Trigger -->
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle shadow-sm rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#customDateCollapse" aria-expanded="false">
                <i class="bi bi-calendar3"></i> Kustom Tanggal
            </button>
        </form>
    </div>

    <!-- Collapsible Custom Date Form -->
    <div class="collapse mb-4 {{ $period === 'custom' ? 'show' : '' }}" id="customDateCollapse">
        <div class="card card-custom p-3 bg-white">
            <form method="GET" action="{{ route('dashboard') }}" class="row g-2 align-items-end">
                <input type="hidden" name="period" value="custom">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date', $startDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Tanggal Selesai</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date', $endDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-sm btn-primary-custom w-100">
                        <i class="bi bi-funnel-fill me-1"></i> Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Filter Info Banner -->
    <div class="alert alert-light border border-secondary-subtle py-2 px-3 rounded-3 mb-4 d-flex align-items-center justify-content-between small">
        <span class="text-secondary">
            <i class="bi bi-clock-history me-1 text-primary"></i> Rentang Periode Aktif: 
            <strong>{{ $startDate->translatedFormat('d F Y') }}</strong> s/d <strong>{{ $endDate->translatedFormat('d F Y') }}</strong>
        </span>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
            Mode: {{ ucfirst(str_replace('_', ' ', $period)) }}
        </span>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Penjualan -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-stat">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Total Penjualan</span>
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-1 text-dark">Rp {{ number_format($totalSalesAmount, 0, ',', '.') }}</h4>
                <div class="d-flex align-items-center text-muted small">
                    <span class="badge bg-success-subtle text-success me-1">{{ $totalSalesCount }} Transaksi</span>
                    <span>selama periode</span>
                </div>
            </div>
        </div>

        <!-- 2. Keuntungan Bersih -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-stat">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Estimasi Keuntungan</span>
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-1 text-primary">Rp {{ number_format($profitLoss['net_profit'], 0, ',', '.') }}</h4>
                <div class="d-flex align-items-center text-muted small">
                    <span class="text-success me-1">Laba Kotor: Rp {{ number_format($profitLoss['gross_profit'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Total Pembelian (PO) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-stat">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Total Pembelian</span>
                    <div class="stat-icon bg-warning-subtle text-warning">
                        <i class="bi bi-cart-dash-fill"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-1 text-dark">Rp {{ number_format($totalPurchasesAmount, 0, ',', '.') }}</h4>
                <div class="d-flex align-items-center text-muted small">
                    <span class="badge bg-warning-subtle text-warning me-1">{{ $totalPurchasesCount }} PO Supplier</span>
                    <span>barang masuk</span>
                </div>
            </div>
        </div>

        <!-- 4. Produk & Stok Menipis -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-stat">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Persediaan Produk</span>
                    <div class="stat-icon bg-danger-subtle text-danger">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-1 text-dark">{{ $totalProducts }} <span class="fs-6 fw-normal text-muted">Item</span></h4>
                <div class="d-flex align-items-center text-muted small">
                    @if($lowStockCount > 0)
                        <span class="badge bg-danger text-white me-1">{{ $lowStockCount }} Stok Menipis</span>
                        <a href="{{ route('stocks.index', ['filter' => 'low']) }}" class="text-danger small">Cek detail &rarr;</a>
                    @else
                        <span class="badge bg-success-subtle text-success">Stok Aman</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Secondary Quick Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card-custom p-3 bg-white d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info-subtle text-info p-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-people fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Pelanggan</div>
                    <div class="fw-bold fs-5">{{ $totalCustomers }} Member</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-custom p-3 bg-white d-flex align-items-center gap-3">
                <div class="rounded-circle bg-secondary-subtle text-secondary p-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-truck fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Supplier</div>
                    <div class="fw-bold fs-5">{{ $totalSuppliers }} Mitra</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-custom p-3 bg-white d-flex align-items-center gap-3">
                <div class="rounded-circle bg-purple-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: #f3e8ff;">
                    <i class="bi bi-cash-stack fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small">Biaya Operasional</div>
                    <div class="fw-bold fs-5">Rp {{ number_format($profitLoss['expenses'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-custom p-3 bg-white d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-check2-circle fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small">HPP Terjual (COGS)</div>
                    <div class="fw-bold fs-5">Rp {{ number_format($profitLoss['total_cogs'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Grafik Penjualan 7 Hari Terakhir & Produk Terlaris -->
    <div class="row g-4 mb-4">
        <!-- Grafik Penjualan vs Pembelian -->
        <div class="col-12 col-xl-8">
            <div class="card card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="fw-bold mb-1">Tren Penjualan 7 Hari Terakhir</h6>
                        <small class="text-muted">Grafik fluktuasi omset penjualan tunai & non-tunai</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">7 Hari Terakhir</span>
                </div>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="salesTrendsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top 5 Produk Terlaris -->
        <div class="col-12 col-xl-4">
            <div class="card card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Produk Terlaris</h6>
                    <small class="text-muted">Periode ini</small>
                </div>
                @if($bestSellers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bestSellers as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-truncate" style="max-width: 150px;">{{ $item->product_name }}</div>
                                            <small class="text-success" style="font-size: 0.72rem;">Laba: Rp {{ number_format($item->total_profit, 0, ',', '.') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill">{{ $item->total_qty }}</span>
                                        </td>
                                        <td class="text-end fw-semibold small">
                                            Rp {{ number_format($item->total_sales, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-bag-x fs-1 d-block mb-2 text-secondary"></i>
                        Belum ada data penjualan pada periode ini
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Row 4: Transaksi Terbaru & Alert Stok Menipis -->
    <div class="row g-4">
        <!-- Transaksi Terbaru -->
        <div class="col-12 col-lg-8">
            <div class="card card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Transaksi Penjualan Terbaru</h6>
                    <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">Lihat Semua</a>
                </div>
                @if($recentSales->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>No. Nota</th>
                                    <th>Kasir</th>
                                    <th>Pelanggan</th>
                                    <th>Metode</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentSales as $sale)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-primary">{{ $sale->invoice_number }}</div>
                                            <small class="text-muted">{{ $sale->transaction_date->format('d/m/Y H:i') }}</small>
                                        </td>
                                        <td>{{ $sale->user?->name ?? 'Kasir' }}</td>
                                        <td>{{ $sale->customer?->name ?? 'Umum' }}</td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $sale->payment_method }}</span>
                                        </td>
                                        <td class="text-end fw-bold">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('sales.receipt', $sale->id) }}" target="_blank" class="btn btn-sm btn-light border" title="Cetak Struk">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <p class="mb-0">Belum ada transaksi penjualan yang tercatat.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Alert Stok Menipis -->
        <div class="col-12 col-lg-4">
            <div class="card card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Stok Menipis</h6>
                    <a href="{{ route('stocks.index', ['filter' => 'low']) }}" class="small text-danger">Kelola Stok</a>
                </div>
                @if($lowStockProducts->count() > 0)
                    <ul class="list-group list-group-flush">
                        @foreach($lowStockProducts as $prod)
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold text-dark">{{ $prod->name }}</div>
                                    <small class="text-muted">Min: {{ $prod->min_stock }} {{ $prod->unit?->short_name ?? 'pcs' }}</small>
                                </div>
                                <span class="badge bg-danger rounded-pill px-3 py-2">
                                    Sisa {{ $prod->stock }} {{ $prod->unit?->short_name ?? 'pcs' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center py-4 text-success">
                        <i class="bi bi-check-circle fs-2 d-block mb-1"></i>
                        Semua persediaan produk dalam batas aman!
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesTrendsChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartDates) !!},
            datasets: [
                {
                    label: 'Penjualan (Rp)',
                    data: {!! json_encode($chartSales) !!},
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 3,
                    pointBackgroundColor: '#4f46e5',
                    pointRadius: 4
                },
                {
                    label: 'Pembelian (Rp)',
                    data: {!! json_encode($chartPurchases) !!},
                    borderColor: '#f59e0b',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    tension: 0.35,
                    borderWidth: 2,
                    pointBackgroundColor: '#f59e0b',
                    pointRadius: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        font: { family: 'Inter', size: 12 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(value);
                        },
                        font: { family: 'Inter', size: 11 }
                    },
                    grid: { color: '#f1f5f9' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 11 } }
                }
            }
        }
    });
</script>
@endpush
