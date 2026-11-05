@extends('layouts.app')

@section('title', 'Cetak Label Barcode Produk')

@section('content')
<style>
    /* Styling Area Preview & Cetak Barcode */
    .barcode-grid-container {
        display: grid;
        gap: 8px;
        background: #ffffff;
        padding: 12px;
        border-radius: 12px;
    }

    /* Ukuran Kertas / Layout Stiker */
    .layout-grid_4 {
        grid-template-columns: repeat(4, 1fr);
    }
    .layout-grid_3 {
        grid-template-columns: repeat(3, 1fr);
    }
    .layout-thermal_2 {
        grid-template-columns: repeat(2, 1fr);
        max-width: 340px;
        margin: 0 auto;
    }
    .layout-thermal_1 {
        grid-template-columns: 1fr;
        max-width: 220px;
        margin: 0 auto;
    }

    .barcode-sticker {
        border: 1px dashed #cbd5e1;
        border-radius: 6px;
        padding: 6px 8px;
        text-align: center;
        background: #fff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .barcode-sticker .store-title {
        font-size: 0.62rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #475569;
        margin-bottom: 1px;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .barcode-sticker .product-title {
        font-size: 0.74rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.15;
        margin-bottom: 3px;
        max-width: 100%;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .barcode-sticker .barcode-svg-wrap {
        width: 100%;
        margin: 2px 0;
        display: flex;
        justify-content: center;
    }

    .barcode-sticker .barcode-svg-wrap svg {
        max-width: 100%;
        height: 36px;
    }

    .barcode-sticker .barcode-code-text {
        font-family: 'Courier New', Courier, monospace;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 1px;
        color: #0f172a;
        margin-top: 1px;
    }

    .barcode-sticker .product-price-text {
        font-size: 0.8rem;
        font-weight: 800;
        color: #0f172a;
        margin-top: 2px;
    }

    .barcode-sticker .product-old-price {
        font-size: 0.63rem;
        text-decoration: line-through;
        color: #64748b;
        margin-right: 4px;
        font-weight: 500;
    }

    @media print {
        @page {
            margin: 6mm;
        }
        body {
            background: #ffffff !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .sidebar,
        .top-header,
        .d-print-none {
            display: none !important;
        }
        .main-wrapper,
        .content-area,
        .container-fluid {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        .barcode-grid-container {
            padding: 0 !important;
            gap: 4px !important;
            border: none !important;
            box-shadow: none !important;
        }
        .barcode-sticker {
            border: 1px dotted #94a3b8 !important;
            border-radius: 3px !important;
            padding: 4px 6px !important;
        }
    }
</style>

<div class="container-fluid px-0">
    <!-- Header (Hidden on Print) -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 d-print-none">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-upc-scan me-2 text-primary"></i>Cetak Label Barcode Produk</h4>
            <p class="text-muted small mb-0">Atur daftar produk, jumlah stiker label, serta ukuran kertas (A4 Sheet / Printer Thermal Label)</p>
        </div>
        <div class="d-flex gap-2">
            @if($printItems->isNotEmpty())
                <button type="button" onclick="window.print()" class="btn btn-dark rounded-pill px-4 d-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-printer-fill"></i> Cetak Sekarang ({{ $printItems->sum('qty') }} Label)
                </button>
            @endif
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary rounded-pill px-3 d-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Kembali ke Produk
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Panel Konfigurasi Cetak (Hidden on Print) -->
        <div class="col-12 col-lg-5 d-print-none">
            <form action="{{ route('products.barcode') }}" method="POST" id="barcodeConfigForm">
                @csrf
                <input type="hidden" name="configured" value="1">

                <div class="card card-custom p-4 mb-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle text-primary me-1"></i> 1. Pilih / Tambah Produk</h6>

                    <div class="input-group mb-3">
                        <select id="productPicker" class="form-select">
                            <option value="">-- Cari & Pilih Produk untuk Ditambahkan --</option>
                            @foreach($allProducts as $p)
                                <option value="{{ $p->id }}"
                                        data-name="{{ $p->name }}"
                                        data-code="{{ $p->barcode ?: $p->sku }}"
                                        data-price="{{ number_format($p->final_price, 0, ',', '.') }}"
                                        data-stock="{{ max(1, (int) $p->stock) }}">
                                    {{ $p->name }} ({{ $p->barcode ?: $p->sku }}) - Rp {{ number_format($p->final_price, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-primary-custom" onclick="addProductToQueue()">
                            <i class="bi bi-plus-lg"></i> Tambah
                        </button>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-semibold text-muted">Daftar Antrean Cetak:</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0" onclick="setAllQuantitiesToStock()" title="Isi jumlah label sesuai stok fisik masing-masing produk">
                                Sesuai Stok
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0" onclick="setAllQuantities(4)">
                                Set Semua = 4
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0" onclick="setAllQuantities(10)">
                                Set Semua = 10
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded-3" style="max-height: 310px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0" id="queueTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Produk & Kode</th>
                                    <th class="text-center" style="width: 100px;">Jml Label</th>
                                    <th class="text-center" style="width: 45px;"></th>
                                </tr>
                            </thead>
                            <tbody id="queueTableBody">
                                @forelse($printItems as $item)
                                    @php $prod = $item['product']; @endphp
                                    <tr id="row-product-{{ $prod->id }}" data-stock="{{ max(1, (int) $prod->stock) }}">
                                        <td>
                                            <input type="hidden" name="product_ids[]" value="{{ $prod->id }}">
                                            <div class="fw-semibold text-dark small">{{ $prod->name }}</div>
                                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.7rem;">{{ $item['code'] }}</span>
                                            <small class="text-muted ms-1" style="font-size: 0.7rem;">Stok: {{ $prod->stock }}</small>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" name="quantities[{{ $prod->id }}]" value="{{ $item['qty'] }}" min="1" max="500" class="form-control form-control-sm text-center fw-bold qty-input">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm text-danger p-1" onclick="removeProductRow({{ $prod->id }})" title="Hapus dari daftar">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="emptyQueueRow">
                                        <td colspan="3" class="text-center py-4 text-muted small">
                                            Belum ada produk dipilih. Pilih produk dari dropdown di atas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card card-custom p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-sliders text-primary me-1"></i> 2. Pengaturan Ukuran & Tampilan Label</h6>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Ukuran Kertas / Tata Letak Kolom</label>
                        <select name="paper_size" class="form-select">
                            <option value="grid_4" {{ $paperSize === 'grid_4' ? 'selected' : '' }}>Kertas Stiker A4 - 4 Kolom (Tom & Jerry 103 / Standar)</option>
                            <option value="grid_3" {{ $paperSize === 'grid_3' ? 'selected' : '' }}>Kertas Stiker A4 - 3 Kolom (Ukuran Lebar / T&J 121)</option>
                            <option value="thermal_2" {{ $paperSize === 'thermal_2' ? 'selected' : '' }}>Printer Thermal Roll - 2 Baris (33x15mm / 35x25mm)</option>
                            <option value="thermal_1" {{ $paperSize === 'thermal_1' ? 'selected' : '' }}>Printer Thermal Roll - 1 Baris (40x30mm / 50x30mm)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold d-block">Informasi yang Ditampilkan pada Stiker</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="show_store_name" id="chkStoreName" value="1" {{ $showStoreName ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="chkStoreName">Nama Toko</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="show_product_name" id="chkProductName" value="1" {{ $showProductName ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="chkProductName">Nama Produk</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="show_price" id="chkPrice" value="1" {{ $showPrice ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="chkPrice">Harga Jual</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="show_code" id="chkCode" value="1" {{ $showCode ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="chkCode">Teks Kode / SKU</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-primary-custom py-2 fw-bold">
                            <i class="bi bi-arrow-repeat me-1"></i> Terapkan & Perbarui Preview Barcode
                        </button>
                        @if($printItems->isNotEmpty())
                            <button type="button" onclick="window.print()" class="btn btn-dark py-2 fw-bold">
                                <i class="bi bi-printer-fill me-1"></i> Cetak Label Sekarang
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Area Preview & Cetak Stiker Barcode -->
        <div class="col-12 col-lg-7">
            <div class="card card-custom p-3 mb-3 d-print-none bg-light border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small fw-bold text-secondary">
                        <i class="bi bi-eye me-1"></i> PRATINJAU CETAK (PREVIEW)
                    </span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                        Total: {{ $printItems->count() }} Produk &bull; {{ $printItems->sum('qty') }} Stiker Label
                    </span>
                </div>
            </div>

            @if($printItems->isEmpty())
                <div class="card card-custom p-5 text-center text-muted d-print-none">
                    <i class="bi bi-upc-scan display-3 text-secondary mb-3"></i>
                    <h6 class="fw-bold text-dark">Belum Ada Label Barcode untuk Dicetak</h6>
                    <p class="small mb-0">Silakan pilih produk di panel sebelah kiri lalu klik <strong>Terapkan & Perbarui Preview Barcode</strong>.</p>
                </div>
            @else
                <div id="barcodePrintArea" class="barcode-grid-container layout-{{ $paperSize }} shadow-sm border">
                    @foreach($printItems as $item)
                        @php
                            $product = $item['product'];
                        @endphp
                        @for($i = 0; $i < $item['qty']; $i++)
                            <div class="barcode-sticker">
                                @if($showStoreName)
                                    <div class="store-title">{{ $storeName }}</div>
                                @endif

                                @if($showProductName)
                                    <div class="product-title">{{ $product->name }}</div>
                                @endif

                                <div class="barcode-svg-wrap">
                                    {!! $item['svg'] !!}
                                </div>

                                @if($showCode)
                                    <div class="barcode-code-text">{{ $item['code'] }}</div>
                                @endif

                                @if($showPrice)
                                    <div class="product-price-text">
                                        @if($product->has_discount)
                                            <span class="product-old-price">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                                            <span>Rp {{ number_format($product->final_price, 0, ',', '.') }}</span>
                                        @else
                                            Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endfor
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    function addProductToQueue() {
        const picker = document.getElementById('productPicker');
        const selectedOption = picker.options[picker.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            return;
        }

        const id = selectedOption.value;
        const name = selectedOption.getAttribute('data-name');
        const code = selectedOption.getAttribute('data-code');
        const stock = selectedOption.getAttribute('data-stock') || 4;

        const emptyRow = document.getElementById('emptyQueueRow');
        if (emptyRow) {
            emptyRow.remove();
        }

        const existingRow = document.getElementById('row-product-' + id);
        if (existingRow) {
            const qtyInput = existingRow.querySelector('.qty-input');
            qtyInput.value = parseInt(qtyInput.value || '0', 10) + 4;
            picker.value = '';
            document.getElementById('barcodeConfigForm').submit();
            return;
        }

        const tbody = document.getElementById('queueTableBody');
        const tr = document.createElement('tr');
        tr.id = 'row-product-' + id;
        tr.setAttribute('data-stock', stock);
        tr.innerHTML = `
            <td>
                <input type="hidden" name="product_ids[]" value="${id}">
                <div class="fw-semibold text-dark small">${name}</div>
                <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.7rem;">${code}</span>
                <small class="text-muted ms-1" style="font-size: 0.7rem;">Stok: ${stock}</small>
            </td>
            <td class="text-center">
                <input type="number" name="quantities[${id}]" value="4" min="1" max="500" class="form-control form-control-sm text-center fw-bold qty-input">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm text-danger p-1" onclick="removeProductRow(${id})" title="Hapus dari daftar">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        picker.value = '';
        document.getElementById('barcodeConfigForm').submit();
    }

    function removeProductRow(id) {
        const row = document.getElementById('row-product-' + id);
        if (row) {
            row.remove();
            document.getElementById('barcodeConfigForm').submit();
        }
    }

    function setAllQuantities(qty) {
        document.querySelectorAll('.qty-input').forEach(input => {
            input.value = qty;
        });
        document.getElementById('barcodeConfigForm').submit();
    }

    function setAllQuantitiesToStock() {
        document.querySelectorAll('#queueTableBody tr[data-stock]').forEach(row => {
            const stock = parseInt(row.getAttribute('data-stock') || '1', 10);
            const input = row.querySelector('.qty-input');
            if (input) {
                input.value = Math.max(1, stock);
            }
        });
        document.getElementById('barcodeConfigForm').submit();
    }
</script>
@endpush
@endsection
