@extends('layouts.app')

@section('title', 'Satuan Produk')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Satuan Produk</h4>
            <p class="text-muted small mb-0">Kelola unit pengukuran barang (Pcs, Box, Kg, Liter, dll.)</p>
        </div>
        <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUnitModal">
            <i class="bi bi-plus-circle me-1"></i> Tambah Satuan
        </button>
    </div>

    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Nama Satuan</th>
                        <th>Singkatan</th>
                        <th class="text-center">Digunakan Produk</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $index => $u)
                        <tr>
                            <td>{{ $units->firstItem() + $index }}</td>
                            <td class="fw-semibold text-dark">{{ $u->name }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary px-3 py-1">{{ $u->short_name ?? '-' }}</span></td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1">
                                    {{ $u->products_count }} Produk
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border" data-bs-toggle="modal" data-bs-target="#editUnitModal{{ $u->id }}" title="Edit">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </button>
                                    <form action="{{ route('units.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Hapus satuan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light border" title="Hapus">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Modal Edit Unit -->
                                <div class="modal fade text-start" id="editUnitModal{{ $u->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <form action="{{ route('units.update', $u->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header border-0">
                                                    <h5 class="modal-title fw-bold">Edit Satuan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Nama Satuan</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Singkatan</label>
                                                        <input type="text" name="short_name" class="form-control" value="{{ $u->short_name }}">
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
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada data satuan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($units->hasPages())
            <div class="p-3 border-top">
                {{ $units->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Unit -->
<div class="modal fade" id="addUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('units.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Tambah Satuan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Satuan <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Pieces (Pcs)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Singkatan</label>
                        <input type="text" name="short_name" class="form-control" placeholder="Contoh: pcs, box, kg">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Satuan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
