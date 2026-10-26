<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CashShiftController;
use App\Http\Controllers\CashTransactionController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // POS / Terminal Kasir
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('/search-product', [PosController::class, 'searchProduct'])->name('search');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
    });

    // Shift Kasir
    Route::prefix('cash-shifts')->name('cash-shifts.')->group(function () {
        Route::get('/', [CashShiftController::class, 'index'])->name('index');
        Route::get('/current', [CashShiftController::class, 'current'])->name('current');
        Route::post('/open', [CashShiftController::class, 'openShift'])->name('open');
        Route::post('/close', [CashShiftController::class, 'closeShift'])->name('close');
        Route::get('/{cashShift}', [CashShiftController::class, 'show'])->name('show');
    });

    Route::post('/cash-transactions', [CashTransactionController::class, 'store'])->name('cash-transactions.store');

    // Riwayat Penjualan & Cetak Struk
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('index');
        Route::get('/{sale}', [SaleController::class, 'show'])->name('show');
        Route::get('/{sale}/receipt', [SaleController::class, 'printReceipt'])->name('receipt');
        Route::get('/{sale}/pdf', [SaleController::class, 'downloadPdf'])->name('pdf');
    });

    // Inventori & Produk
    Route::get('/products/export/excel', [ProductController::class, 'exportExcel'])->name('products.export.excel');
    Route::resource('products', ProductController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('units', UnitController::class);

    // Manajemen Stok
    Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::get('/stocks/{product}/card', [StockController::class, 'stockCard'])->name('stocks.card');
    Route::resource('stock-opnames', StockOpnameController::class);

    // Pembelian (PO) & Supplier
    Route::resource('purchases', PurchaseController::class);
    Route::resource('suppliers', SupplierController::class);

    // Pelanggan & Retur
    Route::resource('customers', CustomerController::class);
    Route::resource('returns/sales', SaleReturnController::class)->names('returns.sales');
    Route::resource('returns/purchases', PurchaseReturnController::class)->names('returns.purchases');

    // Laporan & Rekapitulasi
    Route::prefix('reports')->name('reports.')->group(function () {
        // Laporan Utama Eksisting
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/sales/pdf', [ReportController::class, 'exportSalesPdf'])->name('sales.pdf');
        Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases');
        Route::get('/purchases/pdf', [ReportController::class, 'exportPurchasesPdf'])->name('purchases.pdf');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/profit-loss/pdf', [ReportController::class, 'exportProfitLossPdf'])->name('profit-loss.pdf');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/stock/pdf', [ReportController::class, 'exportStockPdf'])->name('stock.pdf');

        // 1. Laporan Stok (Penyusutan)
        Route::get('/stock-shrinkage', [ReportController::class, 'stockShrinkage'])->name('stock-shrinkage');
        Route::get('/stock-shrinkage/pdf', [ReportController::class, 'exportStockShrinkagePdf'])->name('stock-shrinkage.pdf');

        // 2. Laporan Penjualan per Item Produk
        Route::get('/sales-by-product', [ReportController::class, 'salesByProduct'])->name('sales-by-product');
        Route::get('/sales-by-product/pdf', [ReportController::class, 'exportSalesByProductPdf'])->name('sales-by-product.pdf');

        // 3. Laporan Penjualan per Omset (Tren & Rekap Omset)
        Route::get('/sales-by-turnover', [ReportController::class, 'salesByTurnover'])->name('sales-by-turnover');
        Route::get('/sales-by-turnover/pdf', [ReportController::class, 'exportSalesByTurnoverPdf'])->name('sales-by-turnover.pdf');

        // 4. Laporan Penjualan per Customer
        Route::get('/sales-by-customer', [ReportController::class, 'salesByCustomer'])->name('sales-by-customer');
        Route::get('/sales-by-customer/pdf', [ReportController::class, 'exportSalesByCustomerPdf'])->name('sales-by-customer.pdf');

        // 5. Laporan Keuangan Cash & TF (Kas & Bank)
        Route::get('/finance-cash-bank', [ReportController::class, 'financeCashBank'])->name('finance-cash-bank');
        Route::get('/finance-cash-bank/pdf', [ReportController::class, 'exportFinanceCashBankPdf'])->name('finance-cash-bank.pdf');

        // 6. Laporan Penjualan per Kasir
        Route::get('/sales-by-cashier', [ReportController::class, 'salesByCashier'])->name('sales-by-cashier');
        Route::get('/sales-by-cashier/pdf', [ReportController::class, 'exportSalesByCashierPdf'])->name('sales-by-cashier.pdf');
    });

    // Pengaturan Toko & Manajemen Pengguna (Admin & Super Admin)
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::resource('users', UserController::class);
});
