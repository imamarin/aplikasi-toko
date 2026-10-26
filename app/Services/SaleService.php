<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Setting;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        protected StockService $stockService,
        protected CashService $cashService
    ) {}

    /**
     * Generate sequential invoice number like INV-20261013-0001.
     */
    public function generateInvoiceNumber(): string
    {
        $prefix = Setting::get('invoice_prefix', 'INV');
        $date = Carbon::now()->format('Ymd');
        $count = Sale::whereDate('created_at', Carbon::today())->count() + 1;
        $formattedCount = str_pad((string) $count, 4, '0', STR_PAD_LEFT);

        $number = "{$prefix}-{$date}-{$formattedCount}";

        // Ensure unique
        while (Sale::where('invoice_number', $number)->exists()) {
            $count++;
            $formattedCount = str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            $number = "{$prefix}-{$date}-{$formattedCount}";
        }

        return $number;
    }

    /**
     * Process POS sale checkout atomically inside DB transaction.
     *
     * @throws Exception
     */
    public function processCheckout(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = $data['items'] ?? [];
            if (empty($items)) {
                throw new Exception('Keranjang belanja kosong. Silakan pilih minimal 1 produk.');
            }

            // Find open shift if any
            $currentShift = CashShift::where('user_id', $userId)->where('status', 'open')->first();

            $invoiceNumber = $this->generateInvoiceNumber();
            $transactionDate = Carbon::now();

            $subtotal = 0;
            $itemsToProcess = [];

            // Calculate and validate stock first
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $qty = (float) ($item['qty'] ?? 1);
                $itemDiscount = (float) ($item['discount'] ?? 0);

                if ($qty <= 0) {
                    throw new Exception("Jumlah kuantitas untuk produk tidak valid.");
                }

                $product = Product::where('id', $productId)->lockForUpdate()->firstOrFail();

                if ((float) $product->stock < $qty) {
                    $stockFormatted = rtrim(rtrim(number_format((float) $product->stock, 3, ',', '.'), '0'), ',');
                    $qtyFormatted = rtrim(rtrim(number_format($qty, 3, ',', '.'), '0'), ',');
                    throw new Exception("Stok untuk '{$product->name}' tidak mencukupi (sisa: {$stockFormatted}, diminta: {$qtyFormatted}).");
                }

                // Support custom selling price or fallback to default product price
                $sellingPrice = isset($item['selling_price']) && is_numeric($item['selling_price']) && (float) $item['selling_price'] >= 0
                    ? (float) $item['selling_price']
                    : (float) $product->selling_price;

                // Support custom line subtotal if provided (e.g., buying by nominal Rupiah)
                if (isset($item['subtotal']) && is_numeric($item['subtotal']) && (float) $item['subtotal'] >= 0) {
                    $lineSubtotal = (float) $item['subtotal'];
                } else {
                    $lineSubtotal = round(($sellingPrice * $qty) - $itemDiscount, 2);
                }

                if ($lineSubtotal < 0) {
                    $lineSubtotal = 0;
                }

                $subtotal += $lineSubtotal;

                $itemsToProcess[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'selling_price' => $sellingPrice,
                    'cost_price' => (float) $product->cost_price,
                    'discount_amount' => $itemDiscount,
                    'subtotal' => $lineSubtotal,
                ];
            }

            // Overall discounts
            $discountType = $data['discount_type'] ?? 'fixed';
            $discountValue = (float) ($data['discount_value'] ?? 0);
            $discountAmount = 0;

            if ($discountType === 'percentage') {
                $discountAmount = ($subtotal * $discountValue) / 100;
            } else {
                $discountAmount = $discountValue;
            }

            if ($discountAmount > $subtotal) {
                $discountAmount = $subtotal;
            }

            $afterDiscount = $subtotal - $discountAmount;

            // Taxes
            $taxPercentage = (float) ($data['tax_percentage'] ?? Setting::get('tax_percentage', 0));
            $taxAmount = ($afterDiscount * $taxPercentage) / 100;

            // Shipping Cost (Ongkos Kirim) - recorded separately from product sales
            $rawShipping = $data['shipping_cost'] ?? 0;
            $shippingCost = (float) str_replace(['Rp', 'rp', ' ', '.'], '', (string) $rawShipping);
            if ($shippingCost < 0) {
                $shippingCost = 0.0;
            }

            $grandTotal = round($afterDiscount + $taxAmount + $shippingCost);
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $paidAmount = (float) ($data['paid_amount'] ?? $grandTotal);

            if ($paymentMethod === 'cash' && $paidAmount < $grandTotal) {
                throw new Exception("Jumlah pembayaran tunai (Rp " . number_format($paidAmount, 0, ',', '.') . ") kurang dari grand total (Rp " . number_format($grandTotal, 0, ',', '.') . ").");
            }

            $changeAmount = $paymentMethod === 'cash' ? max(0, $paidAmount - $grandTotal) : 0;
            $customerId = !empty($data['customer_id']) ? (int) $data['customer_id'] : null;

            // Create Sale Header
            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $userId,
                'customer_id' => $customerId,
                'cash_shift_id' => $currentShift?->id,
                'transaction_date' => $transactionDate,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'tax_percentage' => $taxPercentage,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $paymentMethod,
                'payment_status' => 'paid',
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            // Save details & deduct stock
            foreach ($itemsToProcess as $pItem) {
                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $pItem['product']->id,
                    'product_name' => $pItem['product']->name,
                    'cost_price' => $pItem['cost_price'],
                    'selling_price' => $pItem['selling_price'],
                    'qty' => $pItem['qty'],
                    'discount_amount' => $pItem['discount_amount'],
                    'subtotal' => $pItem['subtotal'],
                ]);

                // Record stock movement (out)
                $this->stockService->recordMovement(
                    product: $pItem['product'],
                    type: 'sale',
                    qtyIn: 0,
                    qtyOut: $pItem['qty'],
                    userId: $userId,
                    refType: Sale::class,
                    refId: $sale->id,
                    notes: "Penjualan Nota #{$invoiceNumber}"
                );
            }

            // Record cash in if cash payment
            if ($paymentMethod === 'cash') {
                $netCashReceived = $grandTotal; // Cash effectively retained
                $this->cashService->recordTransaction(
                    cashShiftId: $currentShift?->id,
                    userId: $userId,
                    type: 'in',
                    category: 'sale',
                    amount: $netCashReceived,
                    refType: Sale::class,
                    refId: $sale->id,
                    description: "Penjualan POS Tunai #{$invoiceNumber}"
                );
            }

            // Reward Customer Points (e.g. 1 point for every 10.000 spent)
            if ($customerId) {
                $customer = Customer::find($customerId);
                if ($customer) {
                    $earnedPoints = (int) floor($grandTotal / 10000);
                    if ($earnedPoints > 0) {
                        $customer->increment('points', $earnedPoints);
                    }
                }
            }

            return $sale->load(['details.product', 'customer', 'user']);
        });
    }
}
