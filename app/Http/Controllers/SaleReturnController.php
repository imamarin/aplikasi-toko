<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\ReturnService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaleReturnController extends Controller
{
    public function __construct(
        protected ReturnService $returnService
    ) {}

    public function index()
    {
        $returns = SaleReturn::with(['sale.customer', 'user', 'details.product'])->latest('return_date')->paginate(15);
        return view('returns.sales.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $saleId = $request->input('sale_id');
        $sale = null;

        if ($saleId) {
            $sale = Sale::with(['details.product', 'customer'])->findOrFail($saleId);
        }

        $completedSales = Sale::where('status', 'completed')->latest('transaction_date')->limit(30)->get();

        return view('returns.sales.create', compact('sale', 'completedSales'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => ['required', 'exists:sales,id'],
            'refund_method' => ['required', 'string', 'in:cash,credit,none'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ], [
            'items.required' => 'Pilih minimal satu barang yang diretur.',
        ]);

        try {
            $saleReturn = $this->returnService->processSaleReturn($validated, Auth::id());

            return redirect()->route('returns.sales.show', $saleReturn->id)
                ->with('success', "Retur penjualan #{$saleReturn->return_number} berhasil diproses dan stok telah dikembalikan ke inventori.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses retur: ' . $e->getMessage());
        }
    }

    public function show(SaleReturn $saleReturn)
    {
        $saleReturn->load(['sale.customer', 'user', 'details.product.unit']);
        return view('returns.sales.show', compact('saleReturn'));
    }
}
