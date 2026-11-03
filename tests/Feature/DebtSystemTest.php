<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_pos_debt_checkout_requires_registered_customer(): void
    {
        $admin = User::where('username', 'admin')->first();
        $product = Product::first();

        $response = $this->actingAs($admin)->postJson(route('pos.checkout'), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                    'selling_price' => 10000,
                    'subtotal' => 10000,
                    'discount' => 0,
                ],
            ],
            'customer_id' => null,
            'paid_amount' => 0,
            'payment_method' => 'debt',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_bulk_customer_receivable_payment_allocates_fifo_across_multiple_invoices(): void
    {
        $admin = User::where('username', 'admin')->first();
        $customer = Customer::first();
        $product = Product::first();
        $product->update(['stock' => 100]);

        // Invoice 1: Total 100.000, Paid 0 (Unpaid)
        $res1 = $this->actingAs($admin)->postJson(route('pos.checkout'), [
            'items' => [[
                'product_id' => $product->id,
                'qty' => 1,
                'selling_price' => 100000,
                'subtotal' => 100000,
                'discount' => 0,
            ]],
            'customer_id' => $customer->id,
            'paid_amount' => 0,
            'payment_method' => 'debt',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);
        $res1->assertStatus(200);
        $sale1 = Sale::find($res1->json('sale_id'));
        $this->assertEquals('unpaid', $sale1->payment_status);
        $this->assertEquals(100000, $sale1->remaining_debt);

        // Invoice 2: Total 200.000, Paid 0 (Unpaid)
        $res2 = $this->actingAs($admin)->postJson(route('pos.checkout'), [
            'items' => [[
                'product_id' => $product->id,
                'qty' => 2,
                'selling_price' => 100000,
                'subtotal' => 200000,
                'discount' => 0,
            ]],
            'customer_id' => $customer->id,
            'paid_amount' => 0,
            'payment_method' => 'debt',
            'due_date' => now()->addDays(14)->toDateString(),
        ]);
        $res2->assertStatus(200);
        $sale2 = Sale::find($res2->json('sale_id'));
        $this->assertEquals('unpaid', $sale2->payment_status);

        // Customer pays Rp 250.000 in bulk -> Sale 1 (100k) becomes PAID, Sale 2 (200k) gets 150k (PARTIAL, remaining 50k)
        $payResponse = $this->actingAs($admin)->post(route('receivables.pay', $customer->id), [
            'amount' => '250.000',
            'payment_method' => 'cash',
            'notes' => 'Bayar sekaligus 2 nota',
        ]);

        $payResponse->assertRedirect(route('receivables.show', $customer->id));

        $sale1->refresh();
        $sale2->refresh();

        $this->assertEquals('paid', $sale1->payment_status);
        $this->assertEquals(100000, (float) $sale1->paid_amount);
        $this->assertEquals(0, $sale1->remaining_debt);

        $this->assertEquals('partial', $sale2->payment_status);
        $this->assertEquals(150000, (float) $sale2->paid_amount);
        $this->assertEquals(50000, $sale2->remaining_debt);

        // Verify pages render properly
        $this->actingAs($admin)->get(route('receivables.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('receivables.show', $customer->id))->assertStatus(200);
    }

    public function test_bulk_supplier_payable_payment_allocates_fifo_across_multiple_purchases(): void
    {
        $admin = User::where('username', 'admin')->first();
        $supplier = Supplier::first();
        $product = Product::first();

        // PO 1: Total 300.000, Paid 0
        $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->subDays(5)->toDateString(),
            'items' => [[
                'product_id' => $product->id,
                'qty' => 3,
                'cost_price' => 100000,
                'discount' => 0,
            ]],
            'paid_amount' => 0,
            'payment_method' => 'credit',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        // PO 2: Total 500.000, Paid 0
        $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->subDays(2)->toDateString(),
            'items' => [[
                'product_id' => $product->id,
                'qty' => 5,
                'cost_price' => 100000,
                'discount' => 0,
            ]],
            'paid_amount' => 0,
            'payment_method' => 'credit',
            'due_date' => now()->addDays(14)->toDateString(),
        ]);

        $unpaidPOs = Purchase::where('supplier_id', $supplier->id)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->orderBy('purchase_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $this->assertGreaterThanOrEqual(2, $unpaidPOs->count());
        $po1 = $unpaidPOs->first();
        $po2 = $unpaidPOs->skip(1)->first();

        $payAmount = (float) $po1->remaining_debt + 200000;

        $payResponse = $this->actingAs($admin)->post(route('payables.pay', $supplier->id), [
            'amount' => (string) $payAmount,
            'payment_method' => 'transfer',
            'notes' => 'Transfer pelunasan bertahap',
        ]);

        $payResponse->assertRedirect(route('payables.show', $supplier->id));

        $po1->refresh();
        $po2->refresh();

        $this->assertEquals('paid', $po1->payment_status);
        $this->assertEquals(0, $po1->remaining_debt);
        $this->assertEquals('partial', $po2->payment_status);

        $this->actingAs($admin)->get(route('payables.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('payables.show', $supplier->id))->assertStatus(200);
    }

    public function test_cash_shift_reconciliation_page_displays_sales_details(): void
    {
        $admin = User::where('username', 'admin')->first();
        $product = Product::first();
        $product->update(['stock' => 50]);

        $this->actingAs($admin)->post(route('cash-shifts.open'), [
            'opening_cash' => '200000',
            'notes' => 'Shift Pagi',
        ]);

        $shift = $admin->fresh()->currentOpenShift;
        $this->assertNotNull($shift);

        $saleRes = $this->actingAs($admin)->postJson(route('pos.checkout'), [
            'items' => [[
                'product_id' => $product->id,
                'qty' => 2,
                'selling_price' => 25000,
                'subtotal' => 50000,
                'discount' => 0,
            ]],
            'paid_amount' => 50000,
            'payment_method' => 'cash',
        ]);
        $saleRes->assertStatus(200);
        $invoiceNumber = $saleRes->json('invoice_number');

        $this->actingAs($admin)->post(route('cash-shifts.close'), [
            'cash_shift_id' => $shift->id,
            'closing_cash_actual' => '250000',
            'notes' => 'Closing Shift Pagi',
        ]);

        $showResponse = $this->actingAs($admin)->get(route('cash-shifts.show', $shift->id));
        $showResponse->assertStatus(200)
            ->assertSee($invoiceNumber)
            ->assertSee($product->name)
            ->assertSee('Rincian Transaksi Penjualan Selama Shift');
    }

    public function test_pos_debt_checkout_with_dp_separates_cash_and_non_cash_dp_methods(): void
    {
        $admin = User::where('username', 'admin')->first();
        $customer = Customer::first();
        $product = Product::first();
        $product->update(['stock' => 100]);

        $this->actingAs($admin)->post(route('cash-shifts.open'), [
            'opening_cash' => '100000',
            'notes' => 'Shift Test DP',
        ]);

        $shift = $admin->fresh()->currentOpenShift;
        $cashService = app(\App\Services\CashService::class);

        // 1. Debt Rp 500.000 with DP Rp 150.000 via QRIS (Non-Cash)
        $resQris = $this->actingAs($admin)->postJson(route('pos.checkout'), [
            'items' => [[
                'product_id' => $product->id,
                'qty' => 1,
                'selling_price' => 500000,
                'subtotal' => 500000,
                'discount' => 0,
            ]],
            'customer_id' => $customer->id,
            'paid_amount' => 150000,
            'payment_method' => 'debt',
            'dp_payment_method' => 'qris',
        ]);
        $resQris->assertStatus(200);
        $saleQris = Sale::find($resQris->json('sale_id'));
        $this->assertEquals('partial', $saleQris->payment_status);
        $this->assertEquals('qris', $saleQris->dp_payment_method);
        $this->assertEquals(350000, $saleQris->remaining_debt);
        $this->assertEquals(1, $saleQris->paymentAllocations()->count());

        // Expected physical cash in drawer should still be 100.000 (not 250.000), while non_cash_sales is 150.000
        $summaryAfterQris = $cashService->calculateShiftSummary($shift);
        $this->assertEquals(100000, $summaryAfterQris['expected_cash']);
        $this->assertEquals(150000, $summaryAfterQris['non_cash_sales']);

        // 2. Debt Rp 300.000 with DP Rp 100.000 via Cash (Tunai)
        $resCash = $this->actingAs($admin)->postJson(route('pos.checkout'), [
            'items' => [[
                'product_id' => $product->id,
                'qty' => 1,
                'selling_price' => 300000,
                'subtotal' => 300000,
                'discount' => 0,
            ]],
            'customer_id' => $customer->id,
            'paid_amount' => 100000,
            'payment_method' => 'debt',
            'dp_payment_method' => 'cash',
        ]);
        $resCash->assertStatus(200);
        $saleCash = Sale::find($resCash->json('sale_id'));
        $this->assertEquals('cash', $saleCash->dp_payment_method);

        // Expected physical cash in drawer should now increase by 100.000 -> 200.000
        $summaryAfterCash = $cashService->calculateShiftSummary($shift);
        $this->assertEquals(200000, $summaryAfterCash['expected_cash']);
    }

    public function test_income_report_calculates_opsi_b_correctly(): void
    {
        $admin = User::where('username', 'admin')->first();
        $customer = Customer::first();
        $product = Product::first();
        $product->update([
            'stock' => 100,
            'cost_price' => 60000,
            'selling_price' => 100000,
        ]);

        // Open shift
        $this->actingAs($admin)->post(route('cash-shifts.open'), [
            'opening_cash' => '100000',
            'notes' => 'Shift Test Income Report',
        ]);

        // 1. Direct Cash Sale: 2 pcs @ Rp 100.000 = Rp 200.000 (Modal: Rp 120.000, Laba Kotor: Rp 80.000)
        $resDirect = $this->actingAs($admin)->postJson(route('pos.checkout'), [
            'items' => [[
                'product_id' => $product->id,
                'qty' => 2,
                'selling_price' => 100000,
                'subtotal' => 200000,
                'discount' => 0,
            ]],
            'customer_id' => null,
            'paid_amount' => 200000,
            'payment_method' => 'cash',
        ]);
        $resDirect->assertStatus(200);
        $saleDirect = Sale::find($resDirect->json('sale_id'));
        $day1 = now()->subDays(2)->startOfDay()->addHours(10);
        $saleDirect->update(['transaction_date' => $day1]);

        // 2. Debt Sale on Day 1: 3 pcs @ Rp 100.000 = Rp 300.000 (Modal: Rp 180.000, Laba Kotor: Rp 120.000)
        // With DP Rp 100.000 via QRIS -> Initial Credit on Day 1 = Rp 200.000
        $resDebt = $this->actingAs($admin)->postJson(route('pos.checkout'), [
            'items' => [[
                'product_id' => $product->id,
                'qty' => 3,
                'selling_price' => 100000,
                'subtotal' => 300000,
                'discount' => 0,
            ]],
            'customer_id' => $customer->id,
            'paid_amount' => 100000,
            'payment_method' => 'debt',
            'dp_payment_method' => 'qris',
        ]);
        $resDebt->assertStatus(200);
        $saleDebt = Sale::find($resDebt->json('sale_id'));
        $saleDebt->update(['transaction_date' => $day1]);
        // Update the initial DP payment date to Day 1 as well
        $dpPayment = $saleDebt->paymentAllocations()->first()->receivablePayment;
        $dpPayment->update(['payment_date' => $day1]);

        // 3. Subsequent Debt Repayment on Day 2 via Transfer (TF): Rp 150.000
        $day2 = now()->subDay()->startOfDay()->addHours(14);
        $debtService = app(\App\Services\DebtService::class);
        $repayment = $debtService->payCustomerReceivable($customer, [
            'amount' => 150000,
            'payment_method' => 'transfer',
            'notes' => 'Cicilan via TF',
        ], $admin->id);
        $repayment->update(['payment_date' => $day2]);

        // Verify ReportService::getIncomeReport (Opsi B)
        $reportService = app(\App\Services\ReportService::class);
        $report = $reportService->getIncomeReport($day1->copy()->startOfDay(), $day2->copy()->endOfDay());

        $records = $report['records']->keyBy('date');
        $day1Key = $day1->format('Y-m-d');
        $day2Key = $day2->format('Y-m-d');

        $this->assertTrue($records->has($day1Key));
        $this->assertTrue($records->has($day2Key));

        // Day 1 assertions:
        // Penjualan = 200.000 + 300.000 = 500.000
        // Cash = 200.000, QRIS = 100.000 (DP), TF = 0 -> Pembayaran Real Kios = 300.000
        // Credit = 200.000 (remains historical even after Day 2 repayment!)
        // Modal = 120.000 + 180.000 = 300.000, Laba Kotor = 200.000
        $row1 = $records->get($day1Key);
        $this->assertEquals(500000, $row1->penjualan);
        $this->assertEquals(300000, $row1->pembayaran_real_kios);
        $this->assertEquals(200000, $row1->cash);
        $this->assertEquals(0, $row1->tf);
        $this->assertEquals(100000, $row1->qris);
        $this->assertEquals(200000, $row1->credit);
        $this->assertEquals(300000, $row1->modal);
        $this->assertEquals(200000, $row1->laba_kotor);

        // Day 2 assertions (Opsi B):
        // Penjualan = 0, TF = 150.000 (from debt repayment), Pembayaran Real Kios = 150.000
        $row2 = $records->get($day2Key);
        $this->assertEquals(0, $row2->penjualan);
        $this->assertEquals(150000, $row2->pembayaran_real_kios);
        $this->assertEquals(150000, $row2->tf);
        $this->assertEquals(150000, $row2->debt_repayment);

        // Verify HTTP endpoints for Web & PDF
        $resWeb = $this->actingAs($admin)->get(route('reports.income', [
            'period' => 'custom',
            'start_date' => $day1->format('Y-m-d'),
            'end_date' => $day2->format('Y-m-d'),
        ]));
        $resWeb->assertStatus(200);
        $resWeb->assertSee('PENDAPATAN');
        $resWeb->assertSee('PEMBAYARAN');

        $resPdf = $this->actingAs($admin)->get(route('reports.income.pdf', [
            'period' => 'custom',
            'start_date' => $day1->format('Y-m-d'),
            'end_date' => $day2->format('Y-m-d'),
        ]));
        $resPdf->assertStatus(200);
    }
}

