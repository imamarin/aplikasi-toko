<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Resolve date range from string preset or custom inputs.
     */
    public function resolveDateRange(?string $period = 'today', ?string $customStart = null, ?string $customEnd = null): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'yesterday':
                $start = Carbon::yesterday()->startOfDay();
                $end = Carbon::yesterday()->endOfDay();
                break;
            case 'this_week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;
            case 'this_month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;
            case 'this_year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
            case 'custom':
                $start = $customStart ? Carbon::parse($customStart)->startOfDay() : $now->copy()->startOfDay();
                $end = $customEnd ? Carbon::parse($customEnd)->endOfDay() : $now->copy()->endOfDay();
                break;
            case 'today':
            default:
                $start = Carbon::today()->startOfDay();
                $end = Carbon::today()->endOfDay();
                break;
        }

        return [$start, $end];
    }

    /**
     * Get Sales summary and records.
     */
    public function getSalesReport(Carbon $start, Carbon $end, $userId = null, $customerId = null)
    {
        $userId = !empty($userId) ? (int) $userId : null;
        $customerId = !empty($customerId) ? (int) $customerId : null;

        $query = Sale::with(['user', 'customer', 'details'])
            ->whereBetween('transaction_date', [$start, $end])
            ->where('status', 'completed');

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $sales = $query->latest('transaction_date')->get();

        $totalSubtotal = $sales->sum('subtotal');
        $totalDiscount = $sales->sum('discount_amount');
        $totalTax = $sales->sum('tax_amount');
        $totalShipping = $sales->sum('shipping_cost');
        $totalGrandTotal = $sales->sum('grand_total');
        $totalTransactions = $sales->count();
        $totalProductSales = $totalSubtotal - $totalDiscount;

        return [
            'sales' => $sales,
            'summary' => [
                'total_subtotal' => $totalSubtotal,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'total_shipping' => $totalShipping,
                'total_product_sales' => $totalProductSales,
                'total_grand_total' => $totalGrandTotal,
                'total_transactions' => $totalTransactions,
            ],
        ];
    }

    /**
     * Get Profit and Loss report.
     */
    public function getProfitLossReport(Carbon $start, Carbon $end): array
    {
        $sales = Sale::with('details')
            ->whereBetween('transaction_date', [$start, $end])
            ->where('status', 'completed')
            ->get();

        $totalSubtotal = (float) $sales->sum('subtotal');
        $totalDiscounts = (float) $sales->sum('discount_amount');
        $totalShippingCost = (float) $sales->sum('shipping_cost');
        $totalGrandTotal = (float) $sales->sum('grand_total');

        // Total Product Net Revenue (Penjualan Produk Murni - tidak termasuk ongkir)
        $totalProductRevenue = $totalSubtotal - $totalDiscounts;

        // Total Cost of Goods Sold (HPP)
        $totalCogs = 0;
        foreach ($sales as $sale) {
            foreach ($sale->details as $detail) {
                $totalCogs += ((float) $detail->cost_price * (int) $detail->qty);
            }
        }

        // Gross Profit from products = Product Revenue - HPP
        $grossProfit = $totalProductRevenue - $totalCogs;

        // Operating Expenses (Cash out transactions categorized as expense/other)
        $expenses = (float) CashTransaction::whereBetween('transaction_date', [$start, $end])
            ->where('type', 'out')
            ->whereIn('category', ['expense', 'other'])
            ->sum('amount');

        // Net Profit = Gross Profit - Operating Expenses
        $netProfit = $grossProfit - $expenses;

        return [
            'total_revenue' => $totalProductRevenue,
            'total_product_revenue' => $totalProductRevenue,
            'total_shipping' => $totalShippingCost,
            'total_grand_total' => $totalGrandTotal,
            'total_subtotal' => $totalSubtotal,
            'total_discounts' => $totalDiscounts,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'expenses' => $expenses,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Get Best Selling Products.
     */
    public function getBestSellers(Carbon $start, Carbon $end, int $limit = 10)
    {
        return SaleDetail::select(
                'product_id',
                'product_name',
                DB::raw('SUM(qty) as total_qty'),
                DB::raw('SUM(subtotal) as total_sales'),
                DB::raw('SUM(qty * cost_price) as total_cogs'),
                DB::raw('SUM(subtotal - (qty * cost_price)) as total_profit')
            )
            ->whereHas('sale', function ($query) use ($start, $end) {
                $query->whereBetween('transaction_date', [$start, $end])
                      ->where('status', 'completed');
            })
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();
    }

    /**
     * 1. Get Stock Shrinkage / Loss Report (Penyusutan Stok).
     */
    public function getStockShrinkageReport(Carbon $start, Carbon $end, ?int $categoryId = null, ?string $reason = null): array
    {
        $query = \App\Models\StockMovement::with(['product.category', 'product.unit', 'user'])
            ->whereIn('type', ['adjustment', 'opname'])
            ->where('qty_out', '>', 0)
            ->whereBetween('created_at', [$start, $end]);

        if ($categoryId) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        if ($reason) {
            $query->where('notes', 'like', "%{$reason}%");
        }

        $records = $query->latest('created_at')->get();

        $totalItems = 0;
        $totalLoss = 0;

        foreach ($records as $item) {
            $costPrice = (float) ($item->product?->cost_price ?? 0);
            $loss = $item->qty_out * $costPrice;
            $item->cost_price = $costPrice;
            $item->total_loss = $loss;
            $totalItems += $item->qty_out;
            $totalLoss += $loss;
        }

        return [
            'records' => $records,
            'summary' => [
                'total_events' => $records->count(),
                'total_items' => $totalItems,
                'total_loss' => $totalLoss,
            ],
        ];
    }

    /**
     * 2. Get Sales By Product Item Report (Laporan Penjualan per Item Produk).
     */
    public function getSalesByProductReport(Carbon $start, Carbon $end, ?int $categoryId = null, ?string $search = null, string $sortBy = 'qty_desc'): array
    {
        $query = SaleDetail::select(
                'sale_details.product_id',
                'sale_details.product_name',
                DB::raw('SUM(sale_details.qty) as total_qty'),
                DB::raw('SUM(sale_details.subtotal) as total_subtotal'),
                DB::raw('SUM(sale_details.discount_amount) as total_discount'),
                DB::raw('SUM(sale_details.subtotal - sale_details.discount_amount) as total_sales'),
                DB::raw('SUM(sale_details.qty * sale_details.cost_price) as total_cogs'),
                DB::raw('SUM((sale_details.subtotal - sale_details.discount_amount) - (sale_details.qty * sale_details.cost_price)) as total_profit')
            )
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_details.product_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.transaction_date', [$start, $end]);

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sale_details.product_name', 'like', "%{$search}%")
                  ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }

        $query->groupBy('sale_details.product_id', 'sale_details.product_name');

        if ($sortBy === 'sales_desc') {
            $query->orderByDesc('total_sales');
        } elseif ($sortBy === 'profit_desc') {
            $query->orderByDesc('total_profit');
        } else {
            $query->orderByDesc('total_qty');
        }

        $products = $query->get();

        $productModels = Product::with(['category', 'unit'])->whereIn('id', $products->pluck('product_id')->filter())->get()->keyBy('id');

        $overallQty = 0;
        $overallSales = 0;
        $overallCogs = 0;
        $overallProfit = 0;

        foreach ($products as $p) {
            $prodModel = $productModels->get($p->product_id);
            $p->sku = $prodModel?->sku ?? '-';
            $p->category_name = $prodModel?->category?->name ?? '-';
            $p->unit_name = $prodModel?->unit?->short_name ?? 'pcs';
            $p->margin_percentage = $p->total_sales > 0 ? ($p->total_profit / $p->total_sales) * 100 : 0;

            $overallQty += (int) $p->total_qty;
            $overallSales += (float) $p->total_sales;
            $overallCogs += (float) $p->total_cogs;
            $overallProfit += (float) $p->total_profit;
        }

        $overallMargin = $overallSales > 0 ? ($overallProfit / $overallSales) * 100 : 0;

        return [
            'products' => $products,
            'summary' => [
                'total_products_count' => $products->count(),
                'overall_qty' => $overallQty,
                'overall_sales' => $overallSales,
                'overall_cogs' => $overallCogs,
                'overall_profit' => $overallProfit,
                'overall_margin' => $overallMargin,
            ],
        ];
    }

    /**
     * 3. Get Sales By Turnover Report (Laporan Penjualan per Omset).
     */
    public function getSalesByTurnoverReport(Carbon $start, Carbon $end): array
    {
        $salesByDate = Sale::select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(id) as total_transactions'),
                DB::raw('SUM(subtotal) as total_subtotal'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(shipping_cost) as total_shipping'),
                DB::raw('SUM(grand_total) as total_grand_total')
            )
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$start, $end])
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->orderBy('date', 'desc')
            ->get();

        $totalTransactions = (int) $salesByDate->sum('total_transactions');
        $totalSubtotal = (float) $salesByDate->sum('total_subtotal');
        $totalDiscount = (float) $salesByDate->sum('total_discount');
        $totalTax = (float) $salesByDate->sum('total_tax');
        $totalShipping = (float) $salesByDate->sum('total_shipping');
        $totalGrandTotal = (float) $salesByDate->sum('total_grand_total');

        $daysCount = max(1, $salesByDate->count());
        $avgDailyTurnover = $totalGrandTotal / $daysCount;
        $avgBasketSize = $totalTransactions > 0 ? ($totalGrandTotal / $totalTransactions) : 0;

        return [
            'turnovers' => $salesByDate,
            'summary' => [
                'total_transactions' => $totalTransactions,
                'total_subtotal' => $totalSubtotal,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'total_shipping' => $totalShipping,
                'total_product_sales' => $totalSubtotal - $totalDiscount,
                'total_grand_total' => $totalGrandTotal,
                'active_days' => $salesByDate->count(),
                'avg_daily_turnover' => $avgDailyTurnover,
                'avg_basket_size' => $avgBasketSize,
            ],
        ];
    }

    /**
     * 4. Get Sales By Customer Report (Laporan Penjualan per Customer).
     */
    public function getSalesByCustomerReport(Carbon $start, Carbon $end, ?string $search = null, string $sortBy = 'total_desc', mixed $customerId = null): array
    {
        $query = Sale::select(
                'customer_id',
                DB::raw('COUNT(id) as total_transactions'),
                DB::raw('SUM(subtotal) as total_subtotal'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(grand_total) as total_spent'),
                DB::raw('MAX(transaction_date) as last_transaction_date')
            )
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$start, $end]);

        if ($customerId !== null && $customerId !== '') {
            if ($customerId === 'umum' || $customerId === 'non-member') {
                $query->whereNull('customer_id');
            } elseif (is_numeric($customerId)) {
                $query->where('customer_id', $customerId);
            }
        } elseif ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
                if (stripos('umum', $search) !== false || stripos('non-member', $search) !== false) {
                    $q->orWhereNull('customer_id');
                }
            });
        }

        $query->groupBy('customer_id');

        if ($sortBy === 'tx_desc') {
            $query->orderByDesc('total_transactions');
        } else {
            $query->orderByDesc('total_spent');
        }

        $customerSales = $query->get();

        $customerModels = \App\Models\Customer::whereIn('id', $customerSales->pluck('customer_id')->filter())->get()->keyBy('id');

        $memberSpent = 0;
        $memberTx = 0;
        $generalSpent = 0;
        $generalTx = 0;

        foreach ($customerSales as $item) {
            if ($item->customer_id && $cust = $customerModels->get($item->customer_id)) {
                $item->customer_name = $cust->name;
                $item->customer_code = $cust->code ?? '-';
                $item->customer_phone = $cust->phone ?? '-';
                $item->is_member = true;
                $memberSpent += (float) $item->total_spent;
                $memberTx += (int) $item->total_transactions;
            } else {
                $item->customer_name = 'Pelanggan Umum (Non-Member)';
                $item->customer_code = '-';
                $item->customer_phone = '-';
                $item->is_member = false;
                $generalSpent += (float) $item->total_spent;
                $generalTx += (int) $item->total_transactions;
            }

            $item->avg_per_transaction = $item->total_transactions > 0 ? ($item->total_spent / $item->total_transactions) : 0;
            $item->last_transaction_date = $item->last_transaction_date ? Carbon::parse($item->last_transaction_date) : null;
        }

        $totalSpent = $memberSpent + $generalSpent;
        $totalTx = $memberTx + $generalTx;

        return [
            'customers' => $customerSales,
            'summary' => [
                'total_customers_count' => $customerSales->count(),
                'total_spent' => $totalSpent,
                'total_transactions' => $totalTx,
                'member_spent' => $memberSpent,
                'member_transactions' => $memberTx,
                'general_spent' => $generalSpent,
                'general_transactions' => $generalTx,
                'member_contribution_percentage' => $totalSpent > 0 ? ($memberSpent / $totalSpent) * 100 : 0,
            ],
        ];
    }

    /**
     * 5. Get Cash & Bank (TF) Finance Report (Laporan Keuangan Cash & TF).
     */
    public function getCashAndBankFinanceReport(Carbon $start, Carbon $end): array
    {
        // 1. Sales grouped by payment method
        $salesByMethod = Sale::select(
                'payment_method',
                DB::raw('COUNT(id) as total_transactions'),
                DB::raw('SUM(grand_total) as total_amount')
            )
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$start, $end])
            ->groupBy('payment_method')
            ->get();

        $cashSales = (float) ($salesByMethod->where('payment_method', 'cash')->first()?->total_amount ?? 0);
        $transferSales = (float) ($salesByMethod->where('payment_method', 'transfer')->first()?->total_amount ?? 0);
        $qrisSales = (float) ($salesByMethod->where('payment_method', 'qris')->first()?->total_amount ?? 0);
        $otherNonCashSales = (float) ($salesByMethod->whereNotIn('payment_method', ['cash', 'transfer', 'qris'])->sum('total_amount'));
        $totalNonCashSales = $transferSales + $qrisSales + $otherNonCashSales;
        $totalSales = $cashSales + $totalNonCashSales;

        // 2. Cash Drawer in/out transactions
        $cashIn = (float) CashTransaction::whereBetween('transaction_date', [$start, $end])
            ->where('type', 'in')
            ->sum('amount');

        $cashOut = (float) CashTransaction::whereBetween('transaction_date', [$start, $end])
            ->where('type', 'out')
            ->sum('amount');

        // 3. Cash returns refunds
        $cashRefunds = (float) SaleReturn::whereBetween('created_at', [$start, $end])
            ->where('refund_method', 'cash')
            ->sum('total_refund');

        $netCashDrawer = $cashSales + $cashIn - $cashOut - $cashRefunds;

        // 4. Cash Transactions log list
        $cashLogs = CashTransaction::with('user')
            ->whereBetween('transaction_date', [$start, $end])
            ->latest('transaction_date')
            ->get();

        return [
            'sales_by_method' => $salesByMethod,
            'cash_logs' => $cashLogs,
            'summary' => [
                'cash_sales' => $cashSales,
                'transfer_sales' => $transferSales,
                'qris_sales' => $qrisSales,
                'other_non_cash_sales' => $otherNonCashSales,
                'total_non_cash_sales' => $totalNonCashSales,
                'total_sales' => $totalSales,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'cash_refunds' => $cashRefunds,
                'net_cash_drawer' => $netCashDrawer,
                'total_store_funds_received' => $totalSales + $cashIn - $cashOut - $cashRefunds,
            ],
        ];
    }

    /**
     * 6. Get Sales By Cashier Report (Laporan Penjualan per Kasir).
     */
    public function getSalesByCashierReport(Carbon $start, Carbon $end, ?int $userId = null): array
    {
        $query = Sale::select(
                'user_id',
                DB::raw('COUNT(id) as total_transactions'),
                DB::raw('SUM(subtotal) as total_subtotal'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(grand_total) as total_grand_total')
            )
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$start, $end]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $query->groupBy('user_id')
              ->orderByDesc('total_grand_total');

        $cashierSales = $query->get();

        $userModels = \App\Models\User::with('role')->whereIn('id', $cashierSales->pluck('user_id')->filter())->get()->keyBy('id');

        $overallTurnover = (float) $cashierSales->sum('total_grand_total');
        $overallTransactions = (int) $cashierSales->sum('total_transactions');

        foreach ($cashierSales as $item) {
            $user = $userModels->get($item->user_id);
            $item->cashier_name = $user?->name ?? 'Kasir #' . $item->user_id;
            $item->cashier_username = $user?->username ?? '-';
            $item->cashier_role = $user?->role?->name ?? 'Kasir';
            $item->avg_per_transaction = $item->total_transactions > 0 ? ($item->total_grand_total / $item->total_transactions) : 0;
            $item->contribution_percentage = $overallTurnover > 0 ? ($item->total_grand_total / $overallTurnover) * 100 : 0;
        }

        return [
            'cashiers' => $cashierSales,
            'summary' => [
                'total_cashiers_count' => $cashierSales->count(),
                'overall_turnover' => $overallTurnover,
                'overall_transactions' => $overallTransactions,
                'avg_store_basket_size' => $overallTransactions > 0 ? ($overallTurnover / $overallTransactions) : 0,
            ],
        ];
    }
}
