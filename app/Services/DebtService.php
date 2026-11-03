<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\PayablePayment;
use App\Models\PayablePaymentAllocation;
use App\Models\Purchase;
use App\Models\ReceivablePayment;
use App\Models\ReceivablePaymentAllocation;
use App\Models\Sale;
use App\Models\Supplier;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class DebtService
{
    public function __construct(
        protected CashService $cashService
    ) {}

    public function generateReceivablePaymentNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $count = ReceivablePayment::whereDate('created_at', Carbon::today())->count() + 1;
        $formattedCount = str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        $number = "RCV-{$date}-{$formattedCount}";

        while (ReceivablePayment::where('payment_number', $number)->exists()) {
            $count++;
            $formattedCount = str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            $number = "RCV-{$date}-{$formattedCount}";
        }

        return $number;
    }

    public function generatePayablePaymentNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $count = PayablePayment::whereDate('created_at', Carbon::today())->count() + 1;
        $formattedCount = str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        $number = "PAY-{$date}-{$formattedCount}";

        while (PayablePayment::where('payment_number', $number)->exists()) {
            $count++;
            $formattedCount = str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            $number = "PAY-{$date}-{$formattedCount}";
        }

        return $number;
    }

    /**
     * Pay customer receivables in bulk (FIFO allocation across unpaid/partial sales).
     *
     * @throws Exception
     */
    public function payCustomerReceivable(Customer $customer, array $data, int $userId): ReceivablePayment
    {
        return DB::transaction(function () use ($customer, $data, $userId) {
            $amount = round((float) ($data['amount'] ?? 0), 2);
            if ($amount <= 0) {
                throw new Exception('Nominal pembayaran piutang harus lebih besar dari Rp 0.');
            }

            $paymentMethod = $data['payment_method'] ?? 'cash';
            $paymentDate = !empty($data['payment_date']) ? Carbon::parse($data['payment_date']) : Carbon::now();

            $salesQuery = Sale::where('customer_id', $customer->id)
                ->where('status', 'completed')
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereRaw('grand_total > paid_amount');

            if (!empty($data['sale_id'])) {
                $salesQuery->where('id', (int) $data['sale_id']);
            }

            $unpaidSales = $salesQuery
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            if ($unpaidSales->isEmpty()) {
                throw new Exception("Pelanggan {$customer->name} tidak memiliki tagihan piutang yang belum lunas.");
            }

            $totalRemainingDebt = (float) $unpaidSales->sum(fn (Sale $s) => max(0, (float) $s->grand_total - (float) $s->paid_amount));

            if ($amount > $totalRemainingDebt) {
                $formattedMax = number_format($totalRemainingDebt, 0, ',', '.');
                throw new Exception("Nominal pembayaran melebihi total sisa hutang pelanggan (Maksimal: Rp {$formattedMax}).");
            }

            $currentShift = CashShift::where('user_id', $userId)->where('status', 'open')->first();
            $paymentNumber = $this->generateReceivablePaymentNumber();

            $payment = ReceivablePayment::create([
                'payment_number' => $paymentNumber,
                'customer_id' => $customer->id,
                'user_id' => $userId,
                'cash_shift_id' => $currentShift?->id,
                'payment_date' => $paymentDate,
                'total_paid' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $data['notes'] ?? null,
            ]);

            $remainingToAllocate = $amount;

            foreach ($unpaidSales as $sale) {
                if ($remainingToAllocate <= 0) {
                    break;
                }

                $saleDebt = max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
                if ($saleDebt <= 0) {
                    continue;
                }

                $allocated = min($remainingToAllocate, $saleDebt);

                ReceivablePaymentAllocation::create([
                    'receivable_payment_id' => $payment->id,
                    'sale_id' => $sale->id,
                    'amount' => $allocated,
                ]);

                $newPaidAmount = round((float) $sale->paid_amount + $allocated, 2);
                $newStatus = $newPaidAmount >= (float) $sale->grand_total ? 'paid' : 'partial';

                $sale->update([
                    'paid_amount' => $newPaidAmount,
                    'payment_status' => $newStatus,
                ]);

                $remainingToAllocate = round($remainingToAllocate - $allocated, 2);
            }

            if ($paymentMethod === 'cash') {
                $this->cashService->recordTransaction(
                    cashShiftId: $currentShift?->id,
                    userId: $userId,
                    type: 'in',
                    category: 'receivable_payment',
                    amount: $amount,
                    refType: ReceivablePayment::class,
                    refId: $payment->id,
                    description: "Pembayaran Piutang Pelanggan {$customer->name} #{$paymentNumber}"
                );
            }

            return $payment->load(['customer', 'user', 'allocations.sale']);
        });
    }

    /**
     * Pay supplier payables in bulk (FIFO allocation across unpaid/partial purchases).
     *
     * @throws Exception
     */
    public function paySupplierPayable(Supplier $supplier, array $data, int $userId): PayablePayment
    {
        return DB::transaction(function () use ($supplier, $data, $userId) {
            $amount = round((float) ($data['amount'] ?? 0), 2);
            if ($amount <= 0) {
                throw new Exception('Nominal pembayaran hutang harus lebih besar dari Rp 0.');
            }

            $paymentMethod = $data['payment_method'] ?? 'cash';
            $paymentDate = !empty($data['payment_date']) ? Carbon::parse($data['payment_date']) : Carbon::now();

            $purchasesQuery = Purchase::where('supplier_id', $supplier->id)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereRaw('grand_total > paid_amount');

            if (!empty($data['purchase_id'])) {
                $purchasesQuery->where('id', (int) $data['purchase_id']);
            }

            $unpaidPurchases = $purchasesQuery
                ->orderBy('purchase_date', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            if ($unpaidPurchases->isEmpty()) {
                throw new Exception("Tidak ada tagihan PO belum lunas untuk supplier {$supplier->name}.");
            }

            $totalRemainingDebt = (float) $unpaidPurchases->sum(fn (Purchase $p) => max(0, (float) $p->grand_total - (float) $p->paid_amount));

            if ($amount > $totalRemainingDebt) {
                $formattedMax = number_format($totalRemainingDebt, 0, ',', '.');
                throw new Exception("Nominal pembayaran melebihi total sisa hutang ke supplier (Maksimal: Rp {$formattedMax}).");
            }

            $currentShift = CashShift::where('user_id', $userId)->where('status', 'open')->first();
            $paymentNumber = $this->generatePayablePaymentNumber();

            $payment = PayablePayment::create([
                'payment_number' => $paymentNumber,
                'supplier_id' => $supplier->id,
                'user_id' => $userId,
                'cash_shift_id' => $currentShift?->id,
                'payment_date' => $paymentDate,
                'total_paid' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $data['notes'] ?? null,
            ]);

            $remainingToAllocate = $amount;

            foreach ($unpaidPurchases as $purchase) {
                if ($remainingToAllocate <= 0) {
                    break;
                }

                $purchaseDebt = max(0, (float) $purchase->grand_total - (float) $purchase->paid_amount);
                if ($purchaseDebt <= 0) {
                    continue;
                }

                $allocated = min($remainingToAllocate, $purchaseDebt);

                PayablePaymentAllocation::create([
                    'payable_payment_id' => $payment->id,
                    'purchase_id' => $purchase->id,
                    'amount' => $allocated,
                ]);

                $newPaidAmount = round((float) $purchase->paid_amount + $allocated, 2);
                $newStatus = $newPaidAmount >= (float) $purchase->grand_total ? 'paid' : 'partial';

                $purchase->update([
                    'paid_amount' => $newPaidAmount,
                    'payment_status' => $newStatus,
                ]);

                $remainingToAllocate = round($remainingToAllocate - $allocated, 2);
            }

            if ($paymentMethod === 'cash') {
                $this->cashService->recordTransaction(
                    cashShiftId: $currentShift?->id,
                    userId: $userId,
                    type: 'out',
                    category: 'payable_payment',
                    amount: $amount,
                    refType: PayablePayment::class,
                    refId: $payment->id,
                    description: "Pembayaran Hutang Supplier {$supplier->name} #{$paymentNumber}"
                );
            }

            return $payment->load(['supplier', 'user', 'allocations.purchase']);
        });
    }
}

