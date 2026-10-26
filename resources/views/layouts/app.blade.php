<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ \App\Models\Setting::get('store_name', 'Kasir POS') }}</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --pos-primary: #4f46e5;
            --pos-primary-hover: #4338ca;
            --pos-primary-light: #eef2ff;
            --pos-secondary: #0ea5e9;
            --pos-dark: #0f172a;
            --pos-sidebar-bg: #1e1b4b;
            --pos-sidebar-hover: #312e81;
            --pos-sidebar-active: #4f46e5;
            --pos-body-bg: #f8fafc;
            --pos-card-border: #e2e8f0;
            --pos-text-muted: #64748b;
        }

        html {
            font-size: 13.5px; /* Skala font global lebih compact & proporsional (default 16px) */
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--pos-body-bg);
            color: #1e293b;
            font-size: 0.875rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.45;
        }

        /* Penyesuaian Tipografi Kompak & Proporsional */
        h1, .h1 { font-size: 1.55rem; }
        h2, .h2 { font-size: 1.35rem; }
        h3, .h3 { font-size: 1.2rem; }
        h4, .h4 { font-size: 1.05rem; }
        h5, .h5 { font-size: 0.95rem; }
        h6, .h6 { font-size: 0.85rem; }

        .table {
            font-size: 0.825rem;
        }
        .table > :not(caption) > * > * {
            padding: 0.5rem 0.65rem;
        }
        .table th {
            font-size: 0.8rem;
            letter-spacing: 0.02em;
        }

        .form-control, .form-select {
            font-size: 0.825rem;
            padding: 0.38rem 0.65rem;
        }
        .form-control-sm, .form-select-sm {
            font-size: 0.775rem;
            padding: 0.25rem 0.5rem;
        }
        .form-label {
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }

        .btn {
            font-size: 0.825rem;
            padding: 0.38rem 0.8rem;
        }
        .btn-sm {
            font-size: 0.775rem;
            padding: 0.25rem 0.6rem;
        }

        .badge {
            font-size: 0.725rem;
            font-weight: 500;
        }

        small, .small {
            font-size: 0.775rem;
        }

        /* Sidebar Styling */
        #sidebar-wrapper {
            width: 255px;
            min-height: 100vh;
            background: linear-gradient(180deg, #1e1b4b 0%, #0f172a 100%);
            color: #cbd5e1;
            transition: all 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1040;
            overflow-y: auto;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
        }

        .sidebar-brand {
            padding: 1.1rem 1.15rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.02);
        }

        .sidebar-brand-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }

        .sidebar-heading {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            font-weight: 700;
            padding: 0.95rem 1.15rem 0.3rem;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.55rem 1.1rem;
            color: #cbd5e1;
            font-size: 0.835rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            margin: 0.12rem 0.45rem;
            border-radius: 8px;
        }

        .nav-link-custom:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(3px);
        }

        .nav-link-custom.active {
            color: #ffffff;
            background: var(--pos-sidebar-active);
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
        }

        .nav-link-custom i {
            font-size: 1.05rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content Layout */
        #page-content-wrapper {
            margin-left: 255px;
            flex: 1;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            min-width: 0;
        }

        .top-navbar {
            height: 60px;
            background: #ffffff;
            border-bottom: 1px solid var(--pos-card-border);
            position: sticky;
            top: 0;
            z-index: 1030;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
        }

        /* Cards & Widgets */
        .card-custom {
            border: 1px solid var(--pos-card-border);
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-custom:hover {
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }

        .card-stat {
            border: 1px solid var(--pos-card-border);
            border-radius: 14px;
            background: #ffffff;
            padding: 1.35rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease;
        }

        .card-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
        }

        /* Buttons & Badges */
        .btn-primary-custom {
            background-color: var(--pos-primary);
            border-color: var(--pos-primary);
            color: #ffffff;
            font-weight: 500;
            border-radius: 10px;
            padding: 0.55rem 1.15rem;
            transition: all 0.2s ease;
        }

        .btn-primary-custom:hover {
            background-color: var(--pos-primary-hover);
            border-color: var(--pos-primary-hover);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        /* Badges */
        .badge-role {
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            letter-spacing: 0.02em;
        }

        /* Sidebar Collapsed State (Desktop & Mobile) */
        body.sidebar-collapsed #sidebar-wrapper {
            margin-left: -255px;
        }
        body.sidebar-collapsed #page-content-wrapper {
            margin-left: 0;
        }

        @media (max-width: 991.98px) {
            #sidebar-wrapper {
                margin-left: -255px;
            }
            #sidebar-wrapper.toggled,
            body.sidebar-mobile-open #sidebar-wrapper {
                margin-left: 0;
            }
            #page-content-wrapper {
                margin-left: 0;
            }
        }

        /* Print Media Styles: Clean Tabular Report Output */
        @media print {
            /* Hide all web interface components */
            #sidebar-wrapper,
            .top-navbar,
            .btn,
            button,
            form,
            .card-filter,
            .filter-card,
            .no-print,
            .d-print-none,
            .alert,
            nav,
            footer,
            .modal,
            .pagination,
            .breadcrumb,
            .input-group,
            select,
            input {
                display: none !important;
            }

            /* Clean document canvas */
            html, body {
                background: #ffffff !important;
                background-color: #ffffff !important;
                color: #000000 !important;
                font-family: Arial, Helvetica, sans-serif !important;
                font-size: 10pt !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            #wrapper,
            #page-content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                min-width: 100% !important;
                display: block !important;
                float: none !important;
            }

            .container,
            .container-fluid {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            /* Remove web card visuals */
            .card,
            .card-custom {
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            /* Format pure report table */
            .table-responsive {
                overflow: visible !important;
                display: block !important;
            }

            table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-top: 8px !important;
                margin-bottom: 12px !important;
                font-size: 9.5pt !important;
                color: #000000 !important;
            }

            thead {
                display: table-header-group !important;
            }

            tfoot {
                display: table-footer-group !important;
            }

            tr {
                page-break-inside: avoid !important;
            }

            th, td {
                border: 1px solid #333333 !important;
                padding: 5px 7px !important;
                color: #000000 !important;
                background: transparent !important;
            }

            th {
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-weight: bold !important;
                font-size: 9pt !important;
            }

            tfoot tr td {
                background-color: #f7f7f7 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-weight: bold !important;
            }

            .badge {
                border: none !important;
                background: transparent !important;
                color: #000000 !important;
                padding: 0 !important;
                font-weight: normal !important;
                font-size: 9pt !important;
            }

            .d-print-block {
                display: block !important;
            }

            .d-print-table {
                display: table !important;
            }

            .d-print-table-row {
                display: table-row !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="@yield('body-class', '')">

    <!-- Sidebar Wrapper -->
    <nav id="sidebar-wrapper">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <i class="bi bi-cart-check-fill"></i>
            </div>
            <div>
                <div class="fw-bold text-white fs-6 lh-1">{{ \App\Models\Setting::get('store_name', 'Kasir POS') }}</div>
                <small class="text-white-50" style="font-size: 0.75rem;">Point of Sale System</small>
            </div>
        </div>

        <div class="py-2">
            <!-- Nav: Kasir POS Shortcut -->
            <div class="px-3 my-2">
                <a href="{{ route('pos.index') }}" class="btn btn-warning w-100 fw-bold py-2 text-dark shadow-sm d-flex align-items-center justify-content-center gap-2" style="border-radius: 10px;">
                    <i class="bi bi-calculator fs-5"></i> Buka Kasir (POS)
                </a>
            </div>

            <!-- Nav: Utama -->
            <div class="sidebar-heading">Menu Utama</div>
            <a href="{{ route('dashboard') }}" class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>

            <!-- Nav: Shift Kasir -->
            <a href="{{ route('cash-shifts.index') }}" class="nav-link-custom {{ request()->routeIs('cash-shifts.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Shift Kasir
            </a>

            <!-- Nav: Transaksi Penjualan -->
            <a href="{{ route('sales.index') }}" class="nav-link-custom {{ request()->routeIs('sales.*') && !request()->routeIs('sales.receipt') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> Riwayat Penjualan
            </a>

            <!-- Nav: Inventori & Produk -->
            @if(auth()->user()->hasRole(['super-admin', 'admin', 'gudang']))
            <div class="sidebar-heading">Inventori & Produk</div>
            <a href="{{ route('products.index') }}" class="nav-link-custom {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam-fill"></i> Data Produk
            </a>
            <a href="{{ route('categories.index') }}" class="nav-link-custom {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <i class="bi bi-tags-fill"></i> Kategori
            </a>
            <a href="{{ route('units.index') }}" class="nav-link-custom {{ request()->routeIs('units.*') ? 'active' : '' }}">
                <i class="bi bi-rulers"></i> Satuan Produk
            </a>
            <a href="{{ route('stocks.index') }}" class="nav-link-custom {{ request()->routeIs('stocks.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i> Manajemen Stok
            </a>
            <a href="{{ route('stock-opnames.index') }}" class="nav-link-custom {{ request()->routeIs('stock-opnames.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check"></i> Stok Opname
            </a>
            @endif

            <!-- Nav: Pembelian & Supplier -->
            @if(auth()->user()->hasRole(['super-admin', 'admin', 'gudang']))
            <div class="sidebar-heading">Pembelian (PO)</div>
            <a href="{{ route('purchases.index') }}" class="nav-link-custom {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                <i class="bi bi-bag-check-fill"></i> Pembelian Supplier
            </a>
            <a href="{{ route('suppliers.index') }}" class="nav-link-custom {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Data Supplier
            </a>
            @endif

            <!-- Nav: Pelanggan & Retur -->
            <div class="sidebar-heading">Pelanggan & Retur</div>
            <a href="{{ route('customers.index') }}" class="nav-link-custom {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Pelanggan / Member
            </a>
            <a href="{{ route('returns.sales.index') }}" class="nav-link-custom {{ request()->routeIs('returns.sales.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-counterclockwise"></i> Retur Penjualan
            </a>
            @if(auth()->user()->hasRole(['super-admin', 'admin', 'gudang']))
            <a href="{{ route('returns.purchases.index') }}" class="nav-link-custom {{ request()->routeIs('returns.purchases.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-return-left"></i> Retur Pembelian
            </a>
            @endif

            <!-- Nav: Laporan -->
            @if(auth()->user()->hasRole(['super-admin', 'admin', 'owner']))
            <div class="sidebar-heading">Laporan & Audit</div>
            <a href="{{ route('reports.sales') }}" class="nav-link-custom {{ request()->routeIs('reports.sales') ? 'active' : '' }}">
                <i class="bi bi-graph-up"></i> Penjualan Harian
            </a>
            <a href="{{ route('reports.sales-by-product') }}" class="nav-link-custom {{ request()->routeIs('reports.sales-by-product*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> Penjualan per Produk
            </a>
            <a href="{{ route('reports.sales-by-turnover') }}" class="nav-link-custom {{ request()->routeIs('reports.sales-by-turnover*') ? 'active' : '' }}">
                <i class="bi bi-currency-dollar"></i> Penjualan per Omset
            </a>
            <a href="{{ route('reports.sales-by-customer') }}" class="nav-link-custom {{ request()->routeIs('reports.sales-by-customer*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Penjualan per Customer
            </a>
            <a href="{{ route('reports.sales-by-cashier') }}" class="nav-link-custom {{ request()->routeIs('reports.sales-by-cashier*') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i> Penjualan per Kasir
            </a>
            <a href="{{ route('reports.finance-cash-bank') }}" class="nav-link-custom {{ request()->routeIs('reports.finance-cash-bank*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Keuangan Cash & TF
            </a>
            <a href="{{ route('reports.stock') }}" class="nav-link-custom {{ request()->routeIs('reports.stock') ? 'active' : '' }}">
                <i class="bi bi-box2"></i> Stok Barang
            </a>
            <a href="{{ route('reports.stock-shrinkage') }}" class="nav-link-custom {{ request()->routeIs('reports.stock-shrinkage*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle"></i> Penyusutan Stok
            </a>
            <a href="{{ route('reports.purchases') }}" class="nav-link-custom {{ request()->routeIs('reports.purchases*') ? 'active' : '' }}">
                <i class="bi bi-cart-dash"></i> Laporan Pembelian
            </a>
            <a href="{{ route('reports.profit-loss') }}" class="nav-link-custom {{ request()->routeIs('reports.profit-loss*') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i> Laporan Laba Rugi
            </a>
            @endif

            <!-- Nav: Pengaturan -->
            @if(auth()->user()->hasRole(['super-admin', 'admin']))
            <div class="sidebar-heading">Sistem & Pengaturan</div>
            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('users.index') }}" class="nav-link-custom {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i> Kelola Pengguna
            </a>
            @endif
            <a href="{{ route('settings.index') }}" class="nav-link-custom {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear-fill"></i> Pengaturan Toko
            </a>
            @endif

            <div class="p-3 text-center text-white-50 small mt-4" style="font-size: 0.7rem;">
                POS Kasir v1.0 &copy; {{ date('Y') }}
            </div>
        </div>
    </nav>

    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <button class="btn btn-light me-3" id="sidebarToggle" type="button" title="Buka / Sembunyikan Menu Sidebar" aria-label="Toggle Sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>

            <!-- Status Shift Kasir Terkini -->
            @php
                $userOpenShift = auth()->user()->currentOpenShift;
            @endphp
            <div class="d-none d-md-flex align-items-center gap-2">
                @if($userOpenShift)
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill d-flex align-items-center gap-2">
                        <span class="spinner-grow spinner-grow-sm" style="width: 8px; height: 8px;" role="status"></span>
                        Shift Kasir Aktif (Mulai: {{ $userOpenShift->opened_at->format('H:i') }})
                    </span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill">
                        <i class="bi bi-lock-fill me-1"></i> Shift Kasir Belum Dibuka
                    </span>
                @endif
            </div>

            <div class="ms-auto d-flex align-items-center gap-2 gap-sm-3">
                @yield('navbar-actions')

                <!-- Short Info Key Shortcuts -->
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill d-none d-sm-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#shortcutModal">
                    <i class="bi bi-keyboard"></i> Shortcut Keyboard
                </button>

                <!-- User Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 px-3 py-2 rounded-pill border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="text-start d-none d-sm-block">
                            <div class="fw-semibold lh-1" style="font-size: 0.85rem;">{{ auth()->user()->name }}</div>
                            <small class="text-muted" style="font-size: 0.72rem;">{{ auth()->user()->role?->name ?? 'User' }}</small>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2 mt-2" style="border-radius: 12px; min-width: 200px;">
                        <li><h6 class="dropdown-header">Akun Saya</h6></li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('profile.edit') }}">
                                <i class="bi bi-person me-2 text-primary"></i> Edit Profil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Keluar (Logout)
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="@yield('main-class', 'p-3 p-md-4 flex-grow-1')">
            <!-- Flash Message Alerts -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3 d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3 d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3 d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle-fill fs-5 me-2 text-info"></i>
                    <div>{{ session('info') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
                    <div class="fw-bold mb-1"><i class="bi bi-x-circle-fill me-1"></i> Terdapat kesalahan pada formulir:</div>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Modal Shortcut Keyboard -->
    <div class="modal fade" id="shortcutModal" tabindex="-1" aria-labelledby="shortcutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="shortcutModalLabel"><i class="bi bi-keyboard text-primary me-2"></i> Shortcut Keyboard Kasir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Gunakan tombol keyboard berikut pada halaman Kasir (POS) untuk mempercepat pelayanan kasir:</p>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Fokus Scan / Pencarian Produk</span>
                            <kbd class="bg-dark text-white px-2 py-1 rounded">F2</kbd>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Pilih / Cari Pelanggan</span>
                            <kbd class="bg-dark text-white px-2 py-1 rounded">F3</kbd>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Buka Pembayaran / Checkout</span>
                            <kbd class="bg-dark text-white px-2 py-1 rounded">F4</kbd>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Tahan Transaksi (Hold)</span>
                            <kbd class="bg-dark text-white px-2 py-1 rounded">F7</kbd>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Daftar Transaksi Ditahan</span>
                            <kbd class="bg-dark text-white px-2 py-1 rounded">F8</kbd>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Tutup Modal / Batalkan Pilihan</span>
                            <kbd class="bg-dark text-white px-2 py-1 rounded">ESC</kbd>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>Konfirmasi Bayar di Modal</span>
                            <kbd class="bg-dark text-white px-2 py-1 rounded">Enter</kbd>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Sidebar Toggle for Desktop and Mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                if (window.innerWidth >= 992) {
                    document.body.classList.toggle('sidebar-collapsed');
                } else {
                    document.getElementById('sidebar-wrapper').classList.toggle('toggled');
                }
            });
        }

        // Global Rupiah Currency Formatter for .input-rupiah
        function formatRupiahString(value) {
            if (value === null || value === undefined) return '';
            let clean = value.toString().replace(/\D/g, '');
            if (!clean) return '';
            return new Intl.NumberFormat('id-ID').format(clean);
        }

        function parseRupiahNumber(value) {
            if (!value) return 0;
            let clean = value.toString().replace(/\D/g, '');
            return clean ? parseInt(clean, 10) : 0;
        }

        function attachRupiahFormatter(input) {
            if (input._hasRupiahFormatter) return;
            input._hasRupiahFormatter = true;

            input.setAttribute('inputmode', 'numeric');
            input.setAttribute('autocomplete', 'off');

            function applyFormat() {
                const initialCursor = input.selectionStart || 0;
                const oldVal = input.value || '';

                const dotsBefore = (oldVal.slice(0, initialCursor).match(/\./g) || []).length;
                const digitsBefore = initialCursor - dotsBefore;

                const rawDigits = oldVal.replace(/\D/g, '');
                if (!rawDigits) {
                    input.value = '';
                    return;
                }

                const sanitizedDigits = rawDigits.replace(/^0+(?=\d)/, '');
                const formatted = new Intl.NumberFormat('id-ID').format(sanitizedDigits);
                input.value = formatted;

                let targetCursor = formatted.length;
                let seenDigits = 0;
                for (let i = 0; i < formatted.length; i++) {
                    if (/\d/.test(formatted[i])) {
                        seenDigits++;
                    }
                    if (seenDigits >= digitsBefore) {
                        targetCursor = i + 1;
                        break;
                    }
                }
                input.setSelectionRange(targetCursor, targetCursor);
            }

            input.addEventListener('input', applyFormat);

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace') {
                    const pos = input.selectionStart;
                    if (pos === input.selectionEnd && pos > 0 && input.value[pos - 1] === '.') {
                        e.preventDefault();
                        const before = input.value.slice(0, pos - 2);
                        const after = input.value.slice(pos);
                        input.value = before + after;
                        input.setSelectionRange(pos - 2, pos - 2);
                        applyFormat();
                    }
                }
            });

            if (input.value && input.value.trim() !== '') {
                const rawNum = Math.round(parseFloat(input.value.toString().replace(/\./g, '').replace(',', '.'))) || 0;
                input.value = new Intl.NumberFormat('id-ID').format(rawNum);
            }
        }

        function initRupiahInputs(container = document) {
            container.querySelectorAll('.input-rupiah').forEach(attachRupiahFormatter);
        }

        document.addEventListener('DOMContentLoaded', function () {
            initRupiahInputs();
        });

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (form && form.querySelectorAll) {
                form.querySelectorAll('.input-rupiah').forEach(inp => {
                    inp.value = inp.value.replace(/\./g, '');
                });
                setTimeout(() => {
                    form.querySelectorAll('.input-rupiah').forEach(inp => {
                        if (inp.value) {
                            inp.value = formatRupiahString(inp.value);
                        }
                    });
                }, 200);
            }
        }, true);
    </script>
    @stack('scripts')
</body>
</html>
