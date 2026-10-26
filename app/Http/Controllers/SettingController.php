<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'store_name' => Setting::get('store_name', 'Kasir POS'),
            'store_tagline' => Setting::get('store_tagline', 'Solusi Kasir Bisnis'),
            'store_address' => Setting::get('store_address', 'Jl. Contoh Alamat Toko'),
            'store_phone' => Setting::get('store_phone', '081234567890'),
            'store_email' => Setting::get('store_email', 'toko@pos.test'),
            'tax_percentage' => Setting::get('tax_percentage', '0'),
            'receipt_footer' => Setting::get('receipt_footer', 'Terima kasih atas kunjungan Anda!'),
            'invoice_prefix' => Setting::get('invoice_prefix', 'INV'),
            'purchase_prefix' => Setting::get('purchase_prefix', 'PO'),
            'low_stock_alert' => Setting::get('low_stock_alert', '5'),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'store_tagline' => ['nullable', 'string', 'max:255'],
            'store_address' => ['nullable', 'string'],
            'store_phone' => ['nullable', 'string', 'max:50'],
            'store_email' => ['nullable', 'email', 'max:255'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'receipt_footer' => ['nullable', 'string'],
            'invoice_prefix' => ['required', 'string', 'max:10'],
            'purchase_prefix' => ['required', 'string', 'max:10'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, (string) $value, 'general');
        }

        return back()->with('success', 'Pengaturan sistem & profil toko berhasil disimpan.');
    }
}
