<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function sales(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $userId = $request->filled('user_id') ? (int) $request->input('user_id') : null;
        $customerId = $request->filled('customer_id') ? (int) $request->input('customer_id') : null;

        $reportData = $this->reportService->getSalesReport($start, $end, $userId, $customerId);

        $cashiers = User::whereHas('role', function ($q) {
            $q->whereIn('slug', ['kasir', 'admin', 'super-admin']);
        })->get();

        $customers = Customer::orderBy('name')->get();

        return view('reports.sales', [
            'sales' => $reportData['sales'],
            'summary' => $reportData['summary'],
            'period' => $period,
            'startDate' => $start,
            'endDate' => $end,
            'cashiers' => $cashiers,
            'customers' => $customers,
        ]);
    }

    public function exportSalesPdf(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $userId = $request->filled('user_id') ? (int) $request->input('user_id') : null;
        $customerId = $request->filled('customer_id') ? (int) $request->input('customer_id') : null;

        $reportData = $this->reportService->getSalesReport($start, $end, $userId, $customerId);

        $settings = [
            'store_name' => Setting::get('store_name', 'Kasir POS'),
            'store_address' => Setting::get('store_address', 'Jl. Contoh Toko'),
            'store_phone' => Setting::get('store_phone', '-'),
            'store_email' => Setting::get('store_email', '-'),
        ];

        $pdf = Pdf::loadView('reports.sales_pdf', [
            'sales' => $reportData['sales'],
            'summary' => $reportData['summary'],
            'startDate' => $start,
            'endDate' => $end,
            'settings' => $settings,
        ])->setPaper('a4', 'landscape');

        $fileName = "Laporan-Penjualan-{$start->format('Ymd')}-{$end->format('Ymd')}.pdf";

        return $request->has('stream') ? $pdf->stream($fileName) : $pdf->download($fileName);
    }

    public function purchases(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $query = Purchase::with(['supplier', 'user'])
            ->whereBetween('purchase_date', [$start, $end]);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->input('supplier_id'));
        }

        $purchases = $query->latest('purchase_date')->get();
        $totalPurchases = $purchases->sum('grand_total');
        $totalPaid = $purchases->sum('paid_amount');

        $suppliers = Supplier::orderBy('name')->get();

        return view('reports.purchases', compact('purchases', 'totalPurchases', 'totalPaid', 'period', 'start', 'end', 'suppliers'));
    }

    public function exportPurchasesPdf(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $query = Purchase::with(['supplier', 'user'])
            ->whereBetween('purchase_date', [$start, $end]);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->input('supplier_id'));
        }

        $purchases = $query->latest('purchase_date')->get();
        $totalPurchases = $purchases->sum('grand_total');
        $totalPaid = $purchases->sum('paid_amount');

        $settings = [
            'store_name' => Setting::get('store_name', 'Kasir POS'),
            'store_address' => Setting::get('store_address', 'Jl. Contoh Toko'),
            'store_phone' => Setting::get('store_phone', '-'),
            'store_email' => Setting::get('store_email', '-'),
        ];

        $pdf = Pdf::loadView('reports.purchases_pdf', [
            'purchases' => $purchases,
            'totalPurchases' => $totalPurchases,
            'totalPaid' => $totalPaid,
            'startDate' => $start,
            'endDate' => $end,
            'settings' => $settings,
        ])->setPaper('a4', 'landscape');

        $fileName = "Laporan-Pembelian-{$start->format('Ymd')}-{$end->format('Ymd')}.pdf";

        return $request->has('stream') ? $pdf->stream($fileName) : $pdf->download($fileName);
    }

    public function profitLoss(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $profitLoss = $this->reportService->getProfitLossReport($start, $end);
        $bestSellers = $this->reportService->getBestSellers($start, $end, 10);

        return view('reports.profit_loss', compact('profitLoss', 'bestSellers', 'period', 'start', 'end'));
    }

    public function exportProfitLossPdf(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $profitLoss = $this->reportService->getProfitLossReport($start, $end);
        $bestSellers = $this->reportService->getBestSellers($start, $end, 10);

        $settings = [
            'store_name' => Setting::get('store_name', 'Kasir POS'),
            'store_address' => Setting::get('store_address', 'Jl. Contoh Toko'),
            'store_phone' => Setting::get('store_phone', '-'),
            'store_email' => Setting::get('store_email', '-'),
        ];

        $pdf = Pdf::loadView('reports.profit_loss_pdf', [
            'profitLoss' => $profitLoss,
            'bestSellers' => $bestSellers,
            'startDate' => $start,
            'endDate' => $end,
            'settings' => $settings,
        ])->setPaper('a4', 'portrait');

        $fileName = "Laporan-Laba-Rugi-{$start->format('Ymd')}-{$end->format('Ymd')}.pdf";

        return $request->has('stream') ? $pdf->stream($fileName) : $pdf->download($fileName);
    }

    public function stock(Request $request)
    {
        $query = Product::with(['category', 'unit'])->orderBy('name');

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        $products = $query->get();

        $totalInventoryCogs = 0;
        $totalInventorySalesValue = 0;

        foreach ($products as $p) {
            $totalInventoryCogs += ($p->cost_price * $p->stock);
            $totalInventorySalesValue += ($p->selling_price * $p->stock);
        }

        $categories = Category::where('status', true)->orderBy('name')->get();

        return view('reports.stock', compact('products', 'totalInventoryCogs', 'totalInventorySalesValue', 'categories'));
    }

    public function exportStockPdf(Request $request)
    {
        $query = Product::with(['category', 'unit'])->orderBy('name');

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        $products = $query->get();

        $totalInventoryCogs = 0;
        $totalInventorySalesValue = 0;

        foreach ($products as $p) {
            $totalInventoryCogs += ($p->cost_price * $p->stock);
            $totalInventorySalesValue += ($p->selling_price * $p->stock);
        }

        $settings = $this->getStoreSettings();

        $pdf = Pdf::loadView('reports.stock_pdf', [
            'products' => $products,
            'totalInventoryCogs' => $totalInventoryCogs,
            'totalInventorySalesValue' => $totalInventorySalesValue,
            'settings' => $settings,
        ])->setPaper('a4', 'landscape');

        $fileName = "Laporan-Stok-Persediaan-" . now()->format('Ymd') . ".pdf";

        return $request->has('stream') ? $pdf->stream($fileName) : $pdf->download($fileName);
    }

    /**
     * 1. Laporan Stok (Penyusutan)
     */
    public function stockShrinkage(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $reason = $request->input('reason');

        $data = $this->reportService->getStockShrinkageReport($start, $end, $categoryId, $reason);
        $categories = Category::where('status', true)->orderBy('name')->get();

        return view('reports.stock_shrinkage', [
            'records' => $data['records'],
            'summary' => $data['summary'],
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'categories' => $categories,
        ]);
    }

    public function exportStockShrinkagePdf(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $reason = $request->input('reason');

        $data = $this->reportService->getStockShrinkageReport($start, $end, $categoryId, $reason);
        $settings = $this->getStoreSettings();

        $pdf = Pdf::loadView('reports.stock_shrinkage_pdf', [
            'records' => $data['records'],
            'summary' => $data['summary'],
            'startDate' => $start,
            'endDate' => $end,
            'settings' => $settings,
        ])->setPaper('a4', 'landscape');

        $fileName = "Laporan-Penyusutan-Stok-{$start->format('Ymd')}-{$end->format('Ymd')}.pdf";

        return $request->has('stream') ? $pdf->stream($fileName) : $pdf->download($fileName);
    }

    /**
     * 2. Laporan Penjualan per Item Produk
     */
    public function salesByProduct(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'qty_desc');

        $data = $this->reportService->getSalesByProductReport($start, $end, $categoryId, $search, $sortBy);
        $categories = Category::where('status', true)->orderBy('name')->get();

        return view('reports.sales_by_product', [
            'products' => $data['products'],
            'summary' => $data['summary'],
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'categories' => $categories,
            'search' => $search,
            'sortBy' => $sortBy,
        ]);
    }

    public function exportSalesByProductPdf(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'qty_desc');

        $data = $this->reportService->getSalesByProductReport($start, $end, $categoryId, $search, $sortBy);
        $settings = $this->getStoreSettings();

        $pdf = Pdf::loadView('reports.sales_by_product_pdf', [
            'products' => $data['products'],
            'summary' => $data['summary'],
            'startDate' => $start,
            'endDate' => $end,
            'settings' => $settings,
        ])->setPaper('a4', 'landscape');

        $fileName = "Laporan-Penjualan-per-Produk-{$start->format('Ymd')}-{$end->format('Ymd')}.pdf";

        return $request->has('stream') ? $pdf->stream($fileName) : $pdf->download($fileName);
    }

    /**
     * 3. Laporan Penjualan per Omset (Tren & Rekap Omset)
     */
    public function salesByTurnover(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $data = $this->reportService->getSalesByTurnoverReport($start, $end);

        return view('reports.sales_by_turnover', [
            'turnovers' => $data['turnovers'],
            'summary' => $data['summary'],
            'period' => $period,
            'start' => $start,
            'end' => $end,
        ]);
    }

    public function exportSalesByTurnoverPdf(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $data = $this->reportService->getSalesByTurnoverReport($start, $end);
        $settings = $this->getStoreSettings();

        $pdf = Pdf::loadView('reports.sales_by_turnover_pdf', [
            'turnovers' => $data['turnovers'],
            'summary' => $data['summary'],
            'startDate' => $start,
            'endDate' => $end,
            'settings' => $settings,
        ])->setPaper('a4', 'landscape');

        $fileName = "Laporan-Penjualan-per-Omset-{$start->format('Ymd')}-{$end->format('Ymd')}.pdf";

        return $request->has('stream') ? $pdf->stream($fileName) : $pdf->download($fileName);
    }

    /**
     * 4. Laporan Penjualan per Customer
     */
    public function salesByCustomer(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $search = $request->input('search');
        $customerId = $request->input('customer_id');
        $sortBy = $request->input('sort_by', 'total_desc');

        $data = $this->reportService->getSalesByCustomerReport($start, $end, $search, $sortBy, $customerId);

        $allCustomers = Customer::select('id', 'code', 'name', 'phone', 'member_level')
            ->orderBy('name')
            ->get();

        return view('reports.sales_by_customer', [
            'customers' => $data['customers'],
            'summary' => $data['summary'],
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'search' => $search,
            'customerId' => $customerId,
            'allCustomers' => $allCustomers,
            'sortBy' => $sortBy,
        ]);
    }

    public function exportSalesByCustomerPdf(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $search = $request->input('search');
        $customerId = $request->input('customer_id');
        $sortBy = $request->input('sort_by', 'total_desc');

        $data = $this->reportService->getSalesByCustomerReport($start, $end, $search, $sortBy, $customerId);
        $settings = $this->getStoreSettings();

        $selectedCustomerName = null;
        if ($customerId === 'umum' || $customerId === 'non-member') {
            $selectedCustomerName = 'Pelanggan Umum (Non-Member)';
        } elseif ($customerId) {
            $c = Customer::find($customerId);
            if ($c) {
                $selectedCustomerName = "{$c->name} ({$c->code})";
            }
        } elseif ($search) {
            $selectedCustomerName = "Pencarian: \"{$search}\"";
        }

        $pdf = Pdf::loadView('reports.sales_by_customer_pdf', [
            'customers' => $data['customers'],
            'summary' => $data['summary'],
            'startDate' => $start,
            'endDate' => $end,
            'settings' => $settings,
            'selectedCustomerName' => $selectedCustomerName,
        ])->setPaper('a4', 'landscape');

        $fileName = "Laporan-Penjualan-per-Customer-{$start->format('Ymd')}-{$end->format('Ymd')}.pdf";

        return $request->has('stream') ? $pdf->stream($fileName) : $pdf->download($fileName);
    }

    /**
     * 5. Laporan Keuangan Cash & TF (Kas & Bank)
     */
    public function financeCashBank(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $data = $this->reportService->getCashAndBankFinanceReport($start, $end);

        return view('reports.finance_cash_bank', [
            'salesByMethod' => $data['sales_by_method'],
            'cashLogs' => $data['cash_logs'],
            'summary' => $data['summary'],
            'period' => $period,
            'start' => $start,
            'end' => $end,
        ]);
    }

    public function exportFinanceCashBankPdf(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $data = $this->reportService->getCashAndBankFinanceReport($start, $end);
        $settings = $this->getStoreSettings();

        $pdf = Pdf::loadView('reports.finance_cash_bank_pdf', [
            'salesByMethod' => $data['sales_by_method'],
            'cashLogs' => $data['cash_logs'],
            'summary' => $data['summary'],
            'startDate' => $start,
            'endDate' => $end,
            'settings' => $settings,
        ])->setPaper('a4', 'portrait');

        $fileName = "Laporan-Keuangan-Cash-TF-{$start->format('Ymd')}-{$end->format('Ymd')}.pdf";

        return $request->has('stream') ? $pdf->stream($fileName) : $pdf->download($fileName);
    }

    /**
     * 6. Laporan Penjualan per Kasir
     */
    public function salesByCashier(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $userId = $request->filled('user_id') ? (int) $request->input('user_id') : null;

        $data = $this->reportService->getSalesByCashierReport($start, $end, $userId);

        $cashiers = User::whereHas('role', function ($q) {
            $q->whereIn('slug', ['kasir', 'admin', 'super-admin']);
        })->get();

        return view('reports.sales_by_cashier', [
            'cashiers' => $data['cashiers'],
            'summary' => $data['summary'],
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'cashierList' => $cashiers,
            'selectedUserId' => $userId,
        ]);
    }

    public function exportSalesByCashierPdf(Request $request)
    {
        $period = $request->input('period', 'this_month');
        [$start, $end] = $this->reportService->resolveDateRange($period, $request->input('start_date'), $request->input('end_date'));

        $userId = $request->filled('user_id') ? (int) $request->input('user_id') : null;

        $data = $this->reportService->getSalesByCashierReport($start, $end, $userId);
        $settings = $this->getStoreSettings();

        $pdf = Pdf::loadView('reports.sales_by_cashier_pdf', [
            'cashiers' => $data['cashiers'],
            'summary' => $data['summary'],
            'startDate' => $start,
            'endDate' => $end,
            'settings' => $settings,
        ])->setPaper('a4', 'landscape');

        $fileName = "Laporan-Penjualan-per-Kasir-{$start->format('Ymd')}-{$end->format('Ymd')}.pdf";

        return $request->has('stream') ? $pdf->stream($fileName) : $pdf->download($fileName);
    }

    private function getStoreSettings(): array
    {
        return [
            'store_name' => Setting::get('store_name', 'Kasir POS'),
            'store_address' => Setting::get('store_address', 'Jl. Contoh Toko'),
            'store_phone' => Setting::get('store_phone', '-'),
            'store_email' => Setting::get('store_email', '-'),
        ];
    }
}
