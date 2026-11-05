<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\PayablePayment;
use App\Models\PayablePaymentAllocation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Models\ReceivablePayment;
use App\Models\ReceivablePaymentAllocation;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetail;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function __construct(
        protected StockService $stockService,
        protected CashService $cashService,
        protected DebtService $debtService
    ) {}

    public function generateSaleReturnNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $count = SaleReturn::whereDate('created_at', Carbon::today())->count() + 1;
        $formatted = str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        return "RET-JUAL-{$date}-{$formatted}";
    }

    public function generatePurchaseReturnNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $count = PurchaseReturn::whereDate('created_at', Carbon::today())->count() + 1;
        $formatted = str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        return "RET-BELI-{$date}-{$formatted}";
    }

    /**
     * Process customer sale return.
     *
     * @throws Exception
     */
    public function processSaleReturn(array $data, int $userId): SaleReturn
    {
        return DB::transaction(function () use ($data, $userId) {
            $sale = Sale::with('details')->findOrFail($data['sale_id']);
            $items = $data['items'] ?? [];

            if (empty($items)) {
                throw new Exception('Pilih setidaknya 1 barang untuk diretur.');
            }

            $currentShift = CashShift::where('user_id', $userId)->where('status', 'open')->first();
            $returnNumber = $this->generateSaleReturnNumber();
            $refundMethod = $data['refund_method'] ?? 'cash';
            $totalRefund = 0;
            $itemsToProcess = [];

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) ($item['qty'] ?? 0);
                $reason = $item['reason'] ?? 'Barang cacat/tukar';

                if ($qty <= 0) {
                    continue;
                }

                // Verify original sale detail
                $saleDetail = $sale->details->firstWhere('product_id', $productId);
                if (!$saleDetail) {
                    throw new Exception("Produk ID {$productId} tidak ditemukan dalam transaksi nota #{$sale->invoice_number}.");
                }

                // Check already returned qty for this sale item
                $alreadyReturned = (int) SaleReturnDetail::whereHas('saleReturn', function ($q) use ($sale) {
                    $q->where('sale_id', $sale->id);
                })->where('product_id', $productId)->sum('qty');

                $maxAllowed = $saleDetail->qty - $alreadyReturned;
                if ($qty > $maxAllowed) {
                    throw new Exception("Jumlah retur untuk '{$saleDetail->product_name}' melebihi batas (maksimal: {$maxAllowed}).");
                }

                $refundPrice = (float) $saleDetail->selling_price;
                $lineRefund = $refundPrice * $qty;
                $totalRefund += $lineRefund;

                $itemsToProcess[] = [
                    'product_id' => $productId,
                    'qty' => $qty,
                    'refund_price' => $refundPrice,
                    'subtotal' => $lineRefund,
                    'reason' => $reason,
                ];
            }

            if (empty($itemsToProcess)) {
                throw new Exception('Tidak ada item valid yang dipilih untuk retur.');
            }

            $saleReturn = SaleReturn::create([
                'return_number' => $returnNumber,
                'sale_id' => $sale->id,
                'user_id' => $userId,
                'cash_shift_id' => $currentShift?->id,
                'return_date' => Carbon::now(),
                'total_refund' => $totalRefund,
                'refund_method' => $refundMethod,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($itemsToProcess as $pItem) {
                SaleReturnDetail::create([
                    'sale_return_id' => $saleReturn->id,
                    'product_id' => $pItem['product_id'],
                    'qty' => $pItem['qty'],
                    'refund_price' => $pItem['refund_price'],
                    'subtotal' => $pItem['subtotal'],
                    'reason' => $pItem['reason'],
                ]);

                // Restore stock back into product inventory
                $this->stockService->recordMovement(
                    product: $pItem['product_id'],
                    type: 'sale_return',
                    qtyIn: $pItem['qty'],
                    qtyOut: 0,
                    userId: $userId,
                    refType: SaleReturn::class,
                    refId: $saleReturn->id,
                    notes: "Retur Penjualan #{$returnNumber} dari Nota #{$sale->invoice_number}"
                );
            }

            // 1. Jika nota penjualan masih memiliki sisa piutang (hutang pelanggan), otomatis potong sisa hutang terlebih dahulu
            $remainingDebt = max(0.0, (float) $sale->grand_total - (float) $sale->paid_amount);
            $debtReduced = 0.0;

            if ($refundMethod !== 'none' && $remainingDebt > 0 && $totalRefund > 0) {
                $debtReduced = min((float) $totalRefund, $remainingDebt);

                if ($sale->customer_id) {
                    $paymentNumber = $this->debtService->generateReceivablePaymentNumber();
                    $receivablePayment = ReceivablePayment::create([
                        'payment_number' => $paymentNumber,
                        'customer_id' => $sale->customer_id,
                        'user_id' => $userId,
                        'cash_shift_id' => $currentShift?->id,
                        'payment_date' => Carbon::now(),
                        'total_paid' => $debtReduced,
                        'payment_method' => 'return',
                        'notes' => "Potongan Retur Penjualan #{$returnNumber} (Nota #{$sale->invoice_number})",
                    ]);

                    ReceivablePaymentAllocation::create([
                        'receivable_payment_id' => $receivablePayment->id,
                        'sale_id' => $sale->id,
                        'amount' => $debtReduced,
                    ]);
                }

                $newPaidAmount = round((float) $sale->paid_amount + $debtReduced, 2);
                $newStatus = $newPaidAmount >= (float) $sale->grand_total ? 'paid' : 'partial';

                $sale->update([
                    'paid_amount' => $newPaidAmount,
                    'payment_status' => $newStatus,
                ]);
            }

            // 2. Kurangi kas laci kasir hanya sebesar sisa nilai refund setelah memotong hutang (jika metode = cash)
            $cashRefundAmount = max(0.0, round((float) $totalRefund - $debtReduced, 2));
            if ($refundMethod === 'cash' && $cashRefundAmount > 0) {
                $this->cashService->recordTransaction(
                    cashShiftId: $currentShift?->id,
                    userId: $userId,
                    type: 'out',
                    category: 'refund',
                    amount: $cashRefundAmount,
                    refType: SaleReturn::class,
                    refId: $saleReturn->id,
                    description: "Refund Kas Retur Penjualan #{$returnNumber}"
                );
            }

            return $saleReturn->load(['details.product', 'sale']);
        });
    }

    /**
     * Process return to supplier.
     *
     * @throws Exception
     */
    public function processPurchaseReturn(array $data, int $userId): PurchaseReturn
    {
        return DB::transaction(function () use ($data, $userId) {
            $purchase = Purchase::with('details')->findOrFail($data['purchase_id']);
            $items = $data['items'] ?? [];

            if (empty($items)) {
                throw new Exception('Pilih setidaknya 1 barang untuk retur pembelian.');
            }

            $returnNumber = $this->generatePurchaseReturnNumber();
            $totalAmount = 0;
            $itemsToProcess = [];

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) ($item['qty'] ?? 0);
                $reason = $item['reason'] ?? 'Rusak/kadaluarsa/retur supplier';

                if ($qty <= 0) {
                    continue;
                }

                $purchaseDetail = $purchase->details->firstWhere('product_id', $productId);
                if (!$purchaseDetail) {
                    throw new Exception("Produk ID {$productId} tidak ditemukan dalam PO #{$purchase->purchase_number}.");
                }

                $alreadyReturned = (int) PurchaseReturnDetail::whereHas('purchaseReturn', function ($q) use ($purchase) {
                    $q->where('purchase_id', $purchase->id);
                })->where('product_id', $productId)->sum('qty');

                $maxAllowed = $purchaseDetail->qty - $alreadyReturned;
                if ($qty > $maxAllowed) {
                    throw new Exception("Jumlah retur untuk produk ini melebihi batas pembelian (maksimal: {$maxAllowed}).");
                }

                $costPrice = (float) $purchaseDetail->cost_price;
                $subtotal = $costPrice * $qty;
                $totalAmount += $subtotal;

                $itemsToProcess[] = [
                    'product_id' => $productId,
                    'qty' => $qty,
                    'cost_price' => $costPrice,
                    'subtotal' => $subtotal,
                    'reason' => $reason,
                ];
            }

            if (empty($itemsToProcess)) {
                throw new Exception('Tidak ada item valid yang dipilih untuk retur pembelian.');
            }

            $purchaseReturn = PurchaseReturn::create([
                'return_number' => $returnNumber,
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'user_id' => $userId,
                'return_date' => Carbon::now(),
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($itemsToProcess as $pItem) {
                PurchaseReturnDetail::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'product_id' => $pItem['product_id'],
                    'qty' => $pItem['qty'],
                    'cost_price' => $pItem['cost_price'],
                    'subtotal' => $pItem['subtotal'],
                    'reason' => $pItem['reason'],
                ]);

                // Reduce inventory stock
                $this->stockService->recordMovement(
                    product: $pItem['product_id'],
                    type: 'purchase_return',
                    qtyIn: 0,
                    qtyOut: $pItem['qty'],
                    userId: $userId,
                    refType: PurchaseReturn::class,
                    refId: $purchaseReturn->id,
                    notes: "Retur Pembelian ke Supplier #{$returnNumber}"
                );
            }

            // Jika faktur PO masih memiliki sisa hutang ke supplier, otomatis potong sisa hutang PO tersebut
            $remainingPayable = max(0.0, (float) $purchase->grand_total - (float) $purchase->paid_amount);
            if ($remainingPayable > 0 && $totalAmount > 0) {
                $payableReduced = min((float) $totalAmount, $remainingPayable);

                if ($purchase->supplier_id) {
                    $paymentNumber = $this->debtService->generatePayablePaymentNumber();
                    $payablePayment = PayablePayment::create([
                        'payment_number' => $paymentNumber,
                        'supplier_id' => $purchase->supplier_id,
                        'user_id' => $userId,
                        'cash_shift_id' => null,
                        'payment_date' => Carbon::now(),
                        'total_paid' => $payableReduced,
                        'payment_method' => 'return',
                        'notes' => "Potongan Retur Pembelian #{$returnNumber} (PO #{$purchase->purchase_number})",
                    ]);

                    PayablePaymentAllocation::create([
                        'payable_payment_id' => $payablePayment->id,
                        'purchase_id' => $purchase->id,
                        'amount' => $payableReduced,
                    ]);
                }

                $newPaidAmount = round((float) $purchase->paid_amount + $payableReduced, 2);
                $newStatus = $newPaidAmount >= (float) $purchase->grand_total ? 'paid' : 'partial';

                $purchase->update([
                    'paid_amount' => $newPaidAmount,
                    'payment_status' => $newStatus,
                ]);
            }

            return $purchaseReturn->load(['details.product', 'supplier']);
        });
    }
}
