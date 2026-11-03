<?php

namespace App\Http\Controllers;

use App\Models\PayablePayment;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\Supplier;
use App\Services\DebtService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayableController extends Controller
{
    public function __construct(
        protected DebtService $debtService
    ) {}

    public function index(Request $request)
    {
        $search = $request->input('search');
        $filterStatus = $request->input('status', 'unpaid'); // unpaid, all

        $suppliersQuery = Supplier::query()
            ->withCount(['purchases as unpaid_purchases_count' => function ($q) {
                $q->whereIn('payment_status', ['unpaid', 'partial'])
                  ->whereRaw('grand_total > paid_amount');
            }])
            ->withSum(['purchases as unpaid_grand_total' => function ($q) {
                $q->whereIn('payment_status', ['unpaid', 'partial'])
                  ->whereRaw('grand_total > paid_amount');
            }], 'grand_total')
            ->withSum(['purchases as unpaid_paid_amount' => function ($q) {
                $q->whereIn('payment_status', ['unpaid', 'partial'])
                  ->whereRaw('grand_total > paid_amount');
            }], 'paid_amount')
            ->withMin(['purchases as nearest_due_date' => function ($q) {
                $q->whereIn('payment_status', ['unpaid', 'partial'])
                  ->whereRaw('grand_total > paid_amount')
                  ->whereNotNull('due_date');
            }], 'due_date');

        if ($filterStatus === 'unpaid') {
            $suppliersQuery->whereHas('purchases', function ($q) {
                $q->whereIn('payment_status', ['unpaid', 'partial'])
                  ->whereRaw('grand_total > paid_amount');
            });
        } else {
            $suppliersQuery->whereHas('purchases', function ($q) {
                $q->whereIn('payment_status', ['unpaid', 'partial'])
                  ->orWhere('payment_method', 'credit')
                  ->orWhereHas('paymentAllocations');
            });
        }

        if ($search) {
            $suppliersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $suppliers = $suppliersQuery
            ->orderByDesc(DB::raw('COALESCE(unpaid_grand_total, 0) - COALESCE(unpaid_paid_amount, 0)'))
            ->paginate(15)
            ->withQueryString();

        // Global KPI Summary
        $unpaidPurchaseStats = Purchase::whereIn('payment_status', ['unpaid', 'partial'])
            ->whereRaw('grand_total > paid_amount')
            ->selectRaw('
                COUNT(id) as total_purchases,
                COUNT(DISTINCT supplier_id) as total_suppliers,
                COALESCE(SUM(grand_total - paid_amount), 0) as total_payable,
                SUM(CASE WHEN due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END) as overdue_purchases
            ', [now()->toDateString()])
            ->first();

        $recentPayments = PayablePayment::with(['supplier', 'user', 'allocations.purchase'])
            ->latest('payment_date')
            ->limit(10)
            ->get();

        return view('payables.index', compact(
            'suppliers',
            'unpaidPurchaseStats',
            'recentPayments',
            'search',
            'filterStatus'
        ));
    }

    public function show(Supplier $supplier)
    {
        $unpaidPurchases = Purchase::where('supplier_id', $supplier->id)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereRaw('grand_total > paid_amount')
            ->orderBy('purchase_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $paidDebtPurchases = Purchase::where('supplier_id', $supplier->id)
            ->where('payment_status', 'paid')
            ->where(function ($q) {
                $q->where('payment_method', 'credit')
                  ->orWhereHas('paymentAllocations');
            })
            ->latest('purchase_date')
            ->limit(15)
            ->get();

        $payments = PayablePayment::with(['user', 'allocations.purchase'])
            ->where('supplier_id', $supplier->id)
            ->latest('payment_date')
            ->paginate(10);

        $totalRemainingDebt = (float) $unpaidPurchases->sum(fn (Purchase $p) => $p->remaining_debt);

        return view('payables.show', compact(
            'supplier',
            'unpaidPurchases',
            'paidDebtPurchases',
            'payments',
            'totalRemainingDebt'
        ));
    }

    public function storePayment(Request $request, Supplier $supplier)
    {
        if ($request->has('amount') && is_string($request->amount)) {
            $request->merge([
                'amount' => str_replace(['Rp', 'rp', ' ', '.'], '', $request->amount),
            ]);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'in:cash,transfer'],
            'payment_date' => ['nullable', 'date'],
            'purchase_id' => ['nullable', 'exists:purchases,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'amount.required' => 'Nominal pembayaran hutang wajib diisi.',
            'amount.gt' => 'Nominal pembayaran harus lebih dari Rp 0.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
        ]);

        try {
            $payment = $this->debtService->paySupplierPayable($supplier, $validated, Auth::id());

            return redirect()->route('payables.show', $supplier->id)
                ->with('success', "Pembayaran hutang #{$payment->payment_number} sebesar Rp " . number_format($payment->total_paid, 0, ',', '.') . " berhasil disimpan dan dialokasikan ke " . $payment->allocations->count() . " PO!")
                ->with('last_payment_id', $payment->id);
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses pembayaran hutang: ' . $e->getMessage());
        }
    }

    public function printPaymentReceipt(PayablePayment $payment)
    {
        $payment->load(['supplier', 'user', 'allocations.purchase']);

        $remainingSupplierDebt = (float) Purchase::where('supplier_id', $payment->supplier_id)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereRaw('grand_total > paid_amount')
            ->sum(DB::raw('grand_total - paid_amount'));

        $settings = [
            'store_name' => Setting::get('store_name', 'Kasir POS'),
            'store_address' => Setting::get('store_address', 'Jl. Contoh Alamat Toko'),
            'store_phone' => Setting::get('store_phone', '08123456789'),
            'receipt_footer' => Setting::get('receipt_footer', 'Bukti Pembayaran Hutang Supplier'),
        ];

        return view('payables.receipt', compact('payment', 'remainingSupplierDebt', 'settings'));
    }
}

