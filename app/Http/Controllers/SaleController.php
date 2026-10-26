<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['user', 'customer'])->latest('transaction_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->input('start_date') && $request->input('end_date')) {
            $query->whereBetween('transaction_date', [
                $request->input('start_date') . ' 00:00:00',
                $request->input('end_date') . ' 23:59:59'
            ]);
        }

        if ($method = $request->input('payment_method')) {
            $query->where('payment_method', $method);
        }

        $sales = $query->paginate(15)->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function show(Sale $sale)
    {
        $sale->load(['user', 'customer', 'details.product.unit', 'returns.details.product']);
        return view('sales.show', compact('sale'));
    }

    public function printReceipt(Sale $sale)
    {
        $sale->load(['user', 'customer', 'details']);

        $settings = [
            'store_name' => Setting::get('store_name', 'Kasir POS'),
            'store_address' => Setting::get('store_address', 'Jl. Contoh Alamat Toko'),
            'store_phone' => Setting::get('store_phone', '08123456789'),
            'receipt_footer' => Setting::get('receipt_footer', 'Terima kasih atas kunjungan Anda!'),
        ];

        return view('sales.receipt', compact('sale', 'settings'));
    }

    public function downloadPdf(Sale $sale)
    {
        $sale->load(['user', 'customer', 'details.product.unit']);

        $settings = [
            'store_name' => Setting::get('store_name', 'Kasir POS'),
            'store_address' => Setting::get('store_address', 'Jl. Contoh Alamat Toko'),
            'store_phone' => Setting::get('store_phone', '08123456789'),
            'store_email' => Setting::get('store_email', 'info@toko.test'),
        ];

        $pdf = Pdf::loadView('sales.invoice_pdf', compact('sale', 'settings'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Invoice-{$sale->invoice_number}.pdf");
    }
}
