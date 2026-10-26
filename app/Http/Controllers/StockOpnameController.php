<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Services\StockService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function index()
    {
        $opnames = StockOpname::with(['user', 'details.product'])->latest()->paginate(10);
        return view('stock-opnames.index', compact('opnames'));
    }

    public function create()
    {
        $products = Product::with(['category', 'unit'])->orderBy('name')->get();
        return view('stock-opnames.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'opname_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.physical_stock' => ['required', 'integer', 'min:0'],
        ], [
            'items.required' => 'Pilih minimal satu produk untuk diopname.',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $date = Carbon::parse($request->opname_date);
                $count = StockOpname::whereDate('created_at', Carbon::today())->count() + 1;
                $opnameNumber = 'OPN-' . $date->format('Ymd') . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

                $opname = StockOpname::create([
                    'opname_number' => $opnameNumber,
                    'user_id' => Auth::id(),
                    'opname_date' => $date,
                    'status' => 'adjusted',
                    'notes' => $request->input('notes'),
                ]);

                foreach ($request->input('items') as $item) {
                    $product = Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();
                    $systemStock = (int) $product->stock;
                    $physicalStock = (int) $item['physical_stock'];
                    $difference = $physicalStock - $systemStock;

                    StockOpnameDetail::create([
                        'stock_opname_id' => $opname->id,
                        'product_id' => $product->id,
                        'system_stock' => $systemStock,
                        'physical_stock' => $physicalStock,
                        'difference' => $difference,
                        'cost_price' => $product->cost_price,
                        'notes' => $item['notes'] ?? null,
                    ]);

                    // Adjust actual stock via StockService
                    $this->stockService->adjustStock(
                        product: $product,
                        physicalStock: $physicalStock,
                        userId: Auth::id(),
                        type: 'opname',
                        refType: StockOpname::class,
                        refId: $opname->id,
                        notes: "Penyesuaian Fisik Opname #{$opnameNumber}"
                    );
                }
            });

            return redirect()->route('stock-opnames.index')->with('success', 'Stok opname dan penyesuaian saldo berhasil diproses.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Gagal memproses stok opname: ' . $e->getMessage());
        }
    }

    public function show(StockOpname $stockOpname)
    {
        $stockOpname->load(['user', 'details.product.unit']);
        return view('stock-opnames.show', compact('stockOpname'));
    }
}
