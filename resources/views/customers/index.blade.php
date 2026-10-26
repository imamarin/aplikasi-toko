@extends('layouts.app')

@section('title', 'Data Pelanggan')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Data Pelanggan & Member</h4>
            <p class="text-muted small mb-0">Kelola database pembeli, level keanggotaan, dan akumulasi poin loyalitas</p>
        </div>
        <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Pelanggan Baru
        </button>
    </div>

    <!-- Search Toolbar -->
    <div class="card card-custom p-3 mb-4">
        <form method="GET" action="{{ route('customers.index') }}" class="d-flex gap-2">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama pelanggan, nomor HP, kode..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="btn btn-primary-custom px-4">Cari</button>
            @if(request('search'))
                <a href="{{ route('customers.index') }}" class="btn btn-light border"><i class="bi bi-arrow-counterclockwise"></i></a>
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
                        <th>Nama Pelanggan</th>
                        <th>No. WhatsApp / HP</th>
                        <th>Email</th>
                        <th class="text-center">Level Member</th>
                        <th class="text-center">Poin Loyalitas</th>
                        <th class="text-center">Total Belanja</th>
                        <th class="text-center" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $index => $cust)
                        <tr>
                            <td>{{ $customers->firstItem() + $index }}</td>
                            <td><span class="badge bg-light text-dark border font-monospace">{{ $cust->code }}</span></td>
                            <td>
                                <div class="fw-bold text-dark">{{ $cust->name }}</div>
                                <small class="text-muted">{{ Str::limit($cust->address ?? '-', 35) }}</small>
                            </td>
                            <td>
                                @if($cust->phone && $cust->phone !== '-')
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $cust->phone) }}" target="_blank" class="text-decoration-none text-success">
                                        <i class="bi bi-whatsapp me-1"></i>{{ $cust->phone }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $cust->email ?? '-' }}</td>
                            <td class="text-center">
                                @switch($cust->member_level)
                                    @case('platinum')
                                        <span class="badge bg-dark text-white px-3 py-1">Platinum</span>
                                        @break
                                    @case('gold')
                                        <span class="badge bg-warning text-dark px-3 py-1">Gold</span>
                                        @break
                                    @case('silver')
                                        <span class="badge bg-secondary text-white px-3 py-1">Silver</span>
                                        @break
                                    @default
                                        <span class="badge bg-light text-dark border px-3 py-1">Regular</span>
                                @endswitch
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1">
                                    <i class="bi bi-star-fill me-1"></i>{{ number_format($cust->points) }} Poin
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1">
                                    {{ $cust->sales_count }} Transaksi
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border" data-bs-toggle="modal" data-bs-target="#editCustModal{{ $cust->id }}" title="Edit">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </button>
                                    @if($cust->code !== 'CUST-001')
                                        <form action="{{ route('customers.destroy', $cust->id) }}" method="POST" onsubmit="return confirm('Hapus pelanggan {{ $cust->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-light border" title="Hapus">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <!-- Modal Edit Pelanggan -->
                                <div class="modal fade text-start" id="editCustModal{{ $cust->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <form action="{{ route('customers.update', $cust->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header border-0">
                                                    <h5 class="modal-title fw-bold">Edit Pelanggan</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Nama Pelanggan <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="{{ $cust->name }}" required>
                                                    </div>
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">No. WhatsApp / HP</label>
                                                            <input type="text" name="phone" class="form-control" value="{{ $cust->phone }}">
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Level Member</label>
                                                            <select name="member_level" class="form-select">
                                                                <option value="regular" {{ $cust->member_level === 'regular' ? 'selected' : '' }}>Regular</option>
                                                                <option value="silver" {{ $cust->member_level === 'silver' ? 'selected' : '' }}>Silver</option>
                                                                <option value="gold" {{ $cust->member_level === 'gold' ? 'selected' : '' }}>Gold</option>
                                                                <option value="platinum" {{ $cust->member_level === 'platinum' ? 'selected' : '' }}>Platinum</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Alamat Email</label>
                                                        <input type="email" name="email" class="form-control" value="{{ $cust->email }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Alamat Tempat Tinggal</label>
                                                        <textarea name="address" class="form-control" rows="2">{{ $cust->address }}</textarea>
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
                            <td colspan="9" class="text-center py-5 text-muted">Belum ada pelanggan terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="p-3 border-top">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Pelanggan -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Tambah Pelanggan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Nama pelanggan..." required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">No. WhatsApp / HP</label>
                            <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxx">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Level Member</label>
                            <select name="member_level" class="form-select">
                                <option value="regular">Regular</option>
                                <option value="silver">Silver</option>
                                <option value="gold">Gold</option>
                                <option value="platinum">Platinum</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat Email</label>
                        <input type="email" name="email" class="form-control" placeholder="email@gmail.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat Tempat Tinggal</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Alamat pelanggan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Pelanggan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
