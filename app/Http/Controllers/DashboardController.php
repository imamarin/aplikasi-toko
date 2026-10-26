<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index(Request $request)
    {
        $period = $request->input('period', 'today');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');

        [$startDate, $endDate] = $this->reportService->resolveDateRange($period, $customStart, $customEnd);

        // Period Sales Metrics
        $salesQuery = Sale::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed');

        $totalSalesAmount = (float) $salesQuery->sum('grand_total');
        $totalSalesCount  = (int) $salesQuery->count();

        // Period Purchases Metrics
        $purchasesQuery = Purchase::whereBetween('purchase_date', [$startDate, $endDate]);
        $totalPurchasesAmount = (float) $purchasesQuery->sum('grand_total');
        $totalPurchasesCount  = (int) $purchasesQuery->count();

        // Profit & Loss for period
        $profitLoss = $this->reportService->getProfitLossReport($startDate, $endDate);

        // Overall Master counts
        $totalProducts = Product::count();
        $lowStockCount = Product::lowStock()->count();
        $totalCustomers = Customer::count();
        $totalSuppliers = Supplier::count();

        // Recent 7 days chart data
        $chartDates = [];
        $chartSales = [];
        $chartPurchases = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dayStr = $day->translatedFormat('d M');
            $chartDates[] = $dayStr;

            $chartSales[] = (float) Sale::whereDate('transaction_date', $day)
                ->where('status', 'completed')
                ->sum('grand_total');

            $chartPurchases[] = (float) Purchase::whereDate('purchase_date', $day)
                ->sum('grand_total');
        }

        // Top 5 Best Sellers
        $bestSellers = $this->reportService->getBestSellers($startDate, $endDate, 5);

        // Recent 10 Transactions
        $recentSales = Sale::with(['user', 'customer'])
            ->latest('transaction_date')
            ->limit(10)
            ->get();

        // Low stock products alert list
        $lowStockProducts = Product::with(['category', 'unit'])
            ->lowStock()
            ->orderBy('stock', 'asc')
            ->limit(8)
            ->get();

        return view('dashboard.index', compact(
            'period',
            'startDate',
            'endDate',
            'totalSalesAmount',
            'totalSalesCount',
            'totalPurchasesAmount',
            'totalPurchasesCount',
            'profitLoss',
            'totalProducts',
            'lowStockCount',
            'totalCustomers',
            'totalSuppliers',
            'chartDates',
            'chartSales',
            'chartPurchases',
            'bestSellers',
            'recentSales',
            'lowStockProducts'
        ));
    }
}
