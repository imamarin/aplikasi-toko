@extends('layouts.app')

@section('title', 'Profil Pengguna')

@section('content')
<div class="container-fluid px-0" style="max-width: 900px;">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Pengaturan Profil</h4>
        <p class="text-muted small mb-0">Kelola informasi data diri dan kata sandi akun Anda</p>
    </div>

    <div class="row g-4">
        <!-- Informasi Profil -->
        <div class="col-12 col-md-6">
            <div class="card card-custom p-4 h-100">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-circle text-primary me-2"></i> Data Pengguna</h6>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Username</label>
                        <input type="text" class="form-control bg-light" value="{{ $user->username }}" disabled>
                        <small class="text-muted" style="font-size: 0.75rem;">Username tidak dapat diubah</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role / Hak Akses</label>
                        <input type="text" class="form-control bg-light" value="{{ $user->role?->name ?? 'Kasir' }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alamat Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor Telepon</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100">Simpan Perubahan Profil</button>
                </form>
            </div>
        </div>

        <!-- Ubah Password -->
        <div class="col-12 col-md-6">
            <div class="card card-custom p-4 h-100">
                <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock text-warning me-2"></i> Ubah Password</h6>
                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password Saat Ini</label>
                        <input type="password" name="current_password" class="form-control" required placeholder="••••••••">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password Baru</label>
                        <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" class="form-control" required placeholder="Ulangi password baru">
                    </div>

                    <button type="submit" class="btn btn-warning w-100 fw-semibold text-dark">Perbarui Kata Sandi</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
