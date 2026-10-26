<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function index(Request $request)
    {
        $query = Product::with(['category', 'unit'])->latest();

        // Search by name, SKU, or barcode
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Filter by status
        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('status', (bool) $request->input('status'));
        }

        // Filter by stock level
        if ($request->input('stock_status') === 'low') {
            $query->lowStock();
        } elseif ($request->input('stock_status') === 'empty') {
            $query->where('stock', '<=', 0);
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::where('status', true)->orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::where('status', true)->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        // Auto generate SKU recommendation
        $nextId = (Product::max('id') ?? 0) + 1;
        $suggestedSku = 'PRD-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);

        return view('products.create', compact('categories', 'units', 'suggestedSku'));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['status'] = $request->boolean('status', true);
        $initialStock = (int) ($data['stock'] ?? 0);
        $data['stock'] = 0; // We set to 0 and record via StockService for proper audit trail

        $product = Product::create($data);

        // Record initial stock movement
        if ($initialStock > 0) {
            $this->stockService->recordMovement(
                product: $product,
                type: 'initial',
                qtyIn: $initialStock,
                qtyOut: 0,
                userId: Auth::id(),
                notes: 'Saldo stok awal pembuatan produk'
            );
        }

        return redirect()->route('products.index')->with('success', "Produk '{$product->name}' berhasil ditambahkan.");
    }

    public function show(Product $product)
    {
        $product->load(['category', 'unit']);
        $movements = $product->stockMovements()->with('user')->latest()->paginate(15);

        return view('products.show', compact('product', 'movements'));
    }

    public function edit(Product $product)
    {
        $categories = Category::where('status', true)->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return view('products.edit', compact('product', 'categories', 'units'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['status'] = $request->boolean('status', true);

        // Notice: Stock is not updated directly via Product edit, but via Stock adjustment / opname!
        $product->update($data);

        return redirect()->route('products.index')->with('success', "Data produk '{$product->name}' berhasil diperbarui.");
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Produk berhasil dinonaktifkan/dihapus.');
    }

    public function exportExcel()
    {
        $products = Product::with(['category', 'unit'])->orderBy('name')->get();

        $filename = "daftar-produk-" . date('Ymd-His') . ".csv";
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'SKU', 'Barcode', 'Nama Produk', 'Kategori', 'Satuan', 'Harga Beli (HPP)', 'Harga Jual', 'Stok', 'Stok Minimal', 'Status'];

        $callback = function () use ($products, $columns) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            foreach ($products as $p) {
                fputcsv($file, [
                    $p->id,
                    $p->sku,
                    $p->barcode ?? '-',
                    $p->name,
                    $p->category?->name ?? '-',
                    $p->unit?->name ?? '-',
                    $p->cost_price,
                    $p->selling_price,
                    $p->stock,
                    $p->min_stock,
                    $p->status ? 'Aktif' : 'Nonaktif',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
