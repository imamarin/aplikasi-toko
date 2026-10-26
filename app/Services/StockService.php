<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Exception;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Record a stock movement atomicaly with negative stock prevention.
     *
     * @throws Exception
     */
    public function recordMovement(
        Product|int $product,
        string $type,
        float|int $qtyIn,
        float|int $qtyOut,
        ?int $userId = null,
        ?string $refType = null,
        ?int $refId = null,
        ?string $notes = null
    ): StockMovement {
        $productId = $product instanceof Product ? $product->id : $product;

        // Lock row to prevent race conditions
        $lockedProduct = Product::where('id', $productId)->lockForUpdate()->firstOrFail();

        $stockBefore = (float) $lockedProduct->stock;
        $stockAfter = round($stockBefore + $qtyIn - $qtyOut, 3);

        if ($stockAfter < 0) {
            throw new Exception("Stok untuk produk '{$lockedProduct->name}' tidak mencukupi. Sisa stok: {$stockBefore}, dibutuhkan: {$qtyOut}.");
        }

        $lockedProduct->stock = $stockAfter;
        $lockedProduct->save();

        return StockMovement::create([
            'product_id' => $lockedProduct->id,
            'user_id' => $userId,
            'type' => $type,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'qty_in' => $qtyIn,
            'qty_out' => $qtyOut,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $notes,
        ]);
    }

    /**
     * Adjust product stock to an exact physical count.
     */
    public function adjustStock(
        Product|int $product,
        float|int $physicalStock,
        ?int $userId = null,
        string $type = 'adjustment',
        ?string $refType = null,
        ?int $refId = null,
        ?string $notes = null
    ): StockMovement {
        $productId = $product instanceof Product ? $product->id : $product;
        $lockedProduct = Product::where('id', $productId)->lockForUpdate()->firstOrFail();

        $systemStock = (float) $lockedProduct->stock;
        $difference = round($physicalStock - $systemStock, 3);

        if ($difference > 0) {
            return $this->recordMovement($lockedProduct, $type, $difference, 0, $userId, $refType, $refId, $notes ?? 'Penyesuaian stok bertambah');
        } elseif ($difference < 0) {
            return $this->recordMovement($lockedProduct, $type, 0, abs($difference), $userId, $refType, $refId, $notes ?? 'Penyesuaian stok berkurang');
        }

        // Difference is 0, just return dummy movement or log
        return StockMovement::create([
            'product_id' => $lockedProduct->id,
            'user_id' => $userId,
            'type' => $type,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'qty_in' => 0,
            'qty_out' => 0,
            'stock_before' => $systemStock,
            'stock_after' => $systemStock,
            'notes' => $notes ?? 'Opname fisik cocok dengan sistem',
        ]);
    }
}
