<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Malah Laundry</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        try {
            document.documentElement.dataset.sidebar =
                localStorage.getItem('malahlaundry.sidebar') === 'closed' ? 'closed' : 'open';
        } catch (_) {
            document.documentElement.dataset.sidebar = 'open';
        }
    </script>
    <style>
        :root {
            --primary: #0284C7;
            --primary-dark: #0369A1;
            --primary-light: #E0F2FE;
            --success: #16A34A;
            --success-light: #DCFCE7;
            --warning: #EAB308;
            --warning-light: #FEF9C3;
            --danger: #DC2626;
            --danger-light: #FEE2E2;
            --bg: #F8FAFC;
            --surface: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --sidebar-width: 260px;
            --header-height: 72px;
            --page-gutter: clamp(1rem, 2.5vw, 2rem);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            line-height: 1.5;
        }
        button, input, select, textarea { font: inherit; }
        button, a { -webkit-tap-highlight-color: transparent; }
        :focus-visible { outline: 3px solid var(--primary); outline-offset: 3px; }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 50;
            width: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            background: var(--surface);
            border-right: 1px solid var(--border);
            transition: transform 0.22s ease, visibility 0.22s;
        }
        .brand-header {
            height: var(--header-height);
            flex: 0 0 var(--header-height);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0 1rem;
            border-bottom: 1px solid var(--border);
        }
        .brand-link {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            min-width: 0;
            flex: 1;
            color: inherit;
            text-decoration: none;
        }
        .brand-logo-badge {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            border-radius: 9px;
            background: var(--primary);
            color: #FFFFFF;
            font-size: 1rem;
            font-weight: 700;
        }
        .brand-copy { min-width: 0; }
        .brand-title { font-size: 0.95rem; font-weight: 700; letter-spacing: -0.02em; white-space: nowrap; }
        .brand-sub { font-size: 0.65rem; color: var(--text-muted); white-space: nowrap; }
        .sidebar-navigation { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; }
        .nav-list { list-style: none; padding: 1rem; }
        .nav-label {
            padding: 1.125rem 0.625rem 0.375rem;
            color: var(--text-muted);
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .nav-label:first-child { padding-top: 0; }
        .nav-item + .nav-item { margin-top: 0.25rem; }
        .nav-link {
            display: flex;
            align-items: center;
            min-height: 42px;
            padding: 0.625rem;
            border-radius: 8px;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.15s ease, background-color 0.15s ease;
        }
        .nav-link:hover, .nav-link.active { color: var(--primary-dark); background: var(--primary-light); }
        .nav-link.active { font-weight: 600; }
        .user-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-shrink: 0;
            min-height: var(--header-height);
            padding: 1rem;
            border-top: 1px solid var(--border);
        }
        .user-info { display: flex; flex-direction: column; min-width: 0; }
        .user-name { font-size: 0.85rem; font-weight: 600; overflow-wrap: anywhere; }
        .user-role { color: var(--primary); font-size: 0.75rem; text-transform: capitalize; }
        .icon-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 36px;
            height: 36px;
            padding: 0;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text-muted);
            cursor: pointer;
        }
        .icon-button:hover { color: var(--primary-dark); background: var(--primary-light); }
        .sidebar-close { width: 32px; height: 32px; border-color: transparent; }
        .btn-logout:hover { color: var(--danger); background: var(--danger-light); }

        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-width: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.22s ease;
        }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            height: var(--header-height);
            flex: 0 0 var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0 var(--page-gutter);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
        }
        .topbar-heading { display: flex; align-items: center; gap: 0.75rem; min-width: 0; }
        .page-title { font-size: 1.125rem; font-weight: 700; line-height: 1.35; overflow-wrap: anywhere; }
        .topbar-actions { flex-shrink: 0; }
        .time-badge {
            padding: 0.375rem 0.625rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg);
            color: var(--text-muted);
            font-size: 0.75rem;
            white-space: nowrap;
        }
        .content-body { min-width: 0; flex: 1; padding: var(--page-gutter); }
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            z-index: 40;
            border: 0;
            background: rgb(15 23 42 / 45%);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.22s ease, visibility 0.22s;
        }
        [data-sidebar='closed'] .sidebar { transform: translateX(-100%); visibility: hidden; }
        [data-sidebar='closed'] .main-wrapper { margin-left: 0; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 36px;
            padding: 0.5rem 1rem;
            border: 1px solid transparent;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            line-height: 1.4;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.15s ease;
        }
        .btn svg { flex-shrink: 0; }
        .btn-primary { background: var(--primary); color: #FFFFFF; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { border-color: var(--border); background: var(--surface); color: var(--text-main); }
        .btn-secondary:hover { background: var(--bg); }
        .card {
            min-width: 0;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--surface);
            box-shadow: 0 1px 3px rgb(0 0 0 / 2%);
        }
        .content-body > :last-child { margin-bottom: 0; }
        .section-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
            margin-bottom: 1.25rem;
        }
        .section-title { font-size: 1rem; font-weight: 700; }
        .section-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
        .grid-layout { display: grid; grid-template-columns: minmax(260px, 340px) minmax(0, 1fr); gap: 1.5rem; align-items: start; }
        .grid-layout > .card { margin-bottom: 0; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.375rem; font-size: 0.8rem; font-weight: 600; }
        .form-control {
            width: 100%;
            min-width: 0;
            padding: 0.625rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text-main);
            font-size: 0.85rem;
        }
        .form-control:focus { outline: 2px solid var(--primary-light); border-color: var(--primary); }
        .badge { display: inline-flex; align-items: center; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: var(--success-light); color: var(--success); }
        .badge-warning { background: var(--warning-light); color: #854D0E; }
        .badge-danger { background: var(--danger-light); color: var(--danger); }
        .badge-primary { background: var(--primary-light); color: var(--primary-dark); }
        .table-responsive { width: 100%; max-width: 100%; overflow-x: auto; overscroll-behavior-x: contain; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); background: var(--bg); color: var(--text-muted); text-align: left; font-weight: 600; }
        td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        .validation-list { margin: 0.35rem 0 0 1.25rem; }
        .alert { padding: 0.75rem 1rem; margin-bottom: 1.25rem; border-radius: 8px; font-size: 0.85rem; }
        .alert-success { background: var(--success-light); color: var(--success); border: 1px solid #BBF7D0; }
        .alert-error { background: var(--danger-light); color: var(--danger); border: 1px solid #FECACA; }

        @media (max-width: 1200px) {
            .topbar-actions { display: none; }
        }
        @media (max-width: 1100px) {
            .grid-layout { grid-template-columns: minmax(0, 1fr); }
        }
        @media (max-width: 1023px) {
            .sidebar { width: min(var(--sidebar-width), calc(100vw - 3rem)); transform: translateX(-100%); visibility: hidden; }
            .main-wrapper { margin-left: 0; }
            [data-mobile-sidebar='open'] .sidebar { transform: translateX(0); visibility: visible; }
            [data-mobile-sidebar='open'] .sidebar-overlay { opacity: 1; visibility: visible; }
            [data-mobile-sidebar='open'] body { overflow: hidden; }
        }
        @media (max-width: 600px) {
            .page-title { font-size: 1rem; }
            .card { padding: 1rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .sidebar, .main-wrapper, .sidebar-overlay { transition: none; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar" aria-label="Navigasi utama" tabindex="-1">
        <div class="brand-header">
            <a href="{{ route('dashboard') }}" class="brand-link" aria-label="Malah Laundry — Dashboard">
                <div class="brand-logo-badge" aria-hidden="true">M</div>
                <div class="brand-copy">
                    <div class="brand-title">Malah Laundry</div>
                    <div class="brand-sub">Management System</div>
                </div>
            </a>
            <button type="button" class="icon-button sidebar-close" aria-controls="sidebar" aria-label="Tutup sidebar" title="Tutup sidebar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m14 6-6 6 6 6"></path>
                </svg>
            </button>
        </div>

        <nav class="sidebar-navigation" aria-label="Menu utama">
        <ul class="nav-list">
            <li class="nav-label">Menu Utama</li>
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                    <span>Transaksi Laundry</span>
                </a>
            </li>

            <li class="nav-label">Master Data</li>
            <li class="nav-item">
                <a href="{{ route('services.index') }}" class="nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}">
                    <span>Layanan &amp; Harga</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <span>Pengguna &amp; Kasir</span>
                </a>
            </li>

            <li class="nav-label">Operasional</li>
            <li class="nav-item">
                <a href="{{ route('attendances.index') }}" class="nav-link {{ request()->routeIs('attendances.*') ? 'active' : '' }}">
                    <span>Absensi Kasir (Selfie)</span>
                </a>
            </li>

            <li class="nav-label">Laporan</li>
            <li class="nav-item">
                <a href="{{ route('reports.export') }}" class="nav-link">
                    <span>Ekspor Rekapitulasi (CSV)</span>
                </a>
            </li>
        </ul>
        </nav>

        <div class="user-footer">
            <div class="user-info">
                <span class="user-name">{{ auth()->user()->name ?? 'Administrator' }}</span>
                <span class="user-role">{{ auth()->user()->role ?? 'owner' }}</span>
            </div>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="icon-button btn-logout" title="Keluar" aria-label="Keluar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </button>
            </form>
        </div>
    </aside>
    <button type="button" class="sidebar-overlay" tabindex="-1" aria-label="Tutup sidebar" aria-hidden="true"></button>

    <!-- Main Content -->
    <div class="main-wrapper" id="main-wrapper">
        <header class="topbar">
            <div class="topbar-heading">
                <button type="button" class="icon-button sidebar-toggle" aria-controls="sidebar" aria-expanded="true" aria-label="Tutup sidebar" title="Tutup sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h1 class="page-title">@yield('page_title', 'Dashboard')</h1>
            </div>
            <div class="topbar-actions">
                <div class="time-badge">{{ now()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('l, d M Y - H:i') }} WIB</div>
            </div>
        </header>

        <main class="content-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error" role="alert">
                    Periksa kembali data yang diisi.
                    <ul class="validation-list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    <script>
        (() => {
            const root = document.documentElement;
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main-wrapper');
            const toggle = document.querySelector('.sidebar-toggle');
            const close = document.querySelector('.sidebar-close');
            const overlay = document.querySelector('.sidebar-overlay');
            const mobile = window.matchMedia('(max-width: 1023px)');
            let desktopOpen = root.dataset.sidebar !== 'closed';
            let mobileOpen = false;

            function render() {
                const open = mobile.matches ? mobileOpen : desktopOpen;
                root.dataset.sidebar = desktopOpen ? 'open' : 'closed';
                root.dataset.mobileSidebar = mobile.matches && mobileOpen ? 'open' : 'closed';
                sidebar.inert = !open;
                sidebar.setAttribute('aria-hidden', String(!open));
                toggle.setAttribute('aria-expanded', String(open));
                toggle.setAttribute('aria-label', open ? 'Tutup sidebar' : 'Buka sidebar');
                toggle.title = open ? 'Tutup sidebar' : 'Buka sidebar';
                main.inert = mobile.matches && mobileOpen;

                if (mobile.matches && mobileOpen) {
                    sidebar.setAttribute('role', 'dialog');
                    sidebar.setAttribute('aria-modal', 'true');
                } else {
                    sidebar.removeAttribute('role');
                    sidebar.removeAttribute('aria-modal');
                }
            }

            function setOpen(open, returnFocus = false) {
                if (mobile.matches) {
                    mobileOpen = open;
                } else {
                    desktopOpen = open;
                    try {
                        localStorage.setItem('malahlaundry.sidebar', open ? 'open' : 'closed');
                    } catch (_) {
                        // Navigation remains usable when browser storage is disabled.
                    }
                }
                render();
                if (open && mobile.matches) close.focus();
                if (returnFocus) toggle.focus();
            }

            toggle.addEventListener('click', () => {
                setOpen(!(mobile.matches ? mobileOpen : desktopOpen));
            });
            close.addEventListener('click', () => setOpen(false, true));
            overlay.addEventListener('click', () => setOpen(false, true));
            document.addEventListener('keydown', event => {
                if (!mobile.matches || !mobileOpen) return;
                if (event.key === 'Escape') {
                    event.preventDefault();
                    setOpen(false, true);
                } else if (event.key === 'Tab') {
                    const focusable = Array.from(sidebar.querySelectorAll('a[href], button:not([disabled])'))
                        .filter(element => element.getClientRects().length > 0);
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];
                    if (event.shiftKey && (document.activeElement === first || document.activeElement === sidebar)) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            });
            mobile.addEventListener('change', () => {
                const focusWasInSidebar = sidebar.contains(document.activeElement);
                mobileOpen = false;
                render();
                if (focusWasInSidebar && sidebar.inert) toggle.focus();
            });
            render();
        })();
    </script>
</body>
</html>
