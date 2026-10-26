<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\CashTransaction;
use App\Models\Sale;
use App\Models\SaleReturn;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class CashService
{
    /**
     * Open a new cashier shift.
     *
     * @throws Exception
     */
    public function openShift(int $userId, float $openingCash, ?string $notes = null): CashShift
    {
        // Check if there is already an open shift for this user
        $existing = CashShift::where('user_id', $userId)->where('status', 'open')->first();
        if ($existing) {
            throw new Exception('Anda masih memiliki sesi shift kasir yang aktif. Harap tutup shift sebelumnya terlebih dahulu.');
        }

        return CashShift::create([
            'user_id' => $userId,
            'opening_cash' => $openingCash,
            'opened_at' => Carbon::now(),
            'status' => 'open',
            'notes' => $notes,
        ]);
    }

    /**
     * Calculate expected cash balance in drawer for a shift.
     */
    public function calculateShiftSummary(CashShift $shift): array
    {
        // 1. Opening Cash
        $opening = (float) $shift->opening_cash;

        // 2. Sales in Cash during shift
        $cashSales = (float) Sale::where('cash_shift_id', $shift->id)
            ->where('payment_method', 'cash')
            ->where('status', 'completed')
            ->sum('paid_amount') - (float) Sale::where('cash_shift_id', $shift->id)
            ->where('payment_method', 'cash')
            ->where('status', 'completed')
            ->sum('change_amount');

        // Non cash sales (Transfer, QRIS, Debit, dll.)
        $nonCashSales = (float) Sale::where('cash_shift_id', $shift->id)
            ->where('payment_method', '!=', 'cash')
            ->where('status', 'completed')
            ->sum('grand_total');

        // 3. Cash In transactions (modal tambah, dll.)
        $cashIn = (float) CashTransaction::where('cash_shift_id', $shift->id)
            ->where('type', 'in')
            ->where('category', '!=', 'sale')
            ->sum('amount');

        // 4. Cash Out transactions (beban, pengeluaran kas, dll.)
        $cashOut = (float) CashTransaction::where('cash_shift_id', $shift->id)
            ->where('type', 'out')
            ->sum('amount');

        // 5. Cash Refunds from Returns
        $cashRefunds = (float) SaleReturn::where('cash_shift_id', $shift->id)
            ->where('refund_method', 'cash')
            ->sum('total_refund');

        $expectedCash = $opening + $cashSales + $cashIn - $cashOut - $cashRefunds;

        return [
            'opening_cash' => $opening,
            'cash_sales' => $cashSales,
            'non_cash_sales' => $nonCashSales,
            'total_sales' => $cashSales + $nonCashSales,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'cash_refunds' => $cashRefunds,
            'expected_cash' => $expectedCash,
        ];
    }

    /**
     * Close a cashier shift with physical cash count.
     */
    public function closeShift(CashShift $shift, float $actualCash, ?string $notes = null): CashShift
    {
        $summary = $this->calculateShiftSummary($shift);
        $expectedCash = $summary['expected_cash'];
        $difference = $actualCash - $expectedCash;

        $shift->update([
            'closing_cash_system' => $expectedCash,
            'closing_cash_actual' => $actualCash,
            'difference' => $difference,
            'closed_at' => Carbon::now(),
            'status' => 'closed',
            'notes' => $notes ?? $shift->notes,
        ]);

        return $shift;
    }

    /**
     * Record a manual or automated cash transaction.
     */
    public function recordTransaction(
        ?int $cashShiftId,
        int $userId,
        string $type,
        string $category,
        float $amount,
        ?string $refType = null,
        ?int $refId = null,
        ?string $description = null
    ): CashTransaction {
        return CashTransaction::create([
            'cash_shift_id' => $cashShiftId,
            'user_id' => $userId,
            'type' => $type,
            'category' => $category,
            'amount' => $amount,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'description' => $description,
            'transaction_date' => Carbon::now(),
        ]);
    }
}
