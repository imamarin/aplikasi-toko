@extends('layouts.app')

@section('title', 'Data Supplier')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Data Supplier / Distributor</h4>
            <p class="text-muted small mb-0">Kelola informasi mitra pemasok barang dagangan untuk modul pembelian</p>
        </div>
        <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
            <i class="bi bi-plus-circle me-1"></i> Tambah Supplier Baru
        </button>
    </div>

    <!-- Search Card -->
    <div class="card card-custom p-3 mb-4">
        <form method="GET" action="{{ route('suppliers.index') }}" class="d-flex gap-2">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama supplier, kode, kontak..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="btn btn-primary-custom px-4">Cari</button>
            @if(request('search'))
                <a href="{{ route('suppliers.index') }}" class="btn btn-light border"><i class="bi bi-arrow-counterclockwise"></i></a>
            @endif
        </form>
    </div>

    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Kode</th>
                        <th>Nama Supplier</th>
                        <th>Kontak Person</th>
                        <th>No. Telepon / Email</th>
                        <th>Alamat</th>
                        <th class="text-center">Riwayat PO</th>
                        <th class="text-center" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $index => $sup)
                        <tr>
                            <td>{{ $suppliers->firstItem() + $index }}</td>
                            <td><span class="badge bg-light text-dark border font-monospace">{{ $sup->code }}</span></td>
                            <td>
                                <div class="fw-bold text-dark">{{ $sup->name }}</div>
                                <small class="text-muted">{{ $sup->notes ? Str::limit($sup->notes, 35) : '' }}</small>
                            </td>
                            <td>{{ $sup->contact_person ?? '-' }}</td>
                            <td>
                                <div><i class="bi bi-telephone text-muted me-1"></i>{{ $sup->phone ?? '-' }}</div>
                                @if($sup->email)
                                    <small class="text-muted"><i class="bi bi-envelope text-muted me-1"></i>{{ $sup->email }}</small>
                                @endif
                            </td>
                            <td class="small text-muted">{{ Str::limit($sup->address ?? '-', 40) }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1">
                                    {{ $sup->purchases_count }} Pembelian
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border" data-bs-toggle="modal" data-bs-target="#editSupModal{{ $sup->id }}" title="Edit">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </button>
                                    <form action="{{ route('suppliers.destroy', $sup->id) }}" method="POST" onsubmit="return confirm('Hapus supplier {{ $sup->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light border" title="Hapus">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Modal Edit Supplier -->
                                <div class="modal fade text-start" id="editSupModal{{ $sup->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <form action="{{ route('suppliers.update', $sup->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header border-0">
                                                    <h5 class="modal-title fw-bold">Edit Supplier</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Nama Supplier <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="{{ $sup->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Kontak Person</label>
                                                        <input type="text" name="contact_person" class="form-control" value="{{ $sup->contact_person }}">
                                                    </div>
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">No. Telepon</label>
                                                            <input type="text" name="phone" class="form-control" value="{{ $sup->phone }}">
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Email</label>
                                                            <input type="email" name="email" class="form-control" value="{{ $sup->email }}">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Alamat</label>
                                                        <textarea name="address" class="form-control" rows="2">{{ $sup->address }}</textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Catatan</label>
                                                        <textarea name="notes" class="form-control" rows="2">{{ $sup->notes }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0">
                                                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary-custom">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Belum ada data supplier.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())
            <div class="p-3 border-top">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Supplier -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('suppliers.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Tambah Supplier Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: PT Sumber Pangan Makmur" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kontak Person (PIC)</label>
                        <input type="text" name="contact_person" class="form-control" placeholder="Nama sales / manajer">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">No. Telepon / HP</label>
                            <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxx">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Alamat Email</label>
                            <input type="email" name="email" class="form-control" placeholder="sales@supplier.com">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat Kantor / Gudang</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Alamat lengkap..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Syarat pembayaran, produk utama, dll."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
