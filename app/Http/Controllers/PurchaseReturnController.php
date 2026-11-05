<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Services\ReturnService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnController extends Controller
{
    public function __construct(
        protected ReturnService $returnService
    ) {}

    public function index()
    {
        $returns = PurchaseReturn::with(['purchase.supplier', 'user', 'details.product'])->latest('return_date')->paginate(15);
        return view('returns.purchases.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $purchaseId = $request->input('purchase_id');
        $purchase = null;

        if ($purchaseId) {
            $purchase = Purchase::with(['details.product', 'supplier'])->findOrFail($purchaseId);
        }

        $purchases = Purchase::with('supplier')->latest('purchase_date')->limit(30)->get();

        return view('returns.purchases.create', compact('purchase', 'purchases'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => ['required', 'exists:purchases,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ], [
            'items.required' => 'Pilih minimal satu barang yang diretur ke supplier.',
            'items.*.qty.min' => 'Jumlah retur tidak boleh bernilai negatif.',
        ]);

        // Ambil hanya item yang memiliki Qty Retur > 0
        $validated['items'] = collect($validated['items'])
            ->filter(fn ($item) => (float) ($item['qty'] ?? 0) > 0)
            ->values()
            ->all();

        if (empty($validated['items'])) {
            return back()
                ->withInput()
                ->with('error', 'Pilih minimal satu barang dengan jumlah (Qty Retur) lebih dari 0.');
        }

        try {
            $purchaseReturn = $this->returnService->processPurchaseReturn($validated, Auth::id());

            return redirect()->route('returns.purchases.show', $purchaseReturn->id)
                ->with('success', "Retur pembelian ke supplier #{$purchaseReturn->return_number} berhasil diproses dan stok barang telah dikurangi.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses retur supplier: ' . $e->getMessage());
        }
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['purchase.supplier', 'supplier', 'user', 'details.product.unit']);
        return view('returns.purchases.show', compact('purchaseReturn'));
    }
}
