@extends('layouts.app')

@section('title', 'Buat Retur Pembelian ke Supplier')

@section('content')
<div class="container-fluid px-0" style="max-width: 950px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Formulir Retur Pembelian ke Supplier</h4>
            <p class="text-muted small mb-0">Pilih faktur PO pembelian dan tentukan barang yang dikembalikan ke distributor</p>
        </div>
        <a href="{{ route('returns.purchases.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <!-- Step 1: Select Purchase Invoice -->
    <div class="card card-custom p-4 mb-4">
        <label class="form-label small fw-semibold">Pilih Faktur Pembelian (PO) Asal</label>
        <div class="d-flex gap-2">
            <select class="form-select" id="purchaseSelect" onchange="window.location.href='{{ route('returns.purchases.create') }}?purchase_id=' + this.value">
                <option value="">Pilih No. Faktur PO...</option>
                @foreach($purchases as $p)
                    <option value="{{ $p->id }}" {{ ($purchase && $purchase->id == $p->id) ? 'selected' : '' }}>
                        {{ $p->purchase_number }} - {{ $p->purchase_date->format('d/m/Y') }} - {{ $p->supplier?->name }} (Rp {{ number_format($p->grand_total, 0, ',', '.') }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @if($purchase)
        <form action="{{ route('returns.purchases.store') }}" method="POST">
            @csrf
            <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">

            <div class="card card-custom p-4 mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Faktur Pembelian Asal</small>
                        <div class="fw-bold fs-5 text-primary">{{ $purchase->purchase_number }}</div>
                        <div class="small text-muted">Supplier: {{ $purchase->supplier?->name }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Catatan / Alasan Retur</label>
                        <input type="text" name="notes" class="form-control" placeholder="Contoh: Barang cacat dari pabrik / kemasan bocor...">
                    </div>
                </div>
            </div>

            <!-- Items Table from Purchase -->
            <div class="card card-custom p-4 mb-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-box-arrow-right text-danger me-2"></i> Pilih Barang yang Diretur ke Supplier</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th>Produk</th>
                                <th class="text-center" style="width: 120px;">Qty Beli</th>
                                <th class="text-end" style="width: 150px;">Harga Beli</th>
                                <th class="text-center" style="width: 140px;">Qty Retur</th>
                                <th style="width: 250px;">Alasan Retur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->details as $index => $item)
                                <tr>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                        <div class="fw-bold text-dark">{{ $item->product?->name }}</div>
                                        <small class="text-muted">SKU: {{ $item->product?->sku }}</small>
                                    </td>
                                    <td class="text-center">{{ $item->qty }}</td>
                                    <td class="text-end">Rp {{ number_format($item->cost_price, 0, ',', '.') }}</td>
                                    <td>
                                        <input type="number" name="items[{{ $index }}][qty]" class="form-control form-control-sm text-center" min="0" max="{{ $item->qty }}" value="0">
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][reason]" class="form-control form-control-sm" placeholder="Cacat pabrik, rusak, dll.">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('returns.purchases.index') }}" class="btn btn-light rounded-pill px-4">Batal</a>
                    <button type="submit" class="btn btn-primary-custom px-4 shadow-sm" onclick="return confirm('Konfirmasi proses retur pembelian ini? Stok akan otomatis dikurangi.')">
                        <i class="bi bi-check2-circle me-1"></i> Proses Retur & Kurangi Stok
                    </button>
                </div>
            </div>
        </form>
    @else
        <div class="card card-custom p-5 text-center text-muted">
            <i class="bi bi-truck fs-1 text-secondary mb-2"></i>
            Silakan pilih faktur pembelian di atas untuk memuat daftar barang supplier.
        </div>
    @endif
</div>
@endsection
