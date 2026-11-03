<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ReceivablePayment;
use App\Models\ReceivablePaymentAllocation;
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

            $customerId = !empty($data['customer_id']) ? (int) $data['customer_id'] : null;
            $customer = $customerId ? Customer::find($customerId) : null;
            $customerLevel = $customer?->member_level;

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

                // Support custom selling price or fallback to member tier price / default product price
                $defaultPrice = $product->getPriceForLevel($customerLevel);
                $sellingPrice = isset($item['selling_price']) && is_numeric($item['selling_price']) && (float) $item['selling_price'] >= 0
                    ? (float) $item['selling_price']
                    : $defaultPrice;

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
            $paidAmount = isset($data['paid_amount']) ? (float) $data['paid_amount'] : $grandTotal;
            $customerId = !empty($data['customer_id']) ? (int) $data['customer_id'] : null;
            $dpPaymentMethod = null;

            if ($paymentMethod === 'debt') {
                if (!$customerId) {
                    throw new Exception('Transaksi hutang/tempo wajib memilih pelanggan terdaftar (tidak boleh Pelanggan Umum).');
                }
                if ($paidAmount < 0) {
                    $paidAmount = 0;
                }
                if ($paidAmount <= 0) {
                    $paymentStatus = 'unpaid';
                    $changeAmount = 0;
                } elseif ($paidAmount < $grandTotal) {
                    $paymentStatus = 'partial';
                    $changeAmount = 0;
                    $dpPaymentMethod = $data['dp_payment_method'] ?? 'cash';
                } else {
                    $paymentStatus = 'paid';
                    $changeAmount = $paidAmount - $grandTotal;
                    $paidAmount = $grandTotal;
                    $dpPaymentMethod = $data['dp_payment_method'] ?? 'cash';
                }
            } else {
                if ($paymentMethod === 'cash' && $paidAmount < $grandTotal) {
                    throw new Exception("Jumlah pembayaran tunai (Rp " . number_format($paidAmount, 0, ',', '.') . ") kurang dari grand total (Rp " . number_format($grandTotal, 0, ',', '.') . ").");
                }
                $paymentStatus = 'paid';
                $changeAmount = $paymentMethod === 'cash' ? max(0, $paidAmount - $grandTotal) : 0;
            }

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
                'dp_payment_method' => $dpPaymentMethod,
                'payment_status' => $paymentStatus,
                'due_date' => !empty($data['due_date']) ? Carbon::parse($data['due_date']) : null,
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

            // Record cash in if cash payment or DP on debt
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
            } elseif ($paymentMethod === 'debt' && $paidAmount > 0) {
                $dpAmount = min($paidAmount, $grandTotal);
                $effectiveDpMethod = $dpPaymentMethod ?? 'cash';

                // Record DP as initial ReceivablePayment & Allocation so payment history is complete
                $dateStr = Carbon::now()->format('Ymd');
                $pmtCount = ReceivablePayment::whereDate('created_at', Carbon::today())->count() + 1;
                $pmtNumber = 'RCV-' . $dateStr . '-' . str_pad((string) $pmtCount, 4, '0', STR_PAD_LEFT);
                while (ReceivablePayment::where('payment_number', $pmtNumber)->exists()) {
                    $pmtCount++;
                    $pmtNumber = 'RCV-' . $dateStr . '-' . str_pad((string) $pmtCount, 4, '0', STR_PAD_LEFT);
                }

                $dpPayment = ReceivablePayment::create([
                    'payment_number' => $pmtNumber,
                    'customer_id' => $customerId,
                    'user_id' => $userId,
                    'cash_shift_id' => $currentShift?->id,
                    'payment_date' => $transactionDate,
                    'total_paid' => $dpAmount,
                    'payment_method' => $effectiveDpMethod,
                    'notes' => "Uang Muka (DP) saat transaksi Nota #{$invoiceNumber}",
                ]);

                ReceivablePaymentAllocation::create([
                    'receivable_payment_id' => $dpPayment->id,
                    'sale_id' => $sale->id,
                    'amount' => $dpAmount,
                ]);

                // Only record physical cash drawer transaction if DP was paid in cash
                if ($effectiveDpMethod === 'cash') {
                    $this->cashService->recordTransaction(
                        cashShiftId: $currentShift?->id,
                        userId: $userId,
                        type: 'in',
                        category: 'receivable_dp',
                        amount: $dpAmount,
                        refType: Sale::class,
                        refId: $sale->id,
                        description: "DP Tunai Penjualan Tempo #{$invoiceNumber}"
                    );
                }
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
