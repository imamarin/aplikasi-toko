<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CashService;
use App\Services\PurchaseService;
use App\Services\ReportService;
use App\Services\ReturnService;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Silakan login untuk mengakses sistem kasir');
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::where('username', 'admin')->first();

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_dashboard_is_accessible_when_authenticated(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Analitik');
    }

    public function test_pos_checkout_reduces_product_stock_and_creates_sale(): void
    {
        $kasir = User::where('username', 'kasir')->first();
        $product = Product::where('sku', 'PRD-MNM-001')->first();
        $initialStock = $product->stock;

        // Open shift for cashier first
        $cashService = app(CashService::class);
        $cashService->openShift($kasir->id, 100000);

        $payload = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'discount' => 0,
                ]
            ],
            'customer_id' => null,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_percentage' => 0,
            'paid_amount' => 50000,
            'payment_method' => 'cash',
        ];

        $response = $this->actingAs($kasir)->postJson('/pos/checkout', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Check stock reduced
        $product->refresh();
        $this->assertEquals($initialStock - 2, $product->stock);

        // Check stock movement audit log
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'sale',
            'qty_out' => 2,
        ]);

        // Check sale created
        $this->assertDatabaseHas('sales', [
            'user_id' => $kasir->id,
            'payment_method' => 'cash',
            'grand_total' => 8000, // 2 * 4000
        ]);
    }

    public function test_cashier_shift_calculates_cash_and_closing_difference(): void
    {
        $kasir = User::where('username', 'kasir')->first();
        $cashService = app(CashService::class);

        // Open shift with 200.000 opening cash
        $shift = $cashService->openShift($kasir->id, 200000);

        // Make sale of 2 items (grand_total: 8.000)
        $saleService = app(SaleService::class);
        $product = Product::first();
        $saleService->processCheckout([
            'items' => [
                ['product_id' => $product->id, 'qty' => 1, 'discount' => 0]
            ],
            'paid_amount' => $product->selling_price,
            'payment_method' => 'cash',
        ], $kasir->id);

        // Close shift with actual 205.000 cash
        $expected = 200000 + $product->selling_price;
        $actual = $expected + 1000; // 1.000 surplus

        $closedShift = $cashService->closeShift($shift, $actual, 'Closing sore');

        $this->assertEquals('closed', $closedShift->status);
        $this->assertEquals(1000, $closedShift->difference);
    }

    public function test_purchase_intake_increases_product_stock_and_updates_hpp(): void
    {
        $gudang = User::where('username', 'gudang')->first();
        $supplier = Supplier::first();
        $product = Product::first();
        $initialStock = $product->stock;
        $newCostPrice = 3200;

        $purchaseService = app(PurchaseService::class);
        $purchase = $purchaseService->createPurchase([
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'paid_amount' => 32000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 10,
                    'cost_price' => $newCostPrice,
                    'discount' => 0,
                ]
            ],
        ], $gudang->id);

        $product->refresh();
        $this->assertEquals($initialStock + 10, $product->stock);
        $this->assertEquals($newCostPrice, $product->cost_price);
    }

    public function test_customer_return_restores_stock(): void
    {
        $kasir = User::where('username', 'kasir')->first();
        $product = Product::first();

        // 1. Sale of 5
        $saleService = app(SaleService::class);
        $sale = $saleService->processCheckout([
            'items' => [
                ['product_id' => $product->id, 'qty' => 5, 'discount' => 0]
            ],
            'paid_amount' => 5 * $product->selling_price,
            'payment_method' => 'cash',
        ], $kasir->id);

        $stockAfterSale = $product->fresh()->stock;

        // 2. Return of 2
        $returnService = app(ReturnService::class);
        $return = $returnService->processSaleReturn([
            'sale_id' => $sale->id,
            'refund_method' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'reason' => 'Barang rusak',
                ]
            ],
        ], $kasir->id);

        $this->assertEquals($stockAfterSale + 2, $product->fresh()->stock);
        $this->assertDatabaseHas('sale_returns', [
            'id' => $return->id,
            'total_refund' => 2 * $product->selling_price,
        ]);
    }

    public function test_reports_pages_and_pdf_exports_work_correctly(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Sales Report & PDF
        $resSales = $this->actingAs($admin)->get(route('reports.sales'));
        $resSales->assertStatus(200)->assertSee('Laporan Penjualan');

        $resSalesPdf = $this->actingAs($admin)->get(route('reports.sales.pdf', ['user_id' => '', 'customer_id' => '']));
        $resSalesPdf->assertStatus(200)->assertHeader('content-type', 'application/pdf');

        // 2. Purchases Report & PDF
        $resPurchases = $this->actingAs($admin)->get(route('reports.purchases'));
        $resPurchases->assertStatus(200)->assertSee('Laporan Pembelian Barang');

        $resPurchasesPdf = $this->actingAs($admin)->get(route('reports.purchases.pdf'));
        $resPurchasesPdf->assertStatus(200)->assertHeader('content-type', 'application/pdf');

        // 3. Profit Loss Report & PDF
        $resProfitLoss = $this->actingAs($admin)->get(route('reports.profit-loss'));
        $resProfitLoss->assertStatus(200)->assertSee('Laporan Laba Rugi');

        $resProfitLossPdf = $this->actingAs($admin)->get(route('reports.profit-loss.pdf'));
        $resProfitLossPdf->assertStatus(200)->assertHeader('content-type', 'application/pdf');

        // 4. Stock Valuation Report & PDF
        $resStock = $this->actingAs($admin)->get(route('reports.stock'));
        $resStock->assertStatus(200)->assertSee('Laporan Nilai Persediaan Stok');

        $resStockPdf = $this->actingAs($admin)->get(route('reports.stock.pdf'));
        $resStockPdf->assertStatus(200)->assertHeader('content-type', 'application/pdf');
    }
}
