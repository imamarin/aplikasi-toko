@extends('layouts.app')

@section('title', 'Kelola Pengguna Sistem')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Manajemen Pengguna & Staf</h4>
            <p class="text-muted small mb-0">Kelola akun karyawan toko dan penugasan peran (Super Admin, Admin, Kasir, Gudang, Owner)</p>
        </div>
        <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Pengguna Baru
        </button>
    </div>

    <div class="card card-custom">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Nama Pengguna</th>
                        <th>Username</th>
                        <th>Email / Telepon</th>
                        <th class="text-center">Peran (Role)</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $index => $u)
                        <tr>
                            <td>{{ $users->firstItem() + $index }}</td>
                            <td class="fw-bold text-dark">
                                {{ $u->name }}
                                @if($u->id === auth()->id())
                                    <span class="badge bg-primary-subtle text-primary ms-1" style="font-size: 0.7rem;">Anda</span>
                                @endif
                            </td>
                            <td><code>{{ $u->username }}</code></td>
                            <td>
                                <div>{{ $u->email ?? '-' }}</div>
                                <small class="text-muted">{{ $u->phone }}</small>
                            </td>
                            <td class="text-center">
                                @switch($u->role?->slug)
                                    @case('super-admin')
                                        <span class="badge bg-danger px-3 py-1">Super Admin</span>
                                        @break
                                    @case('admin')
                                        <span class="badge bg-primary px-3 py-1">Admin</span>
                                        @break
                                    @case('kasir')
                                        <span class="badge bg-warning text-dark px-3 py-1">Kasir</span>
                                        @break
                                    @case('gudang')
                                        <span class="badge bg-info text-dark px-3 py-1">Gudang</span>
                                        @break
                                    @default
                                        <span class="badge bg-dark text-white px-3 py-1">{{ $u->role?->name ?? 'User' }}</span>
                                @endswitch
                            </td>
                            <td class="text-center">
                                @if($u->status)
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}" title="Edit">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </button>
                                    @if($u->id !== auth()->id())
                                        <form action="{{ route('users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Hapus/Nonaktifkan pengguna {{ $u->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-light border" title="Hapus">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <!-- Modal Edit User -->
                                <div class="modal fade text-start" id="editUserModal{{ $u->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <form action="{{ route('users.update', $u->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header border-0">
                                                    <h5 class="modal-title fw-bold">Edit Akun Pengguna</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                                                        <input type="text" name="username" class="form-control" value="{{ $u->username }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Peran (Role) <span class="text-danger">*</span></label>
                                                        <select name="role_id" class="form-select" required>
                                                            @foreach($roles as $r)
                                                                <option value="{{ $r->id }}" {{ $u->role_id == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Email</label>
                                                            <input type="email" name="email" class="form-control" value="{{ $u->email }}">
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">No. HP</label>
                                                            <input type="text" name="phone" class="form-control" value="{{ $u->phone }}">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Password Baru (Kosongkan jika tidak diubah)</label>
                                                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="status" value="1" id="uStatus{{ $u->id }}" {{ $u->status ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="uStatus{{ $u->id }}">Status Akun Aktif</label>
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
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="p-3 border-top">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Nama staf/karyawan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Username Login <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" placeholder="username_login" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Peran (Role Akses) <span class="text-danger">*</span></label>
                        <select name="role_id" class="form-select" required>
                            <option value="">Pilih Role...</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} - {{ $r->description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="email@toko.test">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">No. HP</label>
                            <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxx">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password Awal <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
