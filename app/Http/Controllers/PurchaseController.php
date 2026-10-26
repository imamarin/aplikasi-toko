<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {}

    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'user'])->latest('purchase_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('purchase_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($status = $request->input('payment_status')) {
            $query->where('payment_status', $status);
        }

        $purchases = $query->paginate(15)->withQueryString();
        $suppliers = Supplier::where('status', true)->orderBy('name')->get();

        return view('purchases.index', compact('purchases', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', true)->orderBy('name')->get();
        $products = Product::with('unit')->where('status', true)->orderBy('name')->get();

        return view('purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $items = $request->input('items', []);
        if (is_array($items)) {
            foreach ($items as $k => $item) {
                if (isset($item['cost_price']) && is_string($item['cost_price'])) {
                    $items[$k]['cost_price'] = str_replace(['Rp', 'rp', ' ', '.'], '', $item['cost_price']);
                }
                if (isset($item['discount']) && is_string($item['discount'])) {
                    $items[$k]['discount'] = str_replace(['Rp', 'rp', ' ', '.'], '', $item['discount']);
                }
            }
            $request->merge(['items' => $items]);
        }
        if ($request->has('discount_amount') && is_string($request->discount_amount)) {
            $request->merge(['discount_amount' => str_replace(['Rp', 'rp', ' ', '.'], '', $request->discount_amount)]);
        }
        if ($request->has('paid_amount') && is_string($request->paid_amount)) {
            $request->merge(['paid_amount' => str_replace(['Rp', 'rp', ' ', '.'], '', $request->paid_amount)]);
        }

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'in:cash,transfer,credit'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ], [
            'supplier_id.required' => 'Supplier wajib dipilih.',
            'items.required' => 'Daftar barang pembelian wajib diisi minimal 1 produk.',
        ]);

        try {
            $purchase = $this->purchaseService->createPurchase($validated, Auth::id());

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', "Pembelian PO #{$purchase->purchase_number} berhasil disimpan dan stok barang telah bertambah!");
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses pembelian: ' . $e->getMessage());
        }
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'user', 'details.product.unit', 'returns.details.product']);
        return view('purchases.show', compact('purchase'));
    }
}
