@extends('layouts.app')

@section('title', 'Laporan Penjualan per Customer')

@section('content')
<div class="container-fluid px-0">
    @include('reports.partials.nav')

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1">Laporan Penjualan per Customer</h4>
            <p class="text-muted small mb-0">Statistik loyalitas pelanggan, frekuensi transaksi, dan kontribusi omset belanja member vs umum</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.sales-by-customer.pdf', request()->all()) }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1">
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
            <h4 style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">LAPORAN PENJUALAN PER CUSTOMER</h4>
            <div style="font-size: 9pt; color: #555; margin-top: 3px;">
                Periode: <strong>{{ $start->format('d/m/Y') }}</strong> s/d <strong>{{ $end->format('d/m/Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter Card (Hanya di Layar Web) -->
    <div class="card card-custom p-3 mb-4 d-print-none">
        <form method="GET" action="{{ route('reports.sales-by-customer') }}" class="row g-2 align-items-end" id="filterCustomerForm">
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
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">Customer (Searchable)</label>
                <div class="dropdown custom-searchable-dropdown" id="customerSearchableDropdown">
                    <input type="hidden" name="customer_id" id="filterCustomerId" value="{{ $customerId }}">
                    <input type="hidden" name="search" id="filterSearchInput" value="{{ $search }}">
                    
                    <button class="btn btn-white bg-white border form-control form-control-sm text-start d-flex justify-content-between align-items-center py-1 px-2" 
                            type="button" 
                            id="customerDropdownToggle" 
                            data-bs-toggle="dropdown" 
                            data-bs-auto-close="outside"
                            aria-expanded="false">
                        <span class="text-truncate d-flex align-items-center gap-1 me-1">
                            <i class="bi bi-person-circle text-primary"></i>
                            <span id="customerDropdownSelectedLabel" class="fw-medium text-dark text-truncate">
                                @if($customerId === 'umum' || $customerId === 'non-member')
                                    Pelanggan Umum (Walk-in)
                                @elseif($customerId && ($cur = $allCustomers->firstWhere('id', $customerId)))
                                    {{ $cur->name }} ({{ $cur->code }})
                                @elseif($search)
                                    Cari: "{{ $search }}"
                                @else
                                    Semua Customer
                                @endif
                            </span>
                        </span>
                        <span class="d-flex align-items-center gap-1 flex-shrink-0">
                            @if($customerId || $search)
                                <span role="button" class="text-muted p-0 border-0 bg-transparent" title="Reset pilihan customer" onclick="event.stopPropagation(); resetCustomerSelection();">
                                    <i class="bi bi-x-circle-fill text-secondary"></i>
                                </span>
                            @endif
                            <i class="bi bi-chevron-down text-muted" style="font-size: 0.7rem;"></i>
                        </span>
                    </button>

                    <div class="dropdown-menu p-2 shadow-lg border-0 rounded-3 mt-1" style="width: 320px; max-width: 92vw;">
                        <!-- Live Search Input -->
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" 
                                   id="customerSearchLiveInput" 
                                   class="form-control bg-light border-start-0" 
                                   placeholder="Ketik nama, HP, atau kode..." 
                                   autocomplete="off">
                        </div>

                        <!-- List items -->
                        <div class="overflow-y-auto" style="max-height: 240px;" id="customerListItems">
                            <a href="javascript:void(0)" 
                               class="dropdown-item rounded-2 py-1 px-2 d-flex align-items-center justify-content-between customer-item-option {{ !$customerId && !$search ? 'active bg-primary-subtle text-primary fw-semibold' : '' }}" 
                               data-id="" 
                               data-search-text=""
                               data-label="Semua Customer"
                               data-keywords="semua all customer pelanggan">
                                <div><i class="bi bi-people me-1"></i> Semua Customer</div>
                                @if(!$customerId && !$search) <i class="bi bi-check2 text-primary"></i> @endif
                            </a>

                            <a href="javascript:void(0)" 
                               class="dropdown-item rounded-2 py-1 px-2 d-flex align-items-center justify-content-between customer-item-option {{ $customerId === 'umum' || $customerId === 'non-member' ? 'active bg-primary-subtle text-primary fw-semibold' : '' }}" 
                               data-id="umum" 
                               data-search-text=""
                               data-label="Pelanggan Umum (Walk-in)"
                               data-keywords="umum non member walk-in walkin non-member">
                                <div>
                                    <i class="bi bi-person me-1"></i> Pelanggan Umum
                                    <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.68rem;">Non-Member</span>
                                </div>
                                @if($customerId === 'umum' || $customerId === 'non-member') <i class="bi bi-check2 text-primary"></i> @endif
                            </a>

                            <hr class="dropdown-divider my-1">

                            @foreach($allCustomers as $c)
                                <a href="javascript:void(0)" 
                                   class="dropdown-item rounded-2 py-1 px-2 d-flex align-items-center justify-content-between customer-item-option {{ (string)$customerId === (string)$c->id ? 'active bg-primary-subtle text-primary fw-semibold' : '' }}" 
                                   data-id="{{ $c->id }}" 
                                   data-search-text=""
                                   data-label="{{ $c->name }} ({{ $c->code }})"
                                   data-keywords="{{ strtolower($c->name . ' ' . $c->code . ' ' . ($c->phone ?? '') . ' ' . $c->member_level) }}">
                                    <div class="overflow-hidden me-2">
                                        <div class="fw-semibold text-truncate" style="font-size: 0.825rem;">{{ $c->name }}</div>
                                        <div class="text-muted d-flex align-items-center gap-1 font-monospace" style="font-size: 0.72rem;">
                                            <span>{{ $c->code }}</span>
                                            @if($c->phone)
                                                <span>&bull; {{ $c->phone }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="badge {{ in_array($c->member_level, ['gold', 'platinum']) ? 'bg-warning-subtle text-warning' : 'bg-primary-subtle text-primary' }}" style="font-size: 0.65rem; text-transform: capitalize;">
                                        {{ $c->member_level }}
                                    </span>
                                </a>
                            @endforeach

                            <div class="text-center py-3 text-muted small d-none" id="customerEmptyNotice">
                                <i class="bi bi-search d-block mb-1"></i>
                                Customer tidak ditemukan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Urutkan</label>
                <select name="sort_by" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="total_desc" {{ request('sort_by') == 'total_desc' ? 'selected' : '' }}>Total Belanja Terbanyak</option>
                    <option value="tx_desc" {{ request('sort_by') == 'tx_desc' ? 'selected' : '' }}>Transaksi Terbanyak</option>
                </select>
            </div>
            <div class="col-6 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary-custom btn-sm w-100" title="Terapkan Filter"><i class="bi bi-filter"></i></button>
                <a href="{{ route('reports.sales-by-customer') }}" class="btn btn-light border btn-sm" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    @if($customerId || $search)
        <div class="mb-3 d-flex align-items-center gap-2 d-print-none">
            <span class="small text-muted">Filter Aktif:</span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1 py-1 px-2">
                <i class="bi bi-person-check-fill"></i>
                @if($customerId === 'umum' || $customerId === 'non-member')
                    Pelanggan Umum (Walk-in)
                @elseif($customerId && ($cur = $allCustomers->firstWhere('id', $customerId)))
                    {{ $cur->name }} ({{ $cur->code }})
                @elseif($search)
                    Pencarian: "{{ $search }}"
                @endif
                <a href="{{ route('reports.sales-by-customer', array_merge(request()->except(['customer_id', 'search']))) }}" class="text-primary ms-1 text-decoration-none" title="Hapus filter customer">
                    <i class="bi bi-x-circle-fill"></i>
                </a>
            </span>
        </div>
    @endif

    <!-- Summary KPI Cards (Hanya di Layar Web) -->
    <div class="row g-3 mb-4 d-print-none">
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Total Pelanggan Transaksi</small>
                <h4 class="fw-bold text-dark mb-0">{{ $summary['total_customers_count'] }} Pelanggan</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Total {{ $summary['total_transactions'] }} transaksi nota</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white border-primary">
                <small class="text-primary fw-semibold d-block">Omset Member Terdaftar</small>
                <h4 class="fw-bold text-primary mb-0">Rp {{ number_format($summary['member_spent'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Kontribusi: {{ number_format($summary['member_contribution_percentage'], 1) }}% ({{ $summary['member_transactions'] }} nota)</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Omset Pelanggan Umum</small>
                <h4 class="fw-bold text-dark mb-0">Rp {{ number_format($summary['general_spent'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Dari {{ $summary['general_transactions'] }} nota non-member</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white">
                <small class="text-muted d-block">Total Belanja Pelanggan</small>
                <h4 class="fw-bold text-success mb-0">Rp {{ number_format($summary['total_spent'], 0, ',', '.') }}</h4>
                <small class="text-muted" style="font-size: 0.75rem;">Gabungan member dan umum</small>
            </div>
        </div>
    </div>

    <!-- Ringkasan Format Tabel Cetak (Khusus Print) -->
    <div class="d-none d-print-block mb-2">
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Total Pelanggan Belanja</th>
                    <th style="width: 25%;">Omset Member Terdaftar</th>
                    <th style="width: 25%;">Omset Pelanggan Umum</th>
                    <th style="width: 25%;">Total Belanja Keseluruhan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold">{{ $summary['total_customers_count'] }} Pelanggan</td>
                    <td class="fw-bold text-primary">Rp {{ number_format($summary['member_spent'], 0, ',', '.') }} ({{ number_format($summary['member_contribution_percentage'], 1) }}%)</td>
                    <td class="fw-bold">Rp {{ number_format($summary['general_spent'], 0, ',', '.') }}</td>
                    <td class="fw-bold text-success">Rp {{ number_format($summary['total_spent'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Customers Sales Table -->
    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 5%;" class="text-center">#</th>
                        <th style="width: 25%;">Nama Pelanggan</th>
                        <th style="width: 15%;">Kontak / HP</th>
                        <th style="width: 12%;" class="text-center">Tipe Member</th>
                        <th style="width: 13%;" class="text-center">Frekuensi Belanja</th>
                        <th style="width: 15%;" class="text-end">Total Belanja (Omset)</th>
                        <th style="width: 15%;" class="text-end">Rata-rata / Belanja</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $idx => $c)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $c->customer_name }}</div>
                                @if($c->is_member)
                                    <small class="text-muted font-monospace">ID: {{ $c->customer_code }}</small>
                                @endif
                            </td>
                            <td>{{ $c->customer_phone }}</td>
                            <td class="text-center">
                                @if($c->is_member)
                                    <span class="badge bg-primary-subtle text-primary fw-bold">Member</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Umum</span>
                                @endif
                            </td>
                            <td class="text-center fw-semibold">{{ $c->total_transactions }} Kali</td>
                            <td class="text-end fw-bold text-dark">Rp {{ number_format($c->total_spent, 0, ',', '.') }}</td>
                            <td class="text-end text-muted">Rp {{ number_format($c->avg_per_transaction, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Belum ada riwayat transaksi pelanggan pada rentang periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end">TOTAL KESELURUHAN OMSET PELANGGAN:</td>
                        <td class="text-center">{{ number_format($summary['total_transactions'], 0, ',', '.') }} Transaksi</td>
                        <td class="text-end text-primary">Rp {{ number_format($summary['total_spent'], 0, ',', '.') }}</td>
                        <td class="text-end">-</td>
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
                    <p style="margin-bottom: 50px;">Disiapkan Oleh,</p>
                    <p class="fw-bold mb-0" style="text-decoration: underline;">{{ auth()->user()->name }}</p>
                    <small style="color: #666;">Bagian Kasir / Pelayanan</small>
                </td>
                <td style="width: 50%; border: none; text-align: center; padding-top: 20px;">
                    <p style="margin-bottom: 50px;">Mengetahui & Menyetujui,</p>
                    <p class="fw-bold mb-0" style="text-decoration: underline;">( .................................................. )</p>
                    <small style="color: #666;">Owner / Manajer Toko</small>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('customerSearchLiveInput');
    const optionsList = document.getElementById('customerListItems');
    const options = optionsList ? optionsList.querySelectorAll('.customer-item-option') : [];
    const emptyNotice = document.getElementById('customerEmptyNotice');
    const hiddenCustomerId = document.getElementById('filterCustomerId');
    const hiddenSearchInput = document.getElementById('filterSearchInput');
    const labelSpan = document.getElementById('customerDropdownSelectedLabel');
    const filterForm = document.getElementById('filterCustomerForm');
    const dropdownEl = document.getElementById('customerSearchableDropdown');

    // Auto-focus search input when dropdown opens
    if (dropdownEl && searchInput) {
        dropdownEl.addEventListener('shown.bs.dropdown', function () {
            searchInput.focus();
            searchInput.value = '';
            filterCustomerOptions('');
        });
    }

    // Live search filter inside dropdown
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            filterCustomerOptions(this.value.toLowerCase().trim());
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const visibleOptions = Array.from(options).filter(opt => !opt.classList.contains('d-none'));
                if (visibleOptions.length > 0) {
                    visibleOptions[0].click();
                } else if (searchInput.value.trim() !== '') {
                    // Search by custom keyword
                    hiddenCustomerId.value = '';
                    hiddenSearchInput.value = searchInput.value.trim();
                    filterForm.submit();
                }
            }
        });
    }

    function filterCustomerOptions(keyword) {
        let visibleCount = 0;
        options.forEach(function (opt) {
            const match = !keyword || (opt.getAttribute('data-keywords') || '').toLowerCase().includes(keyword);
            if (match) {
                opt.classList.remove('d-none');
                visibleCount++;
            } else {
                opt.classList.add('d-none');
            }
        });
        if (emptyNotice) {
            if (visibleCount === 0) {
                emptyNotice.classList.remove('d-none');
            } else {
                emptyNotice.classList.add('d-none');
            }
        }
    }

    // Option click handler
    options.forEach(function (opt) {
        opt.addEventListener('click', function () {
            const id = this.getAttribute('data-id') || '';
            const label = this.getAttribute('data-label') || 'Semua Customer';
            hiddenCustomerId.value = id;
            hiddenSearchInput.value = '';
            labelSpan.textContent = label;

            if (filterForm) {
                filterForm.submit();
            }
        });
    });

    window.resetCustomerSelection = function () {
        if (hiddenCustomerId) hiddenCustomerId.value = '';
        if (hiddenSearchInput) hiddenSearchInput.value = '';
        if (filterForm) filterForm.submit();
    };
});
</script>
@endpush
