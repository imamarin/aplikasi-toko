<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetail;
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

        $query = Sale::with(['user', 'customer', 'details', 'returns'])
            ->whereBetween('transaction_date', [$start, $end])
            ->where('status', 'completed');

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $sales = $query->latest('transaction_date')->get();

        $totalSubtotal = (float) $sales->sum('subtotal');
        $totalDiscount = (float) $sales->sum('discount_amount');
        $totalTax = (float) $sales->sum('tax_amount');
        $totalShipping = (float) $sales->sum('shipping_cost');
        $grossGrandTotal = (float) $sales->sum('grand_total');
        $totalReturns = (float) $sales->sum(fn (Sale $s) => $s->total_returned);
        $totalGrandTotal = max(0.0, $grossGrandTotal - $totalReturns);
        $totalTransactions = $sales->count();
        $totalProductSales = max(0.0, $totalSubtotal - $totalDiscount - $totalReturns);

        return [
            'sales' => $sales,
            'summary' => [
                'total_subtotal' => $totalSubtotal,
                'total_discount' => $totalDiscount,
                'total_returns' => $totalReturns,
                'total_tax' => $totalTax,
                'total_shipping' => $totalShipping,
                'total_product_sales' => $totalProductSales,
                'gross_grand_total' => $grossGrandTotal,
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
        $sales = Sale::with(['details', 'returns.details'])
            ->whereBetween('transaction_date', [$start, $end])
            ->where('status', 'completed')
            ->get();

        $totalSubtotal = (float) $sales->sum('subtotal');
        $totalDiscounts = (float) $sales->sum('discount_amount');
        $totalShippingCost = (float) $sales->sum('shipping_cost');
        $totalGrandTotal = (float) $sales->sum('grand_total');

        // Total Cost of Goods Sold (HPP) & Retur Penjualan
        $grossCogs = 0.0;
        $totalReturns = 0.0;
        $returnedCogs = 0.0;

        foreach ($sales as $sale) {
            foreach ($sale->details as $detail) {
                $grossCogs += ((float) $detail->cost_price * (float) $detail->qty);
            }

            foreach ($sale->returns as $saleReturn) {
                if ($saleReturn->refund_method === 'none') {
                    continue;
                }
                $totalReturns += (float) $saleReturn->total_refund;
                foreach ($saleReturn->details as $retDetail) {
                    $origDetail = $sale->details->firstWhere('product_id', $retDetail->product_id);
                    $unitCost = (float) ($origDetail?->cost_price ?? 0);
                    $returnedCogs += ($unitCost * (float) $retDetail->qty);
                }
            }
        }

        // Total Product Net Revenue (Penjualan Produk Bersih setelah diskon & retur)
        $totalProductRevenue = max(0.0, $totalSubtotal - $totalDiscounts - $totalReturns);
        $netGrandTotal = max(0.0, $totalGrandTotal - $totalReturns);
        $totalCogs = max(0.0, $grossCogs - $returnedCogs);

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
            'total_grand_total' => $netGrandTotal,
            'total_subtotal' => $totalSubtotal,
            'total_discounts' => $totalDiscounts,
            'total_returns' => $totalReturns,
            'gross_cogs' => $grossCogs,
            'returned_cogs' => $returnedCogs,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'expenses' => $expenses,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Get Best Selling Products (Net of Returns).
     */
    public function getBestSellers(Carbon $start, Carbon $end, int $limit = 10)
    {
        $items = SaleDetail::select(
                'product_id',
                'product_name',
                DB::raw('SUM(qty) as total_qty'),
                DB::raw('SUM(subtotal - discount_amount) as total_sales'),
                DB::raw('SUM(qty * cost_price) as total_cogs'),
                DB::raw('SUM((subtotal - discount_amount) - (qty * cost_price)) as total_profit')
            )
            ->whereHas('sale', function ($query) use ($start, $end) {
                $query->whereBetween('transaction_date', [$start, $end])
                      ->where('status', 'completed');
            })
            ->groupBy('product_id', 'product_name')
            ->get();

        $returnedMap = $this->getReturnedProductStatsMap($start, $end);

        foreach ($items as $item) {
            $ret = $returnedMap[$item->product_id] ?? null;
            $item->gross_qty = (float) $item->total_qty;
            $item->returned_qty = (float) ($ret['returned_qty'] ?? 0);
            $item->total_qty = max(0, $item->gross_qty - $item->returned_qty);
            $item->total_sales = max(0, (float) $item->total_sales - (float) ($ret['returned_sales'] ?? 0));
            $item->total_cogs = max(0, (float) $item->total_cogs - (float) ($ret['returned_cogs'] ?? 0));
            $item->total_profit = $item->total_sales - $item->total_cogs;
        }

        return $items
            ->filter(fn ($i) => $i->total_qty > 0 || $i->gross_qty > 0)
            ->sortByDesc('total_qty')
            ->take($limit)
            ->values();
    }

    /**
     * Helper: Calculate returned qty, sales, and COGS per product_id for sales in [$start, $end].
     */
    private function getReturnedProductStatsMap(Carbon $start, Carbon $end): array
    {
        $salesWithReturns = Sale::with(['details', 'returns.details'])
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$start, $end])
            ->whereHas('returns', function ($q) {
                $q->where('refund_method', '!=', 'none');
            })
            ->get();

        $map = [];
        foreach ($salesWithReturns as $sale) {
            foreach ($sale->returns as $ret) {
                if ($ret->refund_method === 'none') {
                    continue;
                }
                foreach ($ret->details as $retDetail) {
                    $pid = $retDetail->product_id;
                    if (!isset($map[$pid])) {
                        $map[$pid] = [
                            'returned_qty' => 0.0,
                            'returned_sales' => 0.0,
                            'returned_cogs' => 0.0,
                        ];
                    }
                    $origDetail = $sale->details->firstWhere('product_id', $pid);
                    $unitCost = (float) ($origDetail?->cost_price ?? 0);

                    $map[$pid]['returned_qty'] += (float) $retDetail->qty;
                    $map[$pid]['returned_sales'] += (float) $retDetail->subtotal;
                    $map[$pid]['returned_cogs'] += ((float) $retDetail->qty * $unitCost);
                }
            }
        }

        return $map;
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
        $returnedMap = $this->getReturnedProductStatsMap($start, $end);

        $overallQty = 0.0;
        $overallReturnedQty = 0.0;
        $overallSales = 0.0;
        $overallCogs = 0.0;
        $overallProfit = 0.0;

        foreach ($products as $p) {
            $prodModel = $productModels->get($p->product_id);
            $ret = $returnedMap[$p->product_id] ?? null;

            $p->gross_qty = (float) $p->total_qty;
            $p->returned_qty = (float) ($ret['returned_qty'] ?? 0);
            $p->returned_sales = (float) ($ret['returned_sales'] ?? 0);
            $p->returned_cogs = (float) ($ret['returned_cogs'] ?? 0);

            $p->total_qty = max(0.0, $p->gross_qty - $p->returned_qty);
            $p->total_sales = max(0.0, (float) $p->total_sales - $p->returned_sales);
            $p->total_cogs = max(0.0, (float) $p->total_cogs - $p->returned_cogs);
            $p->total_profit = $p->total_sales - $p->total_cogs;

            $p->sku = $prodModel?->sku ?? '-';
            $p->category_name = $prodModel?->category?->name ?? '-';
            $p->unit_name = $prodModel?->unit?->short_name ?? 'pcs';
            $p->margin_percentage = $p->total_sales > 0 ? ($p->total_profit / $p->total_sales) * 100 : 0;

            $overallQty += $p->total_qty;
            $overallReturnedQty += $p->returned_qty;
            $overallSales += $p->total_sales;
            $overallCogs += $p->total_cogs;
            $overallProfit += $p->total_profit;
        }

        if ($sortBy === 'sales_desc') {
            $products = $products->sortByDesc('total_sales')->values();
        } elseif ($sortBy === 'profit_desc') {
            $products = $products->sortByDesc('total_profit')->values();
        } else {
            $products = $products->sortByDesc('total_qty')->values();
        }

        $overallMargin = $overallSales > 0 ? ($overallProfit / $overallSales) * 100 : 0;

        return [
            'products' => $products,
            'summary' => [
                'total_products_count' => $products->count(),
                'overall_qty' => $overallQty,
                'overall_returned_qty' => $overallReturnedQty,
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

        // Hitung retur penjualan per tanggal transaksi nota asal
        $returnsByDate = SaleReturn::select(
                DB::raw('DATE(sales.transaction_date) as date'),
                DB::raw('SUM(sale_returns.total_refund) as total_returns')
            )
            ->join('sales', 'sales.id', '=', 'sale_returns.sale_id')
            ->where('sales.status', 'completed')
            ->where('sale_returns.refund_method', '!=', 'none')
            ->whereBetween('sales.transaction_date', [$start, $end])
            ->groupBy(DB::raw('DATE(sales.transaction_date)'))
            ->pluck('total_returns', 'date');

        foreach ($salesByDate as $t) {
            $t->gross_grand_total = (float) $t->total_grand_total;
            $t->total_returns = (float) ($returnsByDate[$t->date] ?? 0);
            $t->total_grand_total = max(0.0, $t->gross_grand_total - $t->total_returns);
        }

        $totalTransactions = (int) $salesByDate->sum('total_transactions');
        $totalSubtotal = (float) $salesByDate->sum('total_subtotal');
        $totalDiscount = (float) $salesByDate->sum('total_discount');
        $totalReturns = (float) $salesByDate->sum('total_returns');
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
                'total_returns' => $totalReturns,
                'total_tax' => $totalTax,
                'total_shipping' => $totalShipping,
                'total_product_sales' => max(0.0, $totalSubtotal - $totalDiscount - $totalReturns),
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

        // Hitung retur per customer untuk nota pada periode ini
        $returnsByCustomerRows = SaleReturn::select(
                'sales.customer_id',
                DB::raw('SUM(sale_returns.total_refund) as total_returns')
            )
            ->join('sales', 'sales.id', '=', 'sale_returns.sale_id')
            ->where('sales.status', 'completed')
            ->where('sale_returns.refund_method', '!=', 'none')
            ->whereBetween('sales.transaction_date', [$start, $end])
            ->groupBy('sales.customer_id')
            ->get();

        $returnsByCustomer = [];
        foreach ($returnsByCustomerRows as $rRow) {
            $key = $rRow->customer_id ?? 'umum';
            $returnsByCustomer[$key] = (float) $rRow->total_returns;
        }

        $customerModels = \App\Models\Customer::whereIn('id', $customerSales->pluck('customer_id')->filter())->get()->keyBy('id');

        $memberSpent = 0.0;
        $memberTx = 0;
        $generalSpent = 0.0;
        $generalTx = 0;
        $totalReturns = 0.0;

        foreach ($customerSales as $item) {
            $retKey = $item->customer_id ?? 'umum';
            $item->gross_spent = (float) $item->total_spent;
            $item->total_returns = (float) ($returnsByCustomer[$retKey] ?? 0);
            $item->total_spent = max(0.0, $item->gross_spent - $item->total_returns);
            $totalReturns += $item->total_returns;

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

        if ($sortBy === 'total_desc') {
            $customerSales = $customerSales->sortByDesc('total_spent')->values();
        }

        $totalSpent = $memberSpent + $generalSpent;
        $totalTx = $memberTx + $generalTx;

        return [
            'customers' => $customerSales,
            'summary' => [
                'total_customers_count' => $customerSales->count(),
                'total_spent' => $totalSpent,
                'total_returns' => $totalReturns,
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

        // Hitung retur per kasir untuk nota pada periode ini
        $returnsByCashier = SaleReturn::select(
                'sales.user_id',
                DB::raw('SUM(sale_returns.total_refund) as total_returns')
            )
            ->join('sales', 'sales.id', '=', 'sale_returns.sale_id')
            ->where('sales.status', 'completed')
            ->where('sale_returns.refund_method', '!=', 'none')
            ->whereBetween('sales.transaction_date', [$start, $end])
            ->groupBy('sales.user_id')
            ->pluck('total_returns', 'user_id');

        $userModels = \App\Models\User::with('role')->whereIn('id', $cashierSales->pluck('user_id')->filter())->get()->keyBy('id');

        foreach ($cashierSales as $item) {
            $item->gross_grand_total = (float) $item->total_grand_total;
            $item->total_returns = (float) ($returnsByCashier[$item->user_id] ?? 0);
            $item->total_grand_total = max(0.0, $item->gross_grand_total - $item->total_returns);
        }

        $cashierSales = $cashierSales->sortByDesc('total_grand_total')->values();

        $overallTurnover = (float) $cashierSales->sum('total_grand_total');
        $overallReturns = (float) $cashierSales->sum('total_returns');
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
                'overall_returns' => $overallReturns,
                'overall_transactions' => $overallTransactions,
                'avg_store_basket_size' => $overallTransactions > 0 ? ($overallTurnover / $overallTransactions) : 0,
            ],
        ];
    }

    /**
     * 7. Get Daily Income Report (Laporan Pendapatan Harian - Opsi B).
     *
     * Columns:
     * - TANGGAL
     * - PENJUALAN (Grand Total penjualan completed pada tanggal tsb)
     * - PEMBAYARAN REAL KIOS (CASH + TF + QRIS yang benar-benar diterima di tanggal tsb)
     * - CASH (Penjualan tunai + DP piutang tunai + Pelunasan piutang tunai pada tanggal tsb)
     * - TF (Penjualan transfer/debit + DP piutang TF/debit + Pelunasan piutang TF/debit pada tanggal tsb)
     * - QRIS (Penjualan QRIS + DP piutang QRIS + Pelunasan piutang QRIS pada tanggal tsb)
     * - CREDIT (Nilai piutang awal pada tanggal transaksi: grand_total - initial_dp)
     * - MODAL (HPP barang terjual pada tanggal tsb: SUM(qty * cost_price))
     * - LABA KOTOR (PENJUALAN - MODAL)
     */
    public function getIncomeReport(Carbon $start, Carbon $end): array
    {
        $sales = Sale::with(['details', 'returns.details', 'paymentAllocations.receivablePayment'])
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$start, $end])
            ->orderBy('transaction_date', 'asc')
            ->get();

        // Subsequent receivable payments (cicilan/pelunasan uang riil di luar DP awal & di luar potongan retur)
        $subsequentPayments = ReceivablePayment::query()
            ->whereBetween('payment_date', [$start, $end])
            ->where('payment_method', '!=', 'return')
            ->where(function ($q) {
                $q->whereNull('notes')
                  ->orWhere(function ($sq) {
                      $sq->where('notes', 'not like', 'Uang Muka (DP)%')
                         ->where('notes', 'not like', 'Potongan Retur%');
                  });
            })
            ->orderBy('payment_date', 'asc')
            ->get();

        $dailyMap = [];

        $initDay = function (string $dateKey) use (&$dailyMap) {
            if (!isset($dailyMap[$dateKey])) {
                $dailyMap[$dateKey] = [
                    'date' => $dateKey,
                    'total_transactions' => 0,
                    'penjualan' => 0.0,
                    'pembayaran_real_kios' => 0.0,
                    'cash' => 0.0,
                    'tf' => 0.0,
                    'qris' => 0.0,
                    'credit' => 0.0,
                    'modal' => 0.0,
                    'laba_kotor' => 0.0,
                    'debt_repayment' => 0.0,
                ];
            }
        };

        $addPaymentByMethod = function (string $dateKey, string $method, float $amount) use (&$dailyMap) {
            if ($amount <= 0) {
                return;
            }
            $method = strtolower($method);
            if ($method === 'cash') {
                $dailyMap[$dateKey]['cash'] += $amount;
            } elseif ($method === 'qris') {
                $dailyMap[$dateKey]['qris'] += $amount;
            } else {
                // transfer, debit, credit_card, dll masuk ke kolom TF
                $dailyMap[$dateKey]['tf'] += $amount;
            }
        };

        foreach ($sales as $sale) {
            $dateKey = $sale->transaction_date->format('Y-m-d');
            $initDay($dateKey);

            $grandTotal = (float) $sale->grand_total;
            $saleModal = 0.0;
            foreach ($sale->details as $detail) {
                $saleModal += ((float) $detail->qty * (float) $detail->cost_price);
            }

            // Hitung potongan retur pada nota penjualan ini (kecuali tukar barang tanpa refund)
            $returnedRevenue = 0.0;
            $returnedModal = 0.0;
            foreach ($sale->returns as $saleReturn) {
                if ($saleReturn->refund_method === 'none') {
                    continue;
                }
                $returnedRevenue += (float) $saleReturn->total_refund;
                foreach ($saleReturn->details as $retDetail) {
                    $origDetail = $sale->details->firstWhere('product_id', $retDetail->product_id);
                    $unitCost = (float) ($origDetail?->cost_price ?? 0);
                    $returnedModal += ($unitCost * (float) $retDetail->qty);
                }
            }

            $netGrandTotal = max(0.0, $grandTotal - $returnedRevenue);
            $netSaleModal = max(0.0, $saleModal - $returnedModal);

            $dailyMap[$dateKey]['total_transactions'] += 1;
            $dailyMap[$dateKey]['penjualan'] += $netGrandTotal;
            $dailyMap[$dateKey]['modal'] += $netSaleModal;

            if ($sale->payment_method === 'debt') {
                // Hitung alokasi pasca-checkout (cicilan lanjutan & potongan retur)
                $postCheckoutAllocated = 0.0;
                foreach ($sale->paymentAllocations as $alloc) {
                    $notes = (string) ($alloc->receivablePayment?->notes ?? '');
                    if (!str_starts_with($notes, 'Uang Muka (DP)')) {
                        $postCheckoutAllocated += (float) $alloc->amount;
                    }
                }

                // DP awal murni saat transaksi berlangsung
                $initialDp = max(0.0, min($grandTotal, (float) $sale->paid_amount - $postCheckoutAllocated));
                $initialCredit = max(0.0, $grandTotal - $initialDp);

                // Jika ada retur pada nota kasbon ini, potong nilai Credit terlebih dahulu, lalu kelebihannya potong DP
                $creditReducedByReturn = min($initialCredit, $returnedRevenue);
                $netInitialCredit = max(0.0, $initialCredit - $creditReducedByReturn);
                $dpReducedByReturn = max(0.0, $returnedRevenue - $creditReducedByReturn);
                $netInitialDp = max(0.0, $initialDp - $dpReducedByReturn);

                $dailyMap[$dateKey]['credit'] += $netInitialCredit;

                if ($netInitialDp > 0) {
                    $dpMethod = $sale->dp_payment_method ?? 'cash';
                    $addPaymentByMethod($dateKey, $dpMethod, $netInitialDp);
                }
            } else {
                $addPaymentByMethod($dateKey, $sale->payment_method ?? 'cash', $netGrandTotal);
            }
        }

        // Tambahkan pelunasan/cicilan piutang lanjutan pada tanggal pembayaran diterima (Opsi B)
        foreach ($subsequentPayments as $payment) {
            $dateKey = $payment->payment_date->format('Y-m-d');
            $initDay($dateKey);

            $paidAmount = (float) $payment->total_paid;
            $method = $payment->payment_method ?? 'cash';

            $addPaymentByMethod($dateKey, $method, $paidAmount);
            $dailyMap[$dateKey]['debt_repayment'] += $paidAmount;
        }

        ksort($dailyMap);

        $records = collect();
        foreach ($dailyMap as $dateKey => $row) {
            $row['pembayaran_real_kios'] = $row['cash'] + $row['tf'] + $row['qris'];
            $row['laba_kotor'] = $row['penjualan'] - $row['modal'];
            $records->push((object) $row);
        }

        $totalPenjualan = (float) $records->sum('penjualan');
        $totalPembayaranReal = (float) $records->sum('pembayaran_real_kios');
        $totalCash = (float) $records->sum('cash');
        $totalTf = (float) $records->sum('tf');
        $totalQris = (float) $records->sum('qris');
        $totalCredit = (float) $records->sum('credit');
        $totalModal = (float) $records->sum('modal');
        $totalLabaKotor = (float) $records->sum('laba_kotor');
        $totalDebtRepayment = (float) $records->sum('debt_repayment');
        $totalTransactions = (int) $records->sum('total_transactions');

        return [
            'records' => $records,
            'summary' => [
                'total_days' => $records->count(),
                'total_transactions' => $totalTransactions,
                'total_penjualan' => $totalPenjualan,
                'total_pembayaran_real_kios' => $totalPembayaranReal,
                'total_cash' => $totalCash,
                'total_tf' => $totalTf,
                'total_qris' => $totalQris,
                'total_credit' => $totalCredit,
                'total_modal' => $totalModal,
                'total_laba_kotor' => $totalLabaKotor,
                'total_debt_repayment' => $totalDebtRepayment,
                'gross_margin_percentage' => $totalPenjualan > 0 ? ($totalLabaKotor / $totalPenjualan) * 100 : 0,
            ],
        ];
    }
}
