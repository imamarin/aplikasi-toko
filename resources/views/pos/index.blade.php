@extends('layouts.app')

@section('title', 'Terminal Kasir (POS)')
@section('body-class', 'sidebar-collapsed pos-mode')
@section('main-class', 'p-2 p-md-3 flex-grow-1 d-flex flex-column min-vh-0')

@section('navbar-actions')
    <button type="button" class="btn btn-outline-warning btn-sm rounded-pill d-flex align-items-center gap-2 fw-semibold px-3" onclick="openHeldTransactionsModal()" id="btnNavHoldList" title="Buka Daftar Transaksi Ditahan (F8)">
        <i class="bi bi-clock-history"></i>
        <span class="d-none d-md-inline">Hold Transaksi</span>
        <span class="badge bg-warning text-dark rounded-pill fw-bold" id="navHoldCountBadge">0</span>
    </button>
@endsection

@push('styles')
<style>
    /* Full Viewport Fitting for POS (Zero Outer Window Scroll) */
    body.pos-mode {
        overflow: hidden !important;
        height: 100vh !important;
    }

    body.pos-mode #page-content-wrapper {
        height: 100vh !important;
        overflow: hidden !important;
    }

    .pos-container {
        height: 100%;
        max-height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .pos-main-row {
        flex: 1 1 0;
        min-height: 0;
        height: 100%;
        overflow: hidden;
    }

    /* Left Column: Product Catalog */
    .catalog-col {
        height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    #productGridContainer {
        flex: 1 1 0;
        min-height: 0;
        overflow-y: auto;
    }

    /* Right Column: Cart Pane */
    .cart-col {
        height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    .cart-pane {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        height: 100%;
        max-height: 100%;
        min-height: 0;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }

    .cart-header-fixed {
        flex-shrink: 0;
    }

    /* Scrollable items only */
    .cart-items-scroll {
        flex: 1 1 0;
        min-height: 0;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background-color: #f8fafc;
    }

    .cart-footer-fixed {
        flex-shrink: 0;
    }

    .product-card {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
    }

    .product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(79, 70, 229, 0.12);
        border-color: #4f46e5;
    }

    .product-card:active {
        transform: scale(0.98);
    }

    .category-pill {
        cursor: pointer;
        border-radius: 20px;
        padding: 0.35rem 0.85rem;
        font-size: 0.8rem;
        font-weight: 500;
        white-space: nowrap;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        transition: all 0.2s;
    }

    .category-pill.active, .category-pill:hover {
        background: #4f46e5;
        color: #ffffff;
        border-color: #4f46e5;
    }

    .qty-btn {
        width: 26px;
        height: 26px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }

    .grand-total-box {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
        color: #ffffff;
        border-radius: 12px;
        padding: 0.9rem 1.15rem;
    }

    .quick-cash-btn {
        border-radius: 10px;
        font-weight: 600;
        padding: 0.5rem;
        font-size: 0.88rem;
    }

    /* Customer Selection Modal & Card Styles */
    .customer-card-item {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid #e2e8f0;
        background: #ffffff;
    }
    .customer-card-item:hover {
        background-color: #f8fafc;
        border-color: #6366f1;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.08);
    }
    .customer-card-item.active-selected {
        border-color: #4f46e5 !important;
        background-color: #eef2ff !important;
    }
    .customer-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 pos-container">

    <!-- Active Shift Warning if Not Opened -->
    @if(!$currentShift)
        <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-2 d-flex align-items-center justify-content-between p-2 p-md-3 flex-shrink-0">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 text-warning me-2"></i>
                <div>
                    <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">Sesi Shift Kasir Belum Dibuka</h6>
                    <small class="text-muted">Untuk mencatat arus kas masuk dan closing saldo kasir secara akurat, silakan buka shift terlebih dahulu.</small>
                </div>
            </div>
            <a href="{{ route('cash-shifts.index') }}" class="btn btn-warning btn-sm fw-bold text-dark rounded-pill px-3">
                <i class="bi bi-wallet2 me-1"></i> Buka Shift Sekarang
            </a>
        </div>
    @endif

    <div class="row g-2 g-xl-3 flex-grow-1 min-vh-0 pos-main-row">
        <!-- LEFT COLUMN: Product Catalog & Barcode Scanner -->
        <div class="col-12 col-lg-7 col-xl-8 d-flex flex-column h-100 catalog-col">
            <!-- Search & Barcode Bar -->
            <div class="card card-custom p-2 p-md-3 mb-2 shadow-sm flex-shrink-0">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-primary fs-5">
                                <i class="bi bi-upc-scan"></i>
                            </span>
                            <input type="text" id="barcodeSearchInput" class="form-control form-control-lg border-start-0" placeholder="Scan Barcode atau Ketik Nama Produk (F2)..." autofocus autocomplete="off">
                            <button class="btn btn-light border" type="button" onclick="clearSearch()" title="Bersihkan">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <select id="categoryFilter" class="form-select form-select-lg" onchange="filterProductsByCategory(this.value)">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Category Pills Horizon -->
            <div class="d-flex gap-2 overflow-x-auto pb-1 mb-1 flex-shrink-0" style="scrollbar-width: thin;">
                <button type="button" class="category-pill active" onclick="setCategoryPill('', this)">Semua (All)</button>
                @foreach($categories as $cat)
                    <button type="button" class="category-pill" onclick="setCategoryPill('{{ $cat->id }}', this)">{{ $cat->name }}</button>
                @endforeach
            </div>

            <!-- Product Cards Grid -->
            <div class="flex-grow-1 overflow-y-auto pe-1" id="productGridContainer">
                <div class="row g-2 g-xl-3" id="productsGrid">
                    @forelse($products as $prod)
                        <div class="col-6 col-sm-4 col-md-4 col-xl-3">
                            <div class="product-card p-3 h-100 d-flex flex-column" onclick="addToCart({{ json_encode($prod) }})">
                                <div class="text-center mb-2">
                                    @if($prod->image)
                                        <img src="{{ asset('storage/' . $prod->image) }}" alt="{{ $prod->name }}" class="rounded-3 shadow-sm" style="width: 100%; height: 95px; object-fit: cover;">
                                    @else
                                        <div class="rounded-3 bg-light text-muted d-flex align-items-center justify-content-center mx-auto" style="width: 100%; height: 95px;">
                                            <i class="bi bi-box-seam fs-2"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="fw-bold text-dark small text-truncate mb-1" title="{{ $prod->name }}">{{ $prod->name }}</div>
                                <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                    <div class="fw-bold text-primary small">Rp {{ number_format($prod->selling_price, 0, ',', '.') }}</div>
                                    <span class="badge {{ $prod->stock <= 0 ? 'bg-danger' : ($prod->stock <= $prod->min_stock ? 'bg-warning text-dark' : 'bg-light text-secondary border') }}" style="font-size: 0.72rem;">
                                        {{ $prod->stock }} {{ $prod->unit?->short_name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted">
                            <i class="bi bi-box2 fs-1 d-block mb-2"></i>
                            Tidak ada produk aktif.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Interactive Cart & Checkout (Fitted Height, Only Items Scroll) -->
        <div class="col-12 col-lg-5 col-xl-4 h-100 cart-col">
            <div class="cart-pane p-2 p-md-3">
                <!-- Customer Selection Bar: Fixed Header -->
                <div class="d-flex gap-2 mb-2 cart-header-fixed align-items-center">
                    <div class="flex-grow-1 position-relative">
                        <button type="button" 
                                class="btn btn-light border btn-sm w-100 text-start d-flex align-items-center justify-content-between px-2 py-1 shadow-sm" 
                                onclick="openSelectCustomerModal()" 
                                id="btnCustomerDisplay" 
                                title="Klik untuk mencari dan memilih pelanggan (F3)">
                            <div class="d-flex align-items-center gap-2 overflow-hidden me-1">
                                <i class="bi bi-person-circle text-primary" id="customerDisplayIcon"></i>
                                <span class="fw-semibold text-truncate text-dark" id="customerDisplayName" style="font-size: 0.82rem;">
                                    Pelanggan: Umum (Walk-in)
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                <span class="badge bg-secondary-subtle text-secondary py-1 px-2 d-none" id="customerDisplayBadge">Umum</span>
                                <i class="bi bi-chevron-expand text-muted" style="font-size: 0.75rem;"></i>
                            </div>
                        </button>
                        <!-- Hidden customerSelect sync element for flawless backwards-compatibility -->
                        <select id="customerSelect" class="d-none">
                            <option value="">Pelanggan: Umum (Walk-in)</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" 
                                        data-code="{{ $c->code }}" 
                                        data-phone="{{ $c->phone }}" 
                                        data-level="{{ $c->member_level }}" 
                                        data-points="{{ $c->points ?? 0 }}">
                                    {{ $c->name }} ({{ ucfirst($c->member_level) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="openSelectCustomerModal()" title="Cari / Pilih Pelanggan (F3)">
                        <i class="bi bi-search"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#quickCustomerModal" title="Tambah Pelanggan Baru">
                        <i class="bi bi-person-plus-fill"></i>
                    </button>
                    <button type="button" class="btn btn-outline-warning text-dark btn-sm fw-semibold d-flex align-items-center gap-1" onclick="promptHoldCart()" title="Tahan Transaksi (Hold / F7)">
                        <i class="bi bi-pause-circle-fill text-warning"></i> <span>Hold</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearCart()" title="Kosongkan Keranjang">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>

                <!-- Cart Items Table Scroll: ONLY THIS SCROLLS -->
                <div class="cart-items-scroll p-2 mb-2">
                    <div id="cartItemsList">
                        <!-- Items rendered by Vanilla JS -->
                    </div>
                    <div id="cartEmptyState" class="text-center py-4 text-muted">
                        <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary"></i>
                        <span>Keranjang belanja masih kosong</span>
                        <div class="small text-muted mt-1">Pilih produk atau scan barcode</div>
                    </div>
                </div>

                <!-- Calculation Summary & Checkout: Fixed Footer -->
                <div class="border-top pt-2 mb-2 cart-footer-fixed">
                    <div class="d-flex justify-content-between mb-1 small text-muted">
                        <span>Subtotal Barang:</span>
                        <span id="textSubtotal" class="fw-semibold text-dark">Rp 0</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-1 small">
                        <span class="text-muted">Diskon Nota:</span>
                        <div class="d-flex gap-1" style="width: 200px;">
                            <input type="number" id="inputDiscount" min="0" class="form-control form-control-sm text-end" value="0" oninput="renderCartSummary()">
                            <select id="selectDiscountType" class="form-select form-select-sm" style="width: 80px;" onchange="renderCartSummary()">
                                <option value="fixed">Rp</option>
                                <option value="percentage">%</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2 small">
                        <span class="text-muted">Pajak (PPN):</span>
                        <div class="d-flex align-items-center gap-1">
                            <input type="number" id="inputTax" min="0" max="100" class="form-control form-control-sm text-end" style="width: 65px;" value="{{ $defaultTax }}" oninput="renderCartSummary()">
                            <span>%</span>
                        </div>
                    </div>

                    <!-- Grand Total Banner -->
                    <div class="grand-total-box d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <small class="text-white-50 text-uppercase d-block" style="font-size: 0.72rem;">Total Tagihan</small>
                            <h3 class="fw-bold mb-0 fs-4 fs-xl-3" id="textGrandTotal">Rp 0</h3>
                        </div>
                        <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-bold" id="textTotalItems">0 Item</span>
                    </div>

                    <!-- Checkout Button (F4) -->
                    <button type="button" id="btnOpenCheckout" class="btn btn-warning w-100 fw-bold py-2 py-xl-3 text-dark shadow d-flex align-items-center justify-content-center gap-2 rounded-3 fs-5" onclick="openCheckoutModal()" disabled>
                        <i class="bi bi-wallet2"></i> Bayar Sekarang (F4)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PEMBAYARAN / CHECKOUT -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="checkoutModalLabel"><i class="bi bi-cash-stack text-success me-2"></i> Pembayaran Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <!-- Left: Payment details -->
                    <div class="col-md-7">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Metode Pembayaran</label>
                            <div class="row g-2" id="paymentMethodButtons">
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="payment_method" id="payCash" value="cash" checked onchange="handlePaymentMethodChange('cash')">
                                    <label class="btn btn-outline-primary w-100 py-2 fw-semibold rounded-3" for="payCash">
                                        <i class="bi bi-cash d-block fs-4 mb-1"></i> Tunai (Cash)
                                    </label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="payment_method" id="payQris" value="qris" onchange="handlePaymentMethodChange('qris')">
                                    <label class="btn btn-outline-primary w-100 py-2 fw-semibold rounded-3" for="payQris">
                                        <i class="bi bi-qr-code-scan d-block fs-4 mb-1"></i> QRIS
                                    </label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="payment_method" id="payTransfer" value="transfer" onchange="handlePaymentMethodChange('transfer')">
                                    <label class="btn btn-outline-primary w-100 py-2 fw-semibold rounded-3" for="payTransfer">
                                        <i class="bi bi-bank d-block fs-4 mb-1"></i> Transfer
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="payment_method" id="payDebit" value="debit" onchange="handlePaymentMethodChange('debit')">
                                    <label class="btn btn-outline-primary w-100 py-2 fw-semibold rounded-3" for="payDebit">
                                        <i class="bi bi-credit-card d-block fs-4 mb-1"></i> Kartu Debit
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="payment_method" id="payCredit" value="credit" onchange="handlePaymentMethodChange('credit')">
                                    <label class="btn btn-outline-primary w-100 py-2 fw-semibold rounded-3" for="payCredit">
                                        <i class="bi bi-credit-card-2-front d-block fs-4 mb-1"></i> Kartu Kredit
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Uang Pembayaran -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Uang Dibayarkan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" id="inputPaidAmount" class="form-control form-control-lg fw-bold fs-4 text-end" oninput="calcChange()">
                        </div>

                        <!-- Quick Cash Shortcuts (Khusus Cash) -->
                        <div id="quickCashContainer" class="mb-3">
                            <label class="form-label small text-muted">Uang Pas & Pecahan Cepat:</label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-cash-btn" onclick="setQuickCash('exact')">Uang Pas</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-cash-btn" onclick="setQuickCash(10000)">10.000</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-cash-btn" onclick="setQuickCash(20000)">20.000</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-cash-btn" onclick="setQuickCash(50000)">50.000</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary quick-cash-btn" onclick="setQuickCash(100000)">100.000</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Catatan Transaksi</label>
                            <input type="text" id="inputSaleNotes" class="form-control form-control-sm" placeholder="Opsional...">
                        </div>
                    </div>

                    <!-- Right: Summary & Change -->
                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded-4 border h-100 d-flex flex-column justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-3 text-secondary">Ringkasan Tagihan</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Total Tagihan:</span>
                                    <span class="fw-bold fs-5 text-dark" id="modalGrandTotal">Rp 0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted small">Uang Diterima:</span>
                                    <span class="fw-bold fs-5 text-primary" id="modalPaidTotal">Rp 0</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center p-3 rounded-3 bg-white border">
                                    <span class="fw-semibold">Kembalian:</span>
                                    <span class="fw-bold fs-4 text-success" id="modalChangeAmount">Rp 0</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="button" id="btnSubmitPayment" class="btn btn-success w-100 py-3 fw-bold fs-5 shadow rounded-3 d-flex align-items-center justify-content-center gap-2" onclick="submitCheckout()">
                                    <i class="bi bi-check2-circle"></i> Selesai & Bayar (Enter)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TRANSAKSI BERHASIL & CETAK STRUK -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm text-center">
        <div class="modal-content rounded-4 border-0 shadow p-4">
            <div class="text-success mb-3">
                <i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>
            </div>
            <h5 class="fw-bold mb-1">Transaksi Berhasil!</h5>
            <p class="text-muted small mb-3" id="successInvoiceText">Invoice: INV-XXXX</p>

            <div class="p-3 bg-light rounded-3 mb-4 border">
                <small class="text-muted d-block">Uang Kembalian:</small>
                <h3 class="fw-bold text-success mb-0" id="successChangeText">Rp 0</h3>
            </div>

            <div class="d-flex flex-column gap-2">
                <a href="#" id="btnPrintReceipt" target="_blank" class="btn btn-primary-custom py-2 shadow-sm d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-printer-fill"></i> Cetak Struk Kasir
                </a>
                <button type="button" class="btn btn-light border py-2" data-bs-dismiss="modal" onclick="resetPos()">
                    Transaksi Baru (ESC)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL QUICK ADD CUSTOMER -->
<div class="modal fade" id="quickCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Tambah Pelanggan Baru Cepat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" id="quickCustName" class="form-control" placeholder="Nama pelanggan..." required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nomor WhatsApp / HP</label>
                    <input type="text" id="quickCustPhone" class="form-control" placeholder="08xxxxxxxx">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary-custom" onclick="saveQuickCustomer()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PILIH & CARI PELANGGAN -->
<div class="modal fade" id="selectCustomerModal" tabindex="-1" aria-labelledby="selectCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4 d-flex justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="selectCustomerModalLabel">Pilih Pelanggan / Member</h6>
                        <small class="text-muted">Cari member toko untuk mencatat transaksi dan akumulasi poin</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-primary-custom btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1" onclick="openNewCustomerFromSelectModal()">
                        <i class="bi bi-person-plus-fill me-1"></i> Pelanggan Baru
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3 p-md-4">
                <!-- Search Box -->
                <div class="input-group mb-3 shadow-sm rounded-3 overflow-hidden border">
                    <span class="input-group-text bg-white border-0 text-muted ps-3">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" 
                           id="customerSearchModalInput" 
                           class="form-control border-0 py-2 shadow-none" 
                           placeholder="Ketik nama pelanggan, nomor WhatsApp/HP, atau kode member..." 
                           autocomplete="off"
                           oninput="filterCustomersInModal(this.value)">
                    <button type="button" class="btn btn-white border-0 text-muted d-none" id="btnClearCustomerSearch" onclick="clearCustomerSearch()" title="Hapus Pencarian">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>

                <!-- Quick Filter Group & Counter -->
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="d-flex gap-1" id="customerLevelFilterGroup">
                        <button type="button" class="btn btn-sm btn-primary-custom active rounded-pill px-3 filter-level-btn" data-level="all" onclick="setCustomerFilterLevel('all', this)">Semua</button>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 filter-level-btn" data-level="member" onclick="setCustomerFilterLevel('member', this)">Member</button>
                        <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 filter-level-btn" data-level="regular" onclick="setCustomerFilterLevel('regular', this)">Non-Member / Reguler</button>
                    </div>
                    <small class="text-muted fw-semibold" id="customerCountBadge">Menampilkan {{ count($customers) }} pelanggan</small>
                </div>

                <!-- Default Option: Pelanggan Umum (Walk-in) Card -->
                <div class="customer-card-item p-3 rounded-3 mb-2 d-flex align-items-center justify-content-between" 
                     id="customerCardGeneral"
                     onclick="applyCustomerSelection('', 'Pelanggan: Umum (Walk-in)', 'umum')">
                    <div class="d-flex align-items-center gap-3">
                        <div class="customer-avatar bg-secondary-subtle text-secondary">
                            <i class="bi bi-person fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                Pelanggan Umum (Walk-in)
                                <span class="badge bg-secondary-subtle text-secondary fw-normal">Default</span>
                            </div>
                            <small class="text-muted">Pelanggan biasa tanpa kartu member atau akumulasi poin belanja</small>
                        </div>
                    </div>
                    <div class="flex-shrink-0 ms-2">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">Pilih</button>
                    </div>
                </div>

                <!-- Scrollable Customer List Container -->
                <div class="overflow-y-auto pe-1" id="customerListContainer" style="max-height: 360px;">
                    <!-- Rendered by JavaScript dynamically -->
                </div>

                <!-- Empty State -->
                <div class="text-center py-5 d-none" id="customerEmptyState">
                    <div class="rounded-circle bg-light d-inline-flex p-3 text-muted mb-2">
                        <i class="bi bi-person-x fs-1"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Pelanggan Tidak Ditemukan</h6>
                    <p class="text-muted small mb-3">Tidak ada data pelanggan yang cocok dengan kata kunci tersebut.</p>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="openNewCustomerFromSelectModal()">
                        <i class="bi bi-person-plus-fill me-1"></i> Tambah Pelanggan Baru Sekarang
                    </button>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 justify-content-between bg-light">
                <small class="text-muted">Tekan <kbd class="bg-secondary text-white px-1">ESC</kbd> untuk menutup &bull; <kbd class="bg-secondary text-white px-1">Enter</kbd> untuk memilih</small>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAHAN TRANSAKSI (HOLD) -->
<div class="modal fade" id="holdConfirmModal" tabindex="-1" aria-labelledby="holdConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="holdConfirmModalLabel">
                    <i class="bi bi-pause-circle-fill text-warning me-2"></i> Tahan Transaksi Sementara (Hold)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Transaksi akan disimpan sementara di memori kasir agar Anda dapat melayani antrean berikutnya. Anda dapat memanggil kembali (*recall*) transaksi ini kapan saja.
                </p>
                <div class="bg-light p-3 rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Total Item Belanja:</span>
                        <strong class="text-dark" id="holdSummaryItems">0 Item</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Total Tagihan Saat Ini:</span>
                        <strong class="text-primary fs-6" id="holdSummaryTotal">Rp 0</strong>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Catatan / No. Meja / Nama Pemesan <span class="text-muted fw-normal">(Opsional)</span></label>
                    <input type="text" id="holdNoteInput" class="form-control form-control-lg" placeholder="Contoh: Meja 4 / Bpk. Budi / Ambil Dompet" autofocus autocomplete="off">
                    <div class="form-text small">Tekan <strong>Enter</strong> untuk langsung menyimpan dan mengosongkan keranjang kasir.</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning fw-bold rounded-pill px-4" onclick="confirmHoldCart()">
                    <i class="bi bi-check-circle me-1"></i> Simpan & Tahan (Enter)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DAFTAR TRANSAKSI DITAHAN (HELD LIST) -->
<div class="modal fade" id="heldListModal" tabindex="-1" aria-labelledby="heldListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-warning-subtle text-warning p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-clock-history fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="heldListModalLabel">Daftar Transaksi Ditahan (Hold)</h5>
                        <small class="text-muted">Pilih transaksi yang ingin dilanjutkan kembali ke kasir (Shortcut: F8)</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div id="heldTransactionsContainer" style="max-height: 420px; overflow-y: auto;">
                    <!-- Rendered by JS -->
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill" onclick="clearAllHeldTransactions()" id="btnClearAllHeld" style="display: none;">
                    <i class="bi bi-trash me-1"></i> Hapus Semua Hold
                </button>
                <button type="button" class="btn btn-secondary rounded-pill px-4 ms-auto" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Item Price & Quantity Modal -->
<div class="modal fade" id="customItemModal" tabindex="-1" aria-labelledby="customItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center bg-light rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-calculator fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="customItemModalLabel">Sesuaikan Harga / Beli Nominal</h6>
                        <small class="text-muted" id="customModalItemTitle">Nama Produk</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <!-- Info Produk & Harga Normal -->
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Harga Normal Sistem:</span>
                        <span class="fw-bold text-dark small" id="customModalDefaultPrice">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Stok Tersedia:</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" id="customModalStock">0</span>
                    </div>
                </div>

                <!-- Tabs: Pilihan Mode -->
                <ul class="nav nav-pills nav-fill mb-3 bg-light p-1 rounded-3" id="customPriceTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-2 fw-semibold small" id="tab-nominal" data-bs-toggle="pill" data-bs-target="#panel-nominal" type="button" role="tab" onclick="switchCustomMode('nominal')">
                            <i class="bi bi-cash-coin me-1"></i> Beli Nominal (Rp)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2 fw-semibold small" id="tab-price" data-bs-toggle="pill" data-bs-target="#panel-price" type="button" role="tab" onclick="switchCustomMode('price')">
                            <i class="bi bi-tags me-1"></i> Ubah Harga Satuan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2 fw-semibold small" id="tab-qty" data-bs-toggle="pill" data-bs-target="#panel-qty" type="button" role="tab" onclick="switchCustomMode('qty')">
                            <i class="bi bi-box-seam me-1"></i> Kuantitas (Qty)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="customPriceTabContent">
                    <!-- Tab 1: Beli Nominal Uang -->
                    <div class="tab-pane fade show active" id="panel-nominal" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">
                                Masukkan Nominal Uang Pembeli (Rp):
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text fw-bold bg-white text-muted">Rp</span>
                                <input type="number" class="form-control fw-bold fs-4 text-primary" id="inputCustomNominal" placeholder="17000" min="1" step="100" oninput="calculateFromNominal()">
                            </div>
                            <div class="form-text small text-muted mt-1">
                                <i class="bi bi-info-circle me-1"></i> Contoh: Masukkan <code>17000</code> jika pembeli ingin beli senilai Rp 17.000. Kuantitas satuan akan otomatis disesuaikan.
                            </div>
                        </div>

                        <!-- Shortcut Quick Nominal -->
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">Pilihan Cepat Nominal:</label>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setQuickNominal(5000)">Rp 5.000</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setQuickNominal(10000)">Rp 10.000</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setQuickNominal(15000)">Rp 15.000</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setQuickNominal(17000)">Rp 17.000</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setQuickNominal(20000)">Rp 20.000</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setQuickNominal(50000)">Rp 50.000</button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Ubah Harga Satuan -->
                    <div class="tab-pane fade" id="panel-price" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">
                                Harga Satuan Baru (<span id="customPriceUnitLabel">per kg</span>):
                            </label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold bg-white text-muted">Rp</span>
                                <input type="number" class="form-control fw-bold" id="inputCustomPrice" placeholder="18500" min="0" step="100" oninput="calculateFromPrice()">
                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="resetToOriginalPrice()" title="Kembalikan ke harga normal sistem">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </button>
                            </div>
                            <div class="form-text small text-muted mt-1">
                                Ubah harga per satuan jika ada kesepakatan harga khusus / tawar.
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Kuantitas Manual / Timbangan -->
                    <div class="tab-pane fade" id="panel-qty" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">
                                Kuantitas / Berat Timbangan (<span id="customQtyUnitLabel">kg</span>):
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control fw-bold" id="inputCustomQty" placeholder="1" min="0.001" step="0.001" oninput="calculateFromQty()">
                                <span class="input-group-text bg-white text-muted fw-semibold" id="inputCustomQtyUnit">kg</span>
                            </div>
                            <div class="form-text small text-muted mt-1">
                                Masukkan desimal timbangan (contoh: <code>0.5</code>, <code>0.75</code>, <code>1.25</code>).
                            </div>
                        </div>

                        <!-- Shortcut Quick Fractions -->
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">Pilihan Cepat Kuantitas:</label>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setQuickQty(0.25)">1/4 (0.25)</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setQuickQty(0.5)">1/2 (0.5)</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setQuickQty(0.75)">3/4 (0.75)</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setQuickQty(1)">1</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setQuickQty(1.5)">1.5</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="setQuickQty(2)">2</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Hasil Perhitungan -->
                <div class="card bg-primary-subtle border-0 rounded-3 p-3 mt-3">
                    <div class="small fw-bold text-primary mb-2 d-flex align-items-center">
                        <i class="bi bi-receipt-cutoff me-1"></i> Ringkasan Penyesuaian Item:
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-secondary small">Kuantitas Didapat:</span>
                        <span class="fw-bold fs-6 text-dark" id="previewCustomQty">0 pcs</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-secondary small">Harga Satuan Terhitung:</span>
                        <span class="text-dark small" id="previewCustomPrice">Rp 0 / pcs</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-1">
                        <span class="fw-bold text-dark">Total Pembayaran Item:</span>
                        <span class="fw-bold fs-5 text-primary" id="previewCustomTotal">Rp 0</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 justify-content-between bg-light rounded-bottom-4">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="resetCustomItemToDefault()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset ke Standar
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3 border" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary-custom btn-sm rounded-pill px-4 fw-semibold shadow-sm" onclick="applyCustomItemChanges()">
                        <i class="bi bi-check2-circle me-1"></i> Terapkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-collapse sidebar in POS mode
    document.body.classList.add('sidebar-collapsed');
    document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('sidebar-collapsed');
    });

    // In-memory cart items
    let cart = [];
    let currentGrandTotal = 0;
    const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    const quickCustomerModal = new bootstrap.Modal(document.getElementById('quickCustomerModal'));
    const selectCustomerModal = new bootstrap.Modal(document.getElementById('selectCustomerModal'));
    const holdConfirmModal = new bootstrap.Modal(document.getElementById('holdConfirmModal'));
    const heldListModal = new bootstrap.Modal(document.getElementById('heldListModal'));
    const customItemModal = new bootstrap.Modal(document.getElementById('customItemModal'));
    const HOLD_KEY = 'pos_held_transactions_' + '{{ auth()->id() }}';

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F2') {
            e.preventDefault();
            document.getElementById('barcodeSearchInput').focus();
            document.getElementById('barcodeSearchInput').select();
        } else if (e.key === 'F3') {
            e.preventDefault();
            openSelectCustomerModal();
        } else if (e.key === 'F4') {
            e.preventDefault();
            if (cart.length > 0) {
                openCheckoutModal();
            }
        } else if (e.key === 'F7') {
            e.preventDefault();
            promptHoldCart();
        } else if (e.key === 'F8') {
            e.preventDefault();
            openHeldTransactionsModal();
        } else if (e.key === 'Enter') {
            const checkoutModalEl = document.getElementById('checkoutModal');
            const holdConfirmEl = document.getElementById('holdConfirmModal');
            const selectCustomerEl = document.getElementById('selectCustomerModal');
            const customItemEl = document.getElementById('customItemModal');
            if (checkoutModalEl && checkoutModalEl.classList.contains('show')) {
                e.preventDefault();
                submitCheckout();
            } else if (holdConfirmEl && holdConfirmEl.classList.contains('show')) {
                e.preventDefault();
                confirmHoldCart();
            } else if (selectCustomerEl && selectCustomerEl.classList.contains('show')) {
                e.preventDefault();
                selectFirstFilteredCustomer();
            } else if (customItemEl && customItemEl.classList.contains('show')) {
                e.preventDefault();
                applyCustomItemChanges();
            }
        }
    });

    // Barcode Search with Debounce and direct hit
    let searchTimeout = null;
    document.getElementById('barcodeSearchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();

        searchTimeout = setTimeout(() => {
            fetchProducts(query);
        }, 300);
    });

    // Enter key on barcode scanner = instant add if 1 match
    document.getElementById('barcodeSearchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = this.value.trim();
            if (val) {
                fetch(`/pos/search-product?q=${encodeURIComponent(val)}`)
                    .then(res => res.json())
                    .then(res => {
                        if (res.success && res.data.length === 1) {
                            addToCart(res.data[0]);
                            this.value = '';
                        }
                    });
            }
        }
    });

    function clearSearch() {
        document.getElementById('barcodeSearchInput').value = '';
        fetchProducts('');
    }

    function setCategoryPill(categoryId, btn) {
        document.querySelectorAll('.category-pill').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('categoryFilter').value = categoryId;
        fetchProducts(document.getElementById('barcodeSearchInput').value.trim(), categoryId);
    }

    function filterProductsByCategory(categoryId) {
        fetchProducts(document.getElementById('barcodeSearchInput').value.trim(), categoryId);
    }

    function fetchProducts(query = '', categoryId = '') {
        const cat = categoryId || document.getElementById('categoryFilter').value;
        fetch(`/pos/search-product?q=${encodeURIComponent(query)}&category_id=${encodeURIComponent(cat)}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    renderProductGrid(res.data);
                }
            });
    }

    function renderProductGrid(products) {
        const grid = document.getElementById('productsGrid');
        if (products.length === 0) {
            grid.innerHTML = `
                <div class="col-12 text-center py-5 text-muted">
                    <i class="bi bi-search fs-1 d-block mb-2 text-secondary"></i>
                    Tidak ada produk yang cocok dengan pencarian.
                </div>
            `;
            return;
        }

        let html = '';
        products.forEach(p => {
            const imgHtml = p.image 
                ? `<img src="/storage/${p.image}" alt="${p.name}" class="rounded-3 shadow-sm" style="width: 100%; height: 95px; object-fit: cover;">`
                : `<div class="rounded-3 bg-light text-muted d-flex align-items-center justify-content-center mx-auto" style="width: 100%; height: 95px;"><i class="bi bi-box-seam fs-2"></i></div>`;

            const stockDisplay = formatStockDisplay(p.stock);
            const stockBadge = p.stock <= 0 
                ? `<span class="badge bg-danger" style="font-size: 0.72rem;">Habis (0)</span>`
                : (p.stock <= p.min_stock 
                    ? `<span class="badge bg-warning text-dark" style="font-size: 0.72rem;">${stockDisplay} ${p.unit?.short_name || 'pcs'}</span>`
                    : `<span class="badge bg-light text-secondary border" style="font-size: 0.72rem;">${stockDisplay} ${p.unit?.short_name || 'pcs'}</span>`);

            html += `
                <div class="col-6 col-sm-4 col-md-4 col-xl-3">
                    <div class="product-card p-3 h-100 d-flex flex-column" onclick='addToCart(${JSON.stringify(p)})'>
                        <div class="text-center mb-2">
                            ${imgHtml}
                        </div>
                        <div class="fw-bold text-dark small text-truncate mb-1" title="${p.name}">${p.name}</div>
                        <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                            <div class="fw-bold text-primary small">Rp ${new Intl.NumberFormat('id-ID').format(p.selling_price)}</div>
                            ${stockBadge}
                        </div>
                    </div>
                </div>
            `;
        });
        grid.innerHTML = html;
    }

    // Cart Operations
    function addToCart(product) {
        const stockNum = parseFloat(product.stock);
        if (stockNum <= 0) {
            alert(`Stok untuk '${product.name}' habis!`);
            return;
        }

        const existing = cart.find(item => item.product_id === product.id);
        if (existing) {
            if (existing.qty + 1 > existing.stock) {
                alert(`Tidak dapat menambah item. Stok tersedia hanya ${formatStockDisplay(existing.stock)}.`);
                return;
            }
            existing.qty = Math.round((existing.qty + 1) * 1000) / 1000;
            if (existing.is_custom_subtotal) {
                existing.subtotal = Math.round(existing.price * existing.qty);
                existing.is_custom_subtotal = false;
            }
        } else {
            const price = parseFloat(product.selling_price);
            cart.push({
                product_id: product.id,
                name: product.name,
                price: price,
                original_price: price,
                stock: stockNum,
                unit: product.unit?.short_name || 'pcs',
                qty: 1,
                discount: 0,
                is_custom: false,
                is_custom_subtotal: false,
                subtotal: price
            });
        }

        renderCart();

        // Auto-scroll cart down to show newly added item
        setTimeout(() => {
            const scrollContainer = document.querySelector('.cart-items-scroll');
            if (scrollContainer) {
                scrollContainer.scrollTop = scrollContainer.scrollHeight;
            }
        }, 50);
    }

    function updateQty(index, delta) {
        const item = cart[index];
        const newQty = Math.round((item.qty + delta) * 1000) / 1000;

        if (newQty <= 0) {
            removeItem(index);
            return;
        }

        if (newQty > item.stock) {
            alert(`Maksimal stok tercapai (${formatStockDisplay(item.stock)} ${item.unit})`);
            return;
        }

        item.qty = newQty;
        item.subtotal = Math.round(item.price * item.qty);
        item.is_custom_subtotal = false;
        renderCart();
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function clearCart() {
        if (cart.length > 0 && confirm('Kosongkan semua item di keranjang?')) {
            cart = [];
            renderCart();
        }
    }

    function renderCart() {
        const list = document.getElementById('cartItemsList');
        const emptyState = document.getElementById('cartEmptyState');
        const btnCheckout = document.getElementById('btnOpenCheckout');

        if (cart.length === 0) {
            list.innerHTML = '';
            emptyState.style.display = 'block';
            btnCheckout.disabled = true;
            renderCartSummary();
            return;
        }

        emptyState.style.display = 'none';
        btnCheckout.disabled = false;

        let html = '';
        cart.forEach((item, idx) => {
            const lineTotal = (item.subtotal !== undefined && item.is_custom_subtotal)
                ? item.subtotal
                : ((item.price * item.qty) - item.discount);

            const isCustom = item.is_custom || (item.original_price && item.price !== item.original_price) || item.is_custom_subtotal || (!Number.isInteger(item.qty));

            const customBadge = isCustom 
                ? `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1" style="font-size: 0.65rem; padding: 2px 5px;" title="Harga atau kuantitas disesuaikan">Custom</span>` 
                : '';

            const unitLabel = item.unit || 'pcs';
            const formattedQty = formatQty(item.qty);

            html += `
                <div class="card p-2 mb-2 border rounded-3 bg-white shadow-xs">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="fw-semibold text-dark small text-truncate pe-2" style="max-width: 165px;" title="${item.name}">
                            ${item.name} ${customBadge}
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-link text-primary p-0 text-decoration-none" onclick="openCustomItemModal(${idx})" title="Sesuaikan Harga / Beli Nominal (Contoh: Beli Rp 17.000)">
                                <i class="bi bi-pencil-square fs-6"></i>
                            </button>
                            <button type="button" class="btn btn-sm text-danger p-0 ms-1" onclick="removeItem(${idx})" title="Hapus item">
                                <i class="bi bi-x fs-5"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small cursor-pointer" onclick="openCustomItemModal(${idx})" title="Klik untuk ubah harga / beli nominal">
                            Rp ${new Intl.NumberFormat('id-ID').format(item.price)}<span style="font-size: 0.72rem;">/${unitLabel}</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-light border qty-btn" onclick="updateQty(${idx}, -1)">-</button>
                            <span class="px-2 fw-bold small cursor-pointer" onclick="openCustomItemModal(${idx})" title="Klik untuk atur kuantitas">${formattedQty}</span>
                            <button type="button" class="btn btn-sm btn-light border qty-btn" onclick="updateQty(${idx}, 1)">+</button>
                        </div>
                        <div class="fw-bold text-dark small">
                            Rp ${new Intl.NumberFormat('id-ID').format(lineTotal)}
                        </div>
                    </div>
                </div>
            `;
        });

        list.innerHTML = html;
        renderCartSummary();
    }

    function renderCartSummary() {
        let subtotal = 0;
        let totalItems = 0;

        cart.forEach(item => {
            const lineTotal = (item.subtotal !== undefined && item.is_custom_subtotal)
                ? item.subtotal
                : ((item.price * item.qty) - item.discount);
            subtotal += lineTotal;
            totalItems += item.qty;
        });

        const discountInput = parseFloat(document.getElementById('inputDiscount').value) || 0;
        const discountType = document.getElementById('selectDiscountType').value;
        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = (subtotal * discountInput) / 100;
        } else {
            discountAmount = discountInput;
        }

        if (discountAmount > subtotal) discountAmount = subtotal;

        const afterDiscount = subtotal - discountAmount;
        const taxPercentage = parseFloat(document.getElementById('inputTax').value) || 0;
        const taxAmount = (afterDiscount * taxPercentage) / 100;

        currentGrandTotal = Math.round(afterDiscount + taxAmount);

        document.getElementById('textSubtotal').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal);
        document.getElementById('textGrandTotal').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(currentGrandTotal);
        document.getElementById('textTotalItems').innerText = formatQty(totalItems) + ' Item';
    }

    // Checkout Flow
    function openCheckoutModal() {
        document.getElementById('modalGrandTotal').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(currentGrandTotal);
        document.getElementById('inputPaidAmount').value = currentGrandTotal;
        document.getElementById('modalPaidTotal').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(currentGrandTotal);
        document.getElementById('modalChangeAmount').innerText = 'Rp 0';

        checkoutModal.show();
        setTimeout(() => {
            const input = document.getElementById('inputPaidAmount');
            input.focus();
            input.select();
        }, 400);
    }

    function handlePaymentMethodChange(method) {
        const quickCash = document.getElementById('quickCashContainer');
        if (method === 'cash') {
            quickCash.style.display = 'block';
        } else {
            quickCash.style.display = 'none';
            document.getElementById('inputPaidAmount').value = currentGrandTotal;
        }
        calcChange();
    }

    function setQuickCash(val) {
        if (val === 'exact') {
            document.getElementById('inputPaidAmount').value = currentGrandTotal;
        } else {
            document.getElementById('inputPaidAmount').value = val;
        }
        calcChange();
    }

    function calcChange() {
        const paid = parseFloat(document.getElementById('inputPaidAmount').value) || 0;
        const change = paid - currentGrandTotal;

        document.getElementById('modalPaidTotal').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(paid);
        document.getElementById('modalChangeAmount').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.max(0, change));

        const btnSubmit = document.getElementById('btnSubmitPayment');
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;

        if (selectedMethod === 'cash' && change < 0) {
            btnSubmit.disabled = true;
            document.getElementById('modalChangeAmount').className = 'fw-bold fs-4 text-danger';
        } else {
            btnSubmit.disabled = false;
            document.getElementById('modalChangeAmount').className = 'fw-bold fs-4 text-success';
        }
    }

    function submitCheckout() {
        const paidAmount = parseFloat(document.getElementById('inputPaidAmount').value) || 0;
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

        if (paymentMethod === 'cash' && paidAmount < currentGrandTotal) {
            alert('Nominal uang tunai kurang dari total tagihan.');
            return;
        }

        const payload = {
            items: cart.map(i => ({
                product_id: i.product_id,
                qty: i.qty,
                selling_price: i.price,
                subtotal: (i.subtotal !== undefined && i.is_custom_subtotal) ? i.subtotal : Math.round((i.price * i.qty) - i.discount),
                discount: i.discount
            })),
            customer_id: document.getElementById('customerSelect').value || null,
            discount_type: document.getElementById('selectDiscountType').value,
            discount_value: parseFloat(document.getElementById('inputDiscount').value) || 0,
            tax_percentage: parseFloat(document.getElementById('inputTax').value) || 0,
            paid_amount: paidAmount,
            payment_method: paymentMethod,
            notes: document.getElementById('inputSaleNotes').value
        };

        const btn = document.getElementById('btnSubmitPayment');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses...';

        fetch('/pos/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle"></i> Selesai & Bayar (Enter)';

            if (res.success) {
                checkoutModal.hide();
                document.getElementById('successInvoiceText').innerText = 'No. Nota: ' + res.invoice_number;
                document.getElementById('successChangeText').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(res.change_amount);
                document.getElementById('btnPrintReceipt').href = res.receipt_url;

                successModal.show();
            } else {
                alert('Gagal: ' + res.message);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle"></i> Selesai & Bayar (Enter)';
            alert('Terjadi kesalahan jaringan.');
        });
    }

    // ==========================================
    // FITUR PILIH & CARI PELANGGAN (MODAL)
    // ==========================================
    let customersList = @json($customers);
    let selectedCustomerId = '';
    let selectedCustomerName = 'Pelanggan: Umum (Walk-in)';
    let currentCustomerFilterLevel = 'all';

    function openSelectCustomerModal() {
        document.getElementById('customerSearchModalInput').value = '';
        document.getElementById('btnClearCustomerSearch').classList.add('d-none');
        currentCustomerFilterLevel = 'all';
        updateFilterLevelButtons();
        renderCustomerModalList(customersList, '');
        selectCustomerModal.show();
        setTimeout(() => {
            const input = document.getElementById('customerSearchModalInput');
            if (input) {
                input.focus();
            }
        }, 350);
    }

    function clearCustomerSearch() {
        const input = document.getElementById('customerSearchModalInput');
        input.value = '';
        document.getElementById('btnClearCustomerSearch').classList.add('d-none');
        filterCustomersInModal('');
        input.focus();
    }

    function setCustomerFilterLevel(level, btn) {
        currentCustomerFilterLevel = level;
        updateFilterLevelButtons();
        const query = document.getElementById('customerSearchModalInput').value;
        filterCustomersInModal(query);
    }

    function updateFilterLevelButtons() {
        document.querySelectorAll('.filter-level-btn').forEach(btn => {
            btn.classList.remove('btn-primary-custom', 'active');
            btn.classList.add('btn-light', 'border');
        });
        const activeBtn = document.querySelector(`.filter-level-btn[data-level="${currentCustomerFilterLevel}"]`);
        if (activeBtn) {
            activeBtn.classList.remove('btn-light', 'border');
            activeBtn.classList.add('btn-primary-custom', 'active');
        }
    }

    function filterCustomersInModal(query) {
        const clearBtn = document.getElementById('btnClearCustomerSearch');
        if (query && query.trim()) {
            clearBtn.classList.remove('d-none');
        } else {
            clearBtn.classList.add('d-none');
        }

        const q = (query || '').toLowerCase().trim();
        const filtered = customersList.filter(c => {
            const level = (c.member_level || '').toLowerCase();
            if (currentCustomerFilterLevel === 'member') {
                if (level === 'regular' || level === 'umum' || !c.member_level) {
                    return false;
                }
            } else if (currentCustomerFilterLevel === 'regular') {
                if (level !== 'regular' && level !== 'umum' && c.member_level) {
                    return false;
                }
            }

            if (!q) return true;

            const name = (c.name || '').toLowerCase();
            const phone = (c.phone || '').toLowerCase();
            const code = (c.code || '').toLowerCase();

            return name.includes(q) || phone.includes(q) || code.includes(q) || level.includes(q);
        });

        renderCustomerModalList(filtered, q);
    }

    function renderCustomerModalList(list = customersList, searchQuery = '') {
        const container = document.getElementById('customerListContainer');
        const emptyState = document.getElementById('customerEmptyState');
        const badge = document.getElementById('customerCountBadge');
        const cardGeneral = document.getElementById('customerCardGeneral');

        // Show/hide Pelanggan Umum card based on search query
        const isSearchingSpecific = searchQuery && !'umum'.includes(searchQuery) && !'walk-in'.includes(searchQuery) && !'pelanggan'.includes(searchQuery);
        if (isSearchingSpecific) {
            cardGeneral.classList.add('d-none');
        } else {
            cardGeneral.classList.remove('d-none');
            if (!selectedCustomerId) {
                cardGeneral.classList.add('active-selected');
                const btnInd = cardGeneral.querySelector('.btn');
                if (btnInd) {
                    btnInd.className = 'btn btn-primary btn-sm rounded-pill px-3 fw-semibold';
                    btnInd.innerHTML = '<i class="bi bi-check-lg me-1"></i> Terpilih';
                }
            } else {
                cardGeneral.classList.remove('active-selected');
                const btnInd = cardGeneral.querySelector('.btn');
                if (btnInd) {
                    btnInd.className = 'btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold';
                    btnInd.innerText = 'Pilih';
                }
            }
        }

        if (list.length === 0 && cardGeneral.classList.contains('d-none')) {
            container.innerHTML = '';
            emptyState.classList.remove('d-none');
            badge.innerText = '0 pelanggan ditemukan';
            return;
        }

        emptyState.classList.add('d-none');
        badge.innerText = `Menampilkan ${list.length} pelanggan`;

        let html = '';
        list.forEach(c => {
            const isSelected = selectedCustomerId && selectedCustomerId == c.id;
            const level = c.member_level ? c.member_level.toUpperCase() : 'REGULAR';
            const badgeClass = getMemberBadgeClass(level);
            const initial = (c.name || 'P').charAt(0).toUpperCase();

            html += `
                <div class="customer-card-item p-3 rounded-3 mb-2 d-flex align-items-center justify-content-between ${isSelected ? 'active-selected' : ''}"
                     onclick="applyCustomerSelection('${c.id}', '${escapeHtml(c.name)}', '${level}')">
                    <div class="d-flex align-items-center gap-3 overflow-hidden">
                        <div class="customer-avatar bg-primary-subtle text-primary">
                            ${initial}
                        </div>
                        <div class="overflow-hidden">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <span class="fw-bold text-dark text-truncate">${escapeHtml(c.name)}</span>
                                <span class="badge ${badgeClass} small py-1 px-2">${level}</span>
                                ${c.code ? `<span class="badge bg-light text-secondary border font-monospace small">${escapeHtml(c.code)}</span>` : ''}
                            </div>
                            <div class="d-flex align-items-center gap-3 text-muted small flex-wrap">
                                <span><i class="bi bi-telephone me-1 text-primary"></i> ${c.phone ? escapeHtml(c.phone) : '-'}</span>
                                ${c.points !== undefined && c.points !== null ? `<span><i class="bi bi-star-fill me-1 text-warning"></i> ${c.points} Poin</span>` : ''}
                                ${c.address ? `<span class="text-truncate" style="max-width: 200px;"><i class="bi bi-geo-alt me-1"></i> ${escapeHtml(c.address)}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0 ms-2">
                        ${isSelected 
                            ? `<span class="badge bg-primary text-white py-2 px-3 rounded-pill fw-semibold"><i class="bi bi-check-lg me-1"></i> Terpilih</span>`
                            : `<button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">Pilih</button>`
                        }
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    function getMemberBadgeClass(level) {
        switch((level || '').toLowerCase()) {
            case 'vip': return 'bg-dark text-warning border border-warning';
            case 'platinum': return 'bg-info-subtle text-info border border-info';
            case 'gold': return 'bg-warning-subtle text-warning-emphasis border border-warning';
            case 'silver': return 'bg-secondary-subtle text-secondary border border-secondary';
            default: return 'bg-primary-subtle text-primary';
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, function(m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[m];
        });
    }

    function selectFirstFilteredCustomer() {
        const firstCard = document.querySelector('#customerListContainer .customer-card-item');
        if (firstCard) {
            firstCard.click();
        } else {
            const cardGeneral = document.getElementById('customerCardGeneral');
            if (cardGeneral && !cardGeneral.classList.contains('d-none')) {
                cardGeneral.click();
            }
        }
    }

    function applyCustomerSelection(id, name, level = '') {
        selectedCustomerId = id ? String(id) : '';
        selectedCustomerName = name;

        // Sync hidden select element
        const select = document.getElementById('customerSelect');
        if (select) {
            select.value = selectedCustomerId;
        }

        // Update display button on cart header
        const displayName = document.getElementById('customerDisplayName');
        const displayIcon = document.getElementById('customerDisplayIcon');
        const displayBadge = document.getElementById('customerDisplayBadge');

        if (!selectedCustomerId) {
            displayName.innerText = 'Pelanggan: Umum (Walk-in)';
            displayIcon.className = 'bi bi-person-circle text-primary';
            displayBadge.classList.add('d-none');
        } else {
            displayName.innerText = name;
            displayIcon.className = 'bi bi-patch-check-fill text-success';
            displayBadge.innerText = level || 'Member';
            displayBadge.className = `badge ${getMemberBadgeClass(level || 'member')} py-1 px-2`;
            displayBadge.classList.remove('d-none');
        }

        selectCustomerModal.hide();

        // Focus back to barcode scan input for cashier ergonomics
        setTimeout(() => {
            const barcodeInput = document.getElementById('barcodeSearchInput');
            if (barcodeInput) {
                barcodeInput.focus();
            }
        }, 300);
    }

    function openNewCustomerFromSelectModal() {
        selectCustomerModal.hide();
        setTimeout(() => {
            quickCustomerModal.show();
            document.getElementById('quickCustName').focus();
        }, 400);
    }

    function resetPos() {
        cart = [];
        renderCart();
        document.getElementById('barcodeSearchInput').value = '';
        fetchProducts('');
        applyCustomerSelection('', 'Pelanggan: Umum (Walk-in)', 'umum');
        document.getElementById('barcodeSearchInput').focus();
    }

    // Quick customer save
    function saveQuickCustomer() {
        const name = document.getElementById('quickCustName').value.trim();
        const phone = document.getElementById('quickCustPhone').value.trim();

        if (!name) {
            alert('Nama pelanggan wajib diisi.');
            return;
        }

        fetch('/customers', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ name, phone })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const select = document.getElementById('customerSelect');
                const opt = document.createElement('option');
                opt.value = res.data.id;
                opt.innerText = res.data.name + ' (Regular)';
                opt.selected = true;
                select.appendChild(opt);

                customersList.unshift(res.data);
                applyCustomerSelection(res.data.id, res.data.name, res.data.member_level || 'Regular');

                quickCustomerModal.hide();
                document.getElementById('quickCustName').value = '';
                document.getElementById('quickCustPhone').value = '';
                showPosToast(`Pelanggan "${res.data.name}" berhasil ditambahkan dan dipilih!`);
            } else {
                alert(res.message || 'Gagal menyimpan pelanggan.');
            }
        });
    }

    // ==========================================
    // FITUR HOLD TRANSAKSI (PARK & RECALL SALE)
    // ==========================================
    function getHeldList() {
        try {
            const data = localStorage.getItem(HOLD_KEY);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            console.error('Error reading held transactions:', e);
            return [];
        }
    }

    function saveHeldList(list) {
        try {
            localStorage.setItem(HOLD_KEY, JSON.stringify(list));
        } catch (e) {
            console.error('Error saving held transactions:', e);
        }
        updateHoldBadge();
    }

    function updateHoldBadge() {
        const list = getHeldList();
        const badge = document.getElementById('navHoldCountBadge');
        const btn = document.getElementById('btnNavHoldList');
        if (badge) {
            badge.innerText = list.length;
        }
        if (btn) {
            if (list.length > 0) {
                btn.classList.remove('btn-outline-warning');
                btn.classList.add('btn-warning', 'text-dark', 'shadow-sm');
                if (badge) {
                    badge.classList.remove('bg-warning', 'text-dark');
                    badge.classList.add('bg-dark', 'text-white');
                }
            } else {
                btn.classList.remove('btn-warning', 'text-dark', 'shadow-sm');
                btn.classList.add('btn-outline-warning');
                if (badge) {
                    badge.classList.remove('bg-dark', 'text-white');
                    badge.classList.add('bg-warning', 'text-dark');
                }
            }
        }
    }

    function promptHoldCart() {
        if (cart.length === 0) {
            alert('Keranjang belanja masih kosong! Tidak ada transaksi untuk ditahan.');
            return;
        }

        const totalItems = cart.reduce((sum, i) => sum + i.qty, 0);
        document.getElementById('holdSummaryItems').innerText = `${totalItems} Item (${cart.length} Produk)`;
        document.getElementById('holdSummaryTotal').innerText = `Rp ${new Intl.NumberFormat('id-ID').format(currentGrandTotal)}`;

        const custSelect = document.getElementById('customerSelect');
        const custText = custSelect.value ? custSelect.options[custSelect.selectedIndex].text : '';
        document.getElementById('holdNoteInput').value = custText ? custText : `Antrean #${getHeldList().length + 1}`;

        holdConfirmModal.show();
        setTimeout(() => {
            const input = document.getElementById('holdNoteInput');
            if (input) {
                input.focus();
                input.select();
            }
        }, 400);
    }

    function confirmHoldCart() {
        if (cart.length === 0) {
            holdConfirmModal.hide();
            return;
        }

        const note = document.getElementById('holdNoteInput').value.trim() || `Transaksi Hold #${getHeldList().length + 1}`;
        const custSelect = document.getElementById('customerSelect');
        const custId = custSelect.value || '';
        const custName = custSelect.options[custSelect.selectedIndex]?.text || 'Umum (Walk-in)';
        const discount = parseFloat(document.getElementById('inputDiscount').value) || 0;
        const discountType = document.getElementById('selectDiscountType').value;
        const tax = parseFloat(document.getElementById('inputTax').value) || 0;
        const totalItems = cart.reduce((sum, i) => sum + i.qty, 0);

        const now = new Date();
        const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

        const heldItem = {
            id: 'hold_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
            note: note,
            customer_id: custId,
            customer_name: custName,
            cart: JSON.parse(JSON.stringify(cart)),
            discount: discount,
            discount_type: discountType,
            tax: tax,
            total_items: totalItems,
            grand_total: currentGrandTotal,
            created_at: timeStr,
            timestamp: Date.now()
        };

        const list = getHeldList();
        list.unshift(heldItem);
        saveHeldList(list);

        // Reset current active cart
        cart = [];
        applyCustomerSelection('', 'Pelanggan: Umum (Walk-in)', 'umum');
        document.getElementById('inputDiscount').value = '0';
        document.getElementById('selectDiscountType').value = 'fixed';
        renderCart();

        holdConfirmModal.hide();
        document.getElementById('holdNoteInput').value = '';

        showPosToast(`Transaksi "${note}" berhasil ditahan! Silakan layani pelanggan berikutnya.`);
    }

    function openHeldTransactionsModal() {
        renderHeldList();
        heldListModal.show();
    }

    function renderHeldList() {
        const container = document.getElementById('heldTransactionsContainer');
        const clearBtn = document.getElementById('btnClearAllHeld');
        const list = getHeldList();

        if (list.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <div class="rounded-circle bg-light d-inline-flex p-3 mb-3">
                        <i class="bi bi-clock-history text-secondary fs-1"></i>
                    </div>
                    <h6 class="fw-bold text-dark">Tidak Ada Transaksi yang Ditahan</h6>
                    <p class="small text-muted mb-0">
                        Saat ada pelanggan yang belum siap bayar, klik tombol <strong>"Hold"</strong> atau tekan <strong>F7</strong> untuk menahan sementara.
                    </p>
                </div>
            `;
            if (clearBtn) clearBtn.style.display = 'none';
            return;
        }

        if (clearBtn) clearBtn.style.display = 'inline-block';

        let html = '<div class="row g-3">';
        list.forEach(h => {
            const itemsSummary = h.cart.map(i => `${i.qty}x ${i.name}`).join(', ');
            html += `
                <div class="col-12">
                    <div class="card p-3 border rounded-3 bg-white shadow-xs">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="fw-bold text-dark mb-0">${h.note}</h6>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill small">
                                        <i class="bi bi-clock me-1"></i> ${h.created_at}
                                    </span>
                                </div>
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-person me-1"></i> Pelanggan: <strong>${h.customer_name}</strong>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fs-5 fw-bold text-primary">Rp ${new Intl.NumberFormat('id-ID').format(h.grand_total)}</div>
                                <span class="badge bg-light text-secondary border">${h.total_items} Item</span>
                            </div>
                        </div>

                        <div class="p-2 bg-light rounded-2 small text-muted text-truncate mb-3" title="${itemsSummary}">
                            <i class="bi bi-bag-check me-1"></i> ${itemsSummary}
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="deleteHeldTransaction('${h.id}')" title="Hapus transaksi ini">
                                <i class="bi bi-trash me-1"></i> Hapus
                            </button>
                            <button type="button" class="btn btn-success btn-sm rounded-pill px-4 fw-semibold shadow-sm" onclick="recallHeldTransaction('${h.id}')">
                                <i class="bi bi-play-circle-fill me-1"></i> Buka ke Kasir (Recall)
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        container.innerHTML = html;
    }

    function recallHeldTransaction(id) {
        const list = getHeldList();
        const found = list.find(h => h.id === id);
        if (!found) return;

        if (cart.length > 0) {
            const action = confirm(
                `Perhatian: Di keranjang kasir saat ini masih ada ${cart.length} barang.\n\n` +
                `Klik OK untuk menimpa keranjang saat ini dengan transaksi "${found.note}".\n` +
                `Klik Cancel jika ingin menahan transaksi aktif saat ini terlebih dahulu.`
            );
            if (!action) return;
        }

        // Restore state
        cart = JSON.parse(JSON.stringify(found.cart));
        if (found.customer_id) {
            const custObj = customersList.find(c => c.id == found.customer_id);
            applyCustomerSelection(found.customer_id, custObj ? custObj.name : (found.customer_name || 'Member'), custObj ? custObj.member_level : 'Member');
        } else {
            applyCustomerSelection('', 'Pelanggan: Umum (Walk-in)', 'umum');
        }
        document.getElementById('inputDiscount').value = found.discount || 0;
        document.getElementById('selectDiscountType').value = found.discount_type || 'fixed';
        document.getElementById('inputTax').value = found.tax ?? {{ $defaultTax }};

        // Remove restored item from held list
        const updatedList = list.filter(h => h.id !== id);
        saveHeldList(updatedList);

        renderCart();
        heldListModal.hide();
        showPosToast(`Transaksi "${found.note}" berhasil dimuat kembali ke kasir!`);
    }

    function deleteHeldTransaction(id) {
        if (confirm('Yakin ingin menghapus transaksi yang ditahan ini?')) {
            const list = getHeldList().filter(h => h.id !== id);
            saveHeldList(list);
            renderHeldList();
            showPosToast('Transaksi ditahan berhasil dihapus.');
        }
    }

    function clearAllHeldTransactions() {
        if (confirm('Yakin ingin menghapus SEMUA transaksi yang sedang ditahan?')) {
            saveHeldList([]);
            renderHeldList();
            showPosToast('Semua transaksi ditahan telah dibersihkan.');
        }
    }

    function showPosToast(message) {
        const toastEl = document.createElement('div');
        toastEl.className = 'position-fixed bottom-0 start-50 translate-middle-x mb-4 px-4 py-3 bg-dark text-white rounded-pill shadow-lg d-flex align-items-center gap-2';
        toastEl.style.zIndex = '9999';
        toastEl.style.animation = 'fadeIn 0.3s ease';
        toastEl.innerHTML = `<i class="bi bi-info-circle-fill text-warning fs-5"></i> <span>${message}</span>`;
        document.body.appendChild(toastEl);
        setTimeout(() => {
            toastEl.style.opacity = '0';
            toastEl.style.transition = 'opacity 0.5s ease';
            setTimeout(() => toastEl.remove(), 500);
        }, 3000);
    }

    // ==========================================
    // FITUR CUSTOM HARGA & KUANTITAS ITEM (POS)
    // ==========================================
    let activeCustomIndex = -1;
    let activeCustomMode = 'nominal';
    let isNominalMode = false;
    let previewQty = 1;
    let previewPrice = 0;
    let previewTotal = 0;

    function formatStockDisplay(val) {
        const num = parseFloat(val);
        if (isNaN(num)) return '0';
        return Number.isInteger(num) ? num.toString() : parseFloat(num.toFixed(3)).toString();
    }

    function formatQty(qty) {
        const num = parseFloat(qty);
        if (isNaN(num)) return '0';
        return Number.isInteger(num) ? num.toString() : parseFloat(num.toFixed(3)).toString();
    }

    function openCustomItemModal(index) {
        if (!cart[index]) return;
        activeCustomIndex = index;
        const item = cart[index];

        document.getElementById('customModalItemTitle').innerText = `${item.name} (${item.unit || 'pcs'})`;
        document.getElementById('customModalDefaultPrice').innerText = `Rp ${new Intl.NumberFormat('id-ID').format(item.original_price || item.price)} / ${item.unit || 'pcs'}`;
        document.getElementById('customModalStock').innerText = `${formatStockDisplay(item.stock)} ${item.unit || 'pcs'}`;
        
        document.getElementById('customPriceUnitLabel').innerText = `per ${item.unit || 'pcs'}`;
        document.getElementById('customQtyUnitLabel').innerText = item.unit || 'pcs';
        document.getElementById('inputCustomQtyUnit').innerText = item.unit || 'pcs';

        // Set initial values
        previewPrice = item.price;
        previewQty = item.qty;
        previewTotal = (item.subtotal !== undefined && item.is_custom_subtotal) ? item.subtotal : Math.round(item.price * item.qty);
        isNominalMode = !!item.is_custom_subtotal;

        document.getElementById('inputCustomPrice').value = previewPrice;
        document.getElementById('inputCustomQty').value = previewQty;
        document.getElementById('inputCustomNominal').value = previewTotal;

        updateCustomPreview();

        const tabNominalBtn = document.getElementById('tab-nominal');
        const tabPriceBtn = document.getElementById('tab-price');

        if (item.is_custom_subtotal) {
            switchCustomMode('nominal');
            const tabInstance = bootstrap.Tab.getOrCreateInstance(tabNominalBtn);
            tabInstance.show();
        } else if (item.price !== (item.original_price || item.price)) {
            switchCustomMode('price');
            const tabInstance = bootstrap.Tab.getOrCreateInstance(tabPriceBtn);
            tabInstance.show();
        } else {
            switchCustomMode('nominal');
            const tabInstance = bootstrap.Tab.getOrCreateInstance(tabNominalBtn);
            tabInstance.show();
        }

        customItemModal.show();
        setTimeout(() => {
            if (activeCustomMode === 'nominal') {
                const inp = document.getElementById('inputCustomNominal');
                if (inp) {
                    inp.focus();
                    inp.select();
                }
            }
        }, 400);
    }

    function switchCustomMode(mode) {
        activeCustomMode = mode;
        if (mode === 'nominal') {
            isNominalMode = true;
            calculateFromNominal();
        } else if (mode === 'price') {
            isNominalMode = false;
            calculateFromPrice();
        } else if (mode === 'qty') {
            isNominalMode = false;
            calculateFromQty();
        }
    }

    function calculateFromNominal() {
        const nominal = parseFloat(document.getElementById('inputCustomNominal').value) || 0;
        const currentItem = cart[activeCustomIndex];
        const unitPrice = parseFloat(document.getElementById('inputCustomPrice').value) || (currentItem ? currentItem.price : 0);

        if (unitPrice > 0 && nominal > 0) {
            previewQty = Math.round((nominal / unitPrice) * 1000) / 1000;
        } else {
            previewQty = 0;
        }

        previewPrice = unitPrice;
        previewTotal = nominal;
        isNominalMode = true;

        document.getElementById('inputCustomQty').value = previewQty;
        updateCustomPreview();
    }

    function calculateFromPrice() {
        const newPrice = parseFloat(document.getElementById('inputCustomPrice').value) || 0;
        const currentQty = parseFloat(document.getElementById('inputCustomQty').value) || 1;

        previewPrice = newPrice;
        previewQty = currentQty;
        previewTotal = Math.round(newPrice * currentQty);
        isNominalMode = false;

        document.getElementById('inputCustomNominal').value = previewTotal;
        updateCustomPreview();
    }

    function calculateFromQty() {
        const newQty = parseFloat(document.getElementById('inputCustomQty').value) || 0;
        const unitPrice = parseFloat(document.getElementById('inputCustomPrice').value) || (cart[activeCustomIndex]?.price || 0);

        previewQty = newQty;
        previewPrice = unitPrice;
        previewTotal = Math.round(newQty * unitPrice);
        isNominalMode = false;

        document.getElementById('inputCustomNominal').value = previewTotal;
        updateCustomPreview();
    }

    function setQuickNominal(amount) {
        document.getElementById('inputCustomNominal').value = amount;
        calculateFromNominal();
    }

    function setQuickQty(qty) {
        document.getElementById('inputCustomQty').value = qty;
        calculateFromQty();
    }

    function resetToOriginalPrice() {
        if (!cart[activeCustomIndex]) return;
        const orig = cart[activeCustomIndex].original_price || cart[activeCustomIndex].price;
        document.getElementById('inputCustomPrice').value = orig;
        if (activeCustomMode === 'nominal') {
            calculateFromNominal();
        } else {
            calculateFromPrice();
        }
    }

    function updateCustomPreview() {
        const item = cart[activeCustomIndex];
        const unit = item ? (item.unit || 'pcs') : 'pcs';

        document.getElementById('previewCustomQty').innerText = `${formatQty(previewQty)} ${unit}`;
        document.getElementById('previewCustomPrice').innerText = `Rp ${new Intl.NumberFormat('id-ID').format(previewPrice)} / ${unit}`;
        document.getElementById('previewCustomTotal').innerText = `Rp ${new Intl.NumberFormat('id-ID').format(previewTotal)}`;
    }

    function applyCustomItemChanges() {
        if (activeCustomIndex < 0 || !cart[activeCustomIndex]) return;

        const item = cart[activeCustomIndex];

        if (previewQty <= 0) {
            alert('Kuantitas produk harus lebih besar dari 0.');
            return;
        }

        if (previewQty > item.stock) {
            alert(`Kuantitas (${formatQty(previewQty)} ${item.unit}) melebihi stok yang tersedia (${formatStockDisplay(item.stock)} ${item.unit})!`);
            return;
        }

        item.qty = previewQty;
        item.price = previewPrice;
        item.subtotal = previewTotal;
        item.is_custom_subtotal = isNominalMode;
        item.is_custom = (item.price !== item.original_price) || item.is_custom_subtotal || (!Number.isInteger(item.qty));

        customItemModal.hide();
        renderCart();
        showPosToast(`Item "${item.name}" disesuaikan: ${formatQty(item.qty)} ${item.unit} = Rp ${new Intl.NumberFormat('id-ID').format(item.subtotal)}`);
    }

    function resetCustomItemToDefault() {
        if (activeCustomIndex < 0 || !cart[activeCustomIndex]) return;

        const item = cart[activeCustomIndex];
        item.price = item.original_price || item.price;
        item.qty = 1;
        item.subtotal = item.price;
        item.is_custom = false;
        item.is_custom_subtotal = false;

        customItemModal.hide();
        renderCart();
        showPosToast(`Item "${item.name}" dikembalikan ke harga dan jumlah standar.`);
    }

    // Initialize Hold badge count on page load
    updateHoldBadge();
</script>
@endpush
