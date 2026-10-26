<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'unit'])->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $filter = $request->input('filter');
        if ($filter === 'low') {
            $query->lowStock();
        } elseif ($filter === 'empty') {
            $query->where('stock', '<=', 0);
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::where('status', true)->orderBy('name')->get();

        $totalStockUnits = (int) Product::sum('stock');
        $lowStockCount   = (int) Product::lowStock()->count();
        $emptyStockCount = (int) Product::where('stock', '<=', 0)->count();

        return view('stocks.index', compact('products', 'categories', 'totalStockUnits', 'lowStockCount', 'emptyStockCount'));
    }

    public function stockCard(Product $product)
    {
        $product->load(['category', 'unit']);
        $movements = $product->stockMovements()->with('user')->latest()->paginate(20);

        return view('stocks.card', compact('product', 'movements'));
    }

    public function lowStock()
    {
        return redirect()->route('stocks.index', ['filter' => 'low']);
    }
}
