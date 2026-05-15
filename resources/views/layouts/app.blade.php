<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1a2236">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Baidoos POS">
    <title>@yield('title', 'Baidoos POS')</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-size: 0.92rem; }
        .sidebar { min-height: 100vh; background: #1a2236; width: 230px; position: fixed; top: 0; left: 0; z-index: 100; }
        .sidebar .brand { padding: 1.2rem 1rem; background: #111827; }
        .sidebar .brand h5 { color: #fff; margin: 0; font-weight: 700; font-size: 1rem; }
        .sidebar .nav-link { color: #9ca3af; padding: .6rem 1rem; border-radius: 6px; margin: 2px 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #2d3a55; color: #fff; }
        .sidebar .nav-link i { margin-right: 8px; }
        .sidebar .nav-section { color: #6b7280; font-size: .7rem; text-transform: uppercase; padding: .5rem 1rem .2rem; letter-spacing: .08em; }
        .main-wrap { margin-left: 230px; min-height: 100vh; }
        .topbar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: .55rem 1.5rem; }
        .content-body { padding: 1.5rem; }
        .stat-card { background: #fff; border-radius: 10px; padding: 1.2rem; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
        .badge-service { background: #dbeafe; color: #1d4ed8; }
        .badge-product { background: #d1fae5; color: #065f46; }
        .table thead th { font-size: .8rem; text-transform: uppercase; color: #6b7280; background: #f9fafb; }
        .btn-pos { width: 100%; padding: 1rem .5rem; font-size: .85rem; text-align: center; border-radius: 8px; cursor: pointer; border: 2px solid #e5e7eb; background: #fff; }
        .btn-pos:hover { border-color: #3b82f6; background: #eff6ff; }
        .btn-pos.in-cart { border-color: #10b981; background: #ecfdf5; }
        .receipt-table td, .receipt-table th { font-size: .85rem; }
        @media print { .sidebar, .topbar, .no-print { display: none !important; } .main-wrap { margin-left: 0 !important; } }
    </style>
    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<nav class="sidebar d-flex flex-column">
    <div class="brand">
        <h5><i class="bi bi-shop"></i> Baidoos POS</h5>
        <small class="text-secondary" style="font-size:.7rem">
            {{ auth()->user()->branch->name ?? 'All Branches' }}
        </small>
    </div>

    <div class="mt-2 flex-grow-1">
        <div class="nav-section">Main</div>
        <a href="{{ route('dashboard') }}" class="nav-link @active('dashboard')">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        @if(auth()->user()->isCashier())
        <div class="nav-section">POS</div>
        <a href="{{ route('pos.sale') }}" class="nav-link @active('pos.sale')">
            <i class="bi bi-cart-plus"></i> New Sale
        </a>
        <a href="{{ route('staff.index') }}" class="nav-link @active('staff.index')">
            <i class="bi bi-person-lines-fill"></i> Service Staff
        </a>
        @endif

        <div class="nav-section">Reports</div>
        <a href="{{ route('day-closing.index') }}" class="nav-link @active('day-closing.index')">
            <i class="bi bi-calendar-check"></i> Day Closings
        </a>
        @if(auth()->user()->isCashier())
        <a href="{{ route('day-closing.close') }}" class="nav-link">
            <i class="bi bi-lock"></i> Close Today
        </a>
        @endif

        @if(auth()->user()->isOwner() && !auth()->user()->isSuperAdmin())
        <div class="nav-section">Admin</div>
        <a href="{{ route('pos.sale') }}" class="nav-link @active('pos.sale')">
            <i class="bi bi-cart-plus"></i> New Sale
        </a>
        <a href="{{ route('admin.branches.index') }}" class="nav-link @active('admin.branches.index')">
            <i class="bi bi-building"></i> Branches
        </a>
        <a href="{{ route('admin.business-types.index') }}" class="nav-link @active('admin.business-types.index')">
            <i class="bi bi-tags"></i> Categories
        </a>
        <a href="{{ route('admin.items.index') }}" class="nav-link @active('admin.items.index')">
            <i class="bi bi-grid"></i> Items / Services
        </a>
        <a href="{{ route('admin.customers.index') }}" class="nav-link @active('admin.customers.index')">
            <i class="bi bi-people"></i> Customers
        </a>
        <a href="{{ route('admin.users.index') }}" class="nav-link @active('admin.users.index')">
            <i class="bi bi-people"></i> Users
        </a>
        <a href="{{ route('reports.index') }}" class="nav-link @active('reports.index')">
            <i class="bi bi-bar-chart-line"></i> Reports
        </a>
        <a href="{{ route('staff.index') }}" class="nav-link @active('staff.index')">
            <i class="bi bi-person-lines-fill"></i> Service Staff
        </a>
        @endif

        @if(auth()->user()->isSuperAdmin())
        <div class="nav-section">Super Admin</div>
        <a href="{{ route('pos.sale') }}" class="nav-link @active('pos.sale')">
            <i class="bi bi-cart-plus"></i> New Sale
        </a>
        <a href="{{ route('day-closing.close') }}" class="nav-link @active('day-closing.close')">
            <i class="bi bi-lock"></i> Close A Branch
        </a>
        <a href="{{ route('admin.branches.index') }}" class="nav-link @active('admin.branches.index')">
            <i class="bi bi-building"></i> Branches
        </a>
        <a href="{{ route('admin.business-types.index') }}" class="nav-link @active('admin.business-types.index')">
            <i class="bi bi-tags"></i> Categories
        </a>
        <a href="{{ route('admin.items.index') }}" class="nav-link @active('admin.items.index')">
            <i class="bi bi-grid"></i> Items / Services
        </a>
        <a href="{{ route('admin.customers.index') }}" class="nav-link @active('admin.customers.index')">
            <i class="bi bi-people"></i> Customers
        </a>
        <a href="{{ route('admin.users.index') }}" class="nav-link @active('admin.users.index')">
            <i class="bi bi-people"></i> Users
        </a>
        <a href="{{ route('reports.index') }}" class="nav-link @active('reports.index')">
            <i class="bi bi-bar-chart-line"></i> Reports
        </a>
        <a href="{{ route('staff.index') }}" class="nav-link @active('staff.index')">
            <i class="bi bi-person-lines-fill"></i> Service Staff
        </a>
        <a href="{{ route('superadmin.settings') }}" class="nav-link @active('superadmin.settings')">
            <i class="bi bi-gear-fill"></i> System Settings
        </a>
        <button type="button" class="nav-link text-danger w-100 text-start border-0 bg-transparent"
            data-bs-toggle="modal" data-bs-target="#resetSalesModal">
            <i class="bi bi-trash3"></i> Reset All Sales
        </button>
        @endif
    </div>

    <div class="p-3 border-top" style="border-color:#2d3a55 !important">
        <div class="text-secondary" style="font-size:.75rem">
            <i class="bi bi-person-circle"></i>
            {{ auth()->user()->name }}
            <span class="badge bg-secondary ms-1" style="font-size:.6rem">{{ auth()->user()->role }}</span>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary w-100" style="font-size:.75rem">
                <i class="bi bi-box-arrow-left"></i> Logout
            </button>
        </form>
    </div>
</nav>

{{-- Main Content --}}
<div class="main-wrap">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <span id="posTopbarBtn"></span>
            <h6 class="mb-0 fw-semibold text-dark">@yield('page-title', 'Dashboard')</h6>
        </div>
        <div class="text-muted" style="font-size:.8rem">
            <i class="bi bi-calendar3"></i> {{ now()->format('l, d M Y') }}
        </div>
    </div>

    <div class="content-body">

        {{-- Flash messages --}}
        @foreach(['success' => 'success', 'error' => 'danger', 'info' => 'info', 'warning' => 'warning'] as $msg => $type)
            @if(session($msg))
                <div class="alert alert-{{ $type }} alert-dismissible fade show py-2" role="alert">
                    {{ session($msg) }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        @endforeach

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register("{{ asset('sw.js') }}").catch(function () {
                // Ignore service worker registration errors.
            });
        });
    }
</script>

@if(auth()->check() && auth()->user()->isSuperAdmin())
{{-- Reset All Sales Confirmation Modal --}}
<div class="modal fade" id="resetSalesModal" tabindex="-1" aria-labelledby="resetSalesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="resetSalesModalLabel">
                    <i class="bi bi-exclamation-triangle-fill"></i> Reset All Sales
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2"><strong>This will permanently delete:</strong></p>
                <ul>
                    <li>All sales records</li>
                    <li>All sale items</li>
                    <li>All day closings</li>
                </ul>
                <p class="text-danger fw-bold">This action cannot be undone.</p>
                <label class="form-label">Type <code>RESET</code> to confirm:</label>
                <input type="text" id="resetConfirmInput" class="form-control" placeholder="RESET">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('superadmin.reset-sales') }}" id="resetSalesForm">
                    @csrf
                    <input type="hidden" name="confirm" id="resetConfirmHidden">
                    <button type="submit" class="btn btn-danger" id="resetSalesBtn" disabled>
                        <i class="bi bi-trash3"></i> Delete Everything
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('resetConfirmInput').addEventListener('input', function () {
        const val = this.value.trim();
        document.getElementById('resetConfirmHidden').value = val;
        document.getElementById('resetSalesBtn').disabled = val !== 'RESET';
    });
</script>
@endif

@stack('scripts')
</body>
</html>
