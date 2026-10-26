<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_all_six_new_reports_are_accessible_for_admin(): void
    {
        $admin = User::where('username', 'admin')->first();

        $routes = [
            'reports.stock-shrinkage',
            'reports.sales-by-product',
            'reports.sales-by-turnover',
            'reports.sales-by-customer',
            'reports.finance-cash-bank',
            'reports.sales-by-cashier',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($admin)->get(route($route));
            $response->assertStatus(200);
        }
    }

    public function test_all_six_new_reports_pdf_can_be_downloaded_or_streamed(): void
    {
        $admin = User::where('username', 'admin')->first();

        $pdfRoutes = [
            'reports.stock-shrinkage.pdf',
            'reports.sales-by-product.pdf',
            'reports.sales-by-turnover.pdf',
            'reports.sales-by-customer.pdf',
            'reports.finance-cash-bank.pdf',
            'reports.sales-by-cashier.pdf',
        ];

        foreach ($pdfRoutes as $route) {
            $response = $this->actingAs($admin)->get(route($route, ['stream' => 1]));
            $response->assertStatus(200);
            $response->assertHeader('content-type', 'application/pdf');
        }
    }
}
