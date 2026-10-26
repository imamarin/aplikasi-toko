@extends('layouts.guest')

@section('title', 'Login Masuk')

@section('content')
<div class="p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-4 shadow-sm mb-3" style="width: 56px; height: 56px; font-size: 1.75rem;">
            <i class="bi bi-cart-check-fill"></i>
        </div>
        <h4 class="fw-bold mb-1">{{ \App\Models\Setting::get('store_name', 'Kasir POS') }}</h4>
        <p class="text-muted small mb-0">Silakan login untuk mengakses sistem kasir</p>
    </div>

    @if(session('info'))
        <div class="alert alert-info py-2 px-3 small rounded-3 mb-3 border-0">
            <i class="bi bi-info-circle me-1"></i> {{ session('info') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3 border-0">
            <i class="bi bi-exclamation-circle me-1"></i> {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('login') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="username" class="form-label small fw-semibold text-secondary">Username atau Email</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                <input type="text" name="username" id="username" class="form-control border-start-0" value="{{ old('username', 'admin') }}" placeholder="admin / kasir" required autofocus>
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="form-label small fw-semibold text-secondary mb-0">Password</label>
            </div>
            <div class="input-group mt-1">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-lock"></i></span>
                <input type="password" name="password" id="password" class="form-control border-start-0" value="password" placeholder="••••••••" required>
            </div>
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
            <label class="form-check-label small text-secondary" for="remember">
                Ingat saya di perangkat ini
            </label>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100 mb-3 shadow-sm">
            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
        </button>
    </form>

    <div class="border-top pt-3 mt-3 text-center">
        <small class="text-muted d-block mb-2">Akun Default Demo:</small>
        <div class="d-flex gap-2 justify-content-center">
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="fillLogin('admin', 'password')">
                <i class="bi bi-person-fill-gear me-1"></i> Super Admin
            </button>
            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="fillLogin('kasir', 'password')">
                <i class="bi bi-calculator me-1"></i> Kasir
            </button>
        </div>
    </div>
</div>

<script>
    function fillLogin(user, pass) {
        document.getElementById('username').value = user;
        document.getElementById('password').value = pass;
    }
</script>
@endsection
