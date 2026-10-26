@extends('layouts.app')

@section('title', 'Pengaturan Toko & Sistem')

@section('content')
<div class="container-fluid px-0" style="max-width: 900px;">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Pengaturan Toko & Preferensi Sistem</h4>
        <p class="text-muted small mb-0">Sesuaikan profil toko, format nota struk kasir, persentase pajak, dan konfigurasi POS</p>
    </div>

    <div class="card card-custom p-4">
        <form action="{{ route('settings.update') }}" method="POST">
            @csrf

            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-shop me-2"></i> Identitas & Profil Toko</h6>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Nama Toko / Bisnis <span class="text-danger">*</span></label>
                    <input type="text" name="store_name" class="form-control" value="{{ old('store_name', $settings['store_name']) }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Tagline / Slogan Toko</label>
                    <input type="text" name="store_tagline" class="form-control" value="{{ old('store_tagline', $settings['store_tagline']) }}">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Alamat Lengkap Toko</label>
                    <textarea name="store_address" class="form-control" rows="2">{{ old('store_address', $settings['store_address']) }}</textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="store_phone" class="form-control" value="{{ old('store_phone', $settings['store_phone']) }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Alamat Email Toko</label>
                    <input type="email" name="store_email" class="form-control" value="{{ old('store_email', $settings['store_email']) }}">
                </div>
            </div>

            <hr class="my-4">

            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-receipt-cutoff me-2"></i> Pengaturan Faktur & Struk Thermal</h6>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold">Awalan No. Invoice Penjualan <span class="text-danger">*</span></label>
                    <input type="text" name="invoice_prefix" class="form-control font-monospace" value="{{ old('invoice_prefix', $settings['invoice_prefix']) }}" required>
                    <small class="text-muted" style="font-size: 0.75rem;">Contoh hasil: INV-20261013-0001</small>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold">Awalan No. Faktur Pembelian (PO) <span class="text-danger">*</span></label>
                    <input type="text" name="purchase_prefix" class="form-control font-monospace" value="{{ old('purchase_prefix', $settings['purchase_prefix']) }}" required>
                    <small class="text-muted" style="font-size: 0.75rem;">Contoh hasil: PO-20261013-0001</small>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold">Default Pajak PPN (%) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.1" min="0" max="100" name="tax_percentage" class="form-control text-end" value="{{ old('tax_percentage', $settings['tax_percentage']) }}" required>
                        <span class="input-group-text bg-light">%</span>
                    </div>
                    <small class="text-muted" style="font-size: 0.75rem;">Set 0 jika toko tidak mengenakan PPN</small>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Pesan Footer Struk Kasir</label>
                    <textarea name="receipt_footer" class="form-control font-monospace" rows="3">{{ old('receipt_footer', $settings['receipt_footer']) }}</textarea>
                    <small class="text-muted" style="font-size: 0.75rem;">Akan dicetak di bagian paling bawah struk thermal kasir</small>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <button type="submit" class="btn btn-primary-custom px-4 shadow-sm">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Seluruh Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
