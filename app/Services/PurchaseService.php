<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Setting;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        protected StockService $stockService,
        protected CashService $cashService
    ) {}

    public function generatePurchaseNumber(): string
    {
        $prefix = Setting::get('purchase_prefix', 'PO');
        $date = Carbon::now()->format('Ymd');
        $count = Purchase::whereDate('created_at', Carbon::today())->count() + 1;
        $formattedCount = str_pad((string) $count, 4, '0', STR_PAD_LEFT);

        $number = "{$prefix}-{$date}-{$formattedCount}";

        while (Purchase::where('purchase_number', $number)->exists()) {
            $count++;
            $formattedCount = str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            $number = "{$prefix}-{$date}-{$formattedCount}";
        }

        return $number;
    }

    /**
     * Create purchase transaction atomically.
     *
     * @throws Exception
     */
    public function createPurchase(array $data, int $userId): Purchase
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = $data['items'] ?? [];
            if (empty($items)) {
                throw new Exception('Daftar barang pembelian tidak boleh kosong.');
            }

            $purchaseNumber = $this->generatePurchaseNumber();
            $purchaseDate = !empty($data['purchase_date']) ? Carbon::parse($data['purchase_date']) : Carbon::now();

            $subtotal = 0;
            $itemsToProcess = [];

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $qty = (int) ($item['qty'] ?? 1);
                $costPrice = (float) ($item['cost_price'] ?? 0);
                $discount = (float) ($item['discount'] ?? 0);

                if ($qty <= 0) {
                    throw new Exception('Jumlah kuantitas pembelian harus lebih besar dari 0.');
                }

                $product = Product::where('id', $productId)->lockForUpdate()->firstOrFail();
                $lineSubtotal = ($costPrice * $qty) - $discount;
                if ($lineSubtotal < 0) {
                    $lineSubtotal = 0;
                }

                $subtotal += $lineSubtotal;

                $itemsToProcess[] = [
                    'product' => $product,
                    'cost_price' => $costPrice,
                    'qty' => $qty,
                    'discount_amount' => $discount,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $overallDiscount = (float) ($data['discount_amount'] ?? 0);
            $grandTotal = max(0, $subtotal - $overallDiscount);
            $paidAmount = (float) ($data['paid_amount'] ?? $grandTotal);
            $paymentMethod = $data['payment_method'] ?? 'cash';

            $paymentStatus = 'paid';
            if ($paidAmount <= 0) {
                $paymentStatus = 'unpaid';
            } elseif ($paidAmount < $grandTotal) {
                $paymentStatus = 'partial';
            }

            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'user_id' => $userId,
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $purchaseDate,
                'subtotal' => $subtotal,
                'discount_amount' => $overallDiscount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'due_date' => !empty($data['due_date']) ? Carbon::parse($data['due_date']) : null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($itemsToProcess as $pItem) {
                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $pItem['product']->id,
                    'cost_price' => $pItem['cost_price'],
                    'qty' => $pItem['qty'],
                    'discount_amount' => $pItem['discount_amount'],
                    'subtotal' => $pItem['subtotal'],
                ]);

                // Update product cost price (HPP) with newest purchase price
                $pItem['product']->cost_price = $pItem['cost_price'];
                $pItem['product']->save();

                // Add to stock
                $this->stockService->recordMovement(
                    product: $pItem['product'],
                    type: 'purchase',
                    qtyIn: $pItem['qty'],
                    qtyOut: 0,
                    userId: $userId,
                    refType: Purchase::class,
                    refId: $purchase->id,
                    notes: "Pembelian Barang Masuk #{$purchaseNumber}"
                );
            }

            // If cash was paid and there is an active shift or user cashflow
            if ($paidAmount > 0 && $paymentMethod === 'cash') {
                $currentShift = CashShift::where('user_id', $userId)->where('status', 'open')->first();
                $this->cashService->recordTransaction(
                    cashShiftId: $currentShift?->id,
                    userId: $userId,
                    type: 'out',
                    category: 'purchase',
                    amount: $paidAmount,
                    refType: Purchase::class,
                    refId: $purchase->id,
                    description: "Pembelian Barang ke Supplier Tunai #{$purchaseNumber}"
                );
            }

            return $purchase->load(['details.product', 'supplier', 'user']);
        });
    }
}
