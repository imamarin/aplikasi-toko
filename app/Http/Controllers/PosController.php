<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Services\SaleService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PosController extends Controller
{
    public function __construct(
        protected SaleService $saleService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $currentShift = $user->currentOpenShift;

        $customers = Customer::where('status', true)->orderBy('name')->get();
        $categories = Category::where('status', true)->orderBy('name')->get();

        // Default store tax percentage
        $defaultTax = (float) Setting::get('tax_percentage', 0);
        $storeName = Setting::get('store_name', 'Kasir POS');

        // Initial products for POS product grid
        $products = Product::with(['category', 'unit'])
            ->active()
            ->orderBy('name')
            ->limit(24)
            ->get();

        return view('pos.index', compact('currentShift', 'customers', 'categories', 'defaultTax', 'storeName', 'products'));
    }

    public function searchProduct(Request $request)
    {
        $query = $request->input('q');
        $categoryId = $request->input('category_id');

        $productsQuery = Product::with(['category', 'unit'])->active();

        if ($categoryId) {
            $productsQuery->where('category_id', $categoryId);
        }

        if ($query) {
            $productsQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('barcode', $query)
                  ->orWhere('barcode', 'like', "%{$query}%");
            });
        }

        $results = $productsQuery->orderBy('name')->limit(30)->get();

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.selling_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.subtotal' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'discount_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'in:cash,transfer,qris,debit,credit'],
            'notes' => ['nullable', 'string'],
        ], [
            'items.required' => 'Keranjang transaksi masih kosong.',
            'paid_amount.required' => 'Jumlah uang yang dibayarkan wajib diisi.',
            'payment_method.required' => 'Pilih salah satu metode pembayaran.',
        ]);

        try {
            $sale = $this->saleService->processCheckout($validated, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Transaksi penjualan berhasil disimpan!',
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'change_amount' => $sale->change_amount,
                'receipt_url' => route('sales.receipt', $sale->id),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
