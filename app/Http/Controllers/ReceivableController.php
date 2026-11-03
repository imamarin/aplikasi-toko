<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\Setting;
use App\Services\DebtService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceivableController extends Controller
{
    public function __construct(
        protected DebtService $debtService
    ) {}

    public function index(Request $request)
    {
        $search = $request->input('search');
        $filterStatus = $request->input('status', 'unpaid'); // unpaid, all

        $customersQuery = Customer::query()
            ->withCount(['sales as unpaid_sales_count' => function ($q) {
                $q->where('status', 'completed')
                  ->whereIn('payment_status', ['unpaid', 'partial'])
                  ->whereRaw('grand_total > paid_amount');
            }])
            ->withSum(['sales as unpaid_grand_total' => function ($q) {
                $q->where('status', 'completed')
                  ->whereIn('payment_status', ['unpaid', 'partial'])
                  ->whereRaw('grand_total > paid_amount');
            }], 'grand_total')
            ->withSum(['sales as unpaid_paid_amount' => function ($q) {
                $q->where('status', 'completed')
                  ->whereIn('payment_status', ['unpaid', 'partial'])
                  ->whereRaw('grand_total > paid_amount');
            }], 'paid_amount')
            ->withMin(['sales as nearest_due_date' => function ($q) {
                $q->where('status', 'completed')
                  ->whereIn('payment_status', ['unpaid', 'partial'])
                  ->whereRaw('grand_total > paid_amount')
                  ->whereNotNull('due_date');
            }], 'due_date');

        if ($filterStatus === 'unpaid') {
            $customersQuery->whereHas('sales', function ($q) {
                $q->where('status', 'completed')
                  ->whereIn('payment_status', ['unpaid', 'partial'])
                  ->whereRaw('grand_total > paid_amount');
            });
        } else {
            $customersQuery->whereHas('sales', function ($q) {
                $q->where('status', 'completed')
                  ->where(function ($sq) {
                      $sq->whereIn('payment_status', ['unpaid', 'partial'])
                         ->orWhere('payment_method', 'debt');
                  });
            });
        }

        if ($search) {
            $customersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $customersQuery
            ->orderByDesc(DB::raw('COALESCE(unpaid_grand_total, 0) - COALESCE(unpaid_paid_amount, 0)'))
            ->paginate(15)
            ->withQueryString();

        // Global KPI Summary
        $unpaidSalesStats = Sale::where('status', 'completed')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereRaw('grand_total > paid_amount')
            ->selectRaw('
                COUNT(id) as total_invoices,
                COUNT(DISTINCT customer_id) as total_customers,
                COALESCE(SUM(grand_total - paid_amount), 0) as total_receivable,
                SUM(CASE WHEN due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END) as overdue_invoices
            ', [now()->toDateString()])
            ->first();

        $recentPayments = ReceivablePayment::with(['customer', 'user', 'allocations.sale'])
            ->latest('payment_date')
            ->limit(10)
            ->get();

        return view('receivables.index', compact(
            'customers',
            'unpaidSalesStats',
            'recentPayments',
            'search',
            'filterStatus'
        ));
    }

    public function show(Customer $customer)
    {
        $unpaidSales = Sale::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereRaw('grand_total > paid_amount')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $paidDebtSales = Sale::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->where(function ($q) {
                $q->where('payment_method', 'debt')
                  ->orWhereHas('paymentAllocations');
            })
            ->latest('transaction_date')
            ->limit(15)
            ->get();

        $payments = ReceivablePayment::with(['user', 'allocations.sale'])
            ->where('customer_id', $customer->id)
            ->latest('payment_date')
            ->paginate(10);

        $totalRemainingDebt = (float) $unpaidSales->sum(fn (Sale $s) => $s->remaining_debt);

        return view('receivables.show', compact(
            'customer',
            'unpaidSales',
            'paidDebtSales',
            'payments',
            'totalRemainingDebt'
        ));
    }

    public function storePayment(Request $request, Customer $customer)
    {
        if ($request->has('amount') && is_string($request->amount)) {
            $request->merge([
                'amount' => str_replace(['Rp', 'rp', ' ', '.'], '', $request->amount),
            ]);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'in:cash,transfer,qris,debit'],
            'payment_date' => ['nullable', 'date'],
            'sale_id' => ['nullable', 'exists:sales,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'amount.required' => 'Nominal pembayaran wajib diisi.',
            'amount.gt' => 'Nominal pembayaran harus lebih dari Rp 0.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
        ]);

        try {
            $payment = $this->debtService->payCustomerReceivable($customer, $validated, Auth::id());

            return redirect()->route('receivables.show', $customer->id)
                ->with('success', "Pembayaran piutang #{$payment->payment_number} sebesar Rp " . number_format($payment->total_paid, 0, ',', '.') . " berhasil disimpan dan dialokasikan ke " . $payment->allocations->count() . " nota!")
                ->with('last_payment_id', $payment->id);
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses pembayaran piutang: ' . $e->getMessage());
        }
    }

    public function printPaymentReceipt(ReceivablePayment $payment)
    {
        $payment->load(['customer', 'user', 'allocations.sale']);

        $remainingCustomerDebt = (float) Sale::where('customer_id', $payment->customer_id)
            ->where('status', 'completed')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereRaw('grand_total > paid_amount')
            ->sum(DB::raw('grand_total - paid_amount'));

        $settings = [
            'store_name' => Setting::get('store_name', 'Kasir POS'),
            'store_address' => Setting::get('store_address', 'Jl. Contoh Alamat Toko'),
            'store_phone' => Setting::get('store_phone', '08123456789'),
            'receipt_footer' => Setting::get('receipt_footer', 'Terima kasih atas pembayaran Anda!'),
        ];

        return view('receivables.receipt', compact('payment', 'remainingCustomerDebt', 'settings'));
    }
}

