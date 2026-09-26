<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Malah Laundry</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 50;
            transition: transform 0.2s ease;
        }

        .brand-header {
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-logo-badge {
            width: 38px;
            height: 38px;
            background-color: var(--primary);
            color: #FFFFFF;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .brand-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.01em;
        }

        .brand-sub {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .nav-list {
            list-style: none;
            padding: 1rem 0.75rem;
            flex: 1;
            overflow-y: auto;
        }

        .nav-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.05em;
            padding: 0.5rem 0.75rem 0.25rem;
            margin-top: 0.5rem;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.75rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.15s ease;
        }

        .nav-link:hover {
            color: var(--primary);
            background-color: var(--primary-light);
        }

        .nav-link.active {
            color: var(--primary);
            background-color: var(--primary-light);
            font-weight: 600;
        }

        .user-footer {
            padding: 1rem;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .user-role {
            font-size: 0.7rem;
            color: var(--primary);
            text-transform: capitalize;
        }

        .btn-logout {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.4rem;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .btn-logout:hover {
            color: var(--danger);
            background-color: var(--danger-light);
        }

        /* Main Content */
        .main-wrapper {
            margin-left: 250px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            height: 64px;
            background-color: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.75rem;
        }

        .page-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .time-badge {
            font-size: 0.75rem;
            color: var(--text-muted);
            background-color: var(--bg);
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            border: 1px solid var(--border);
        }

        .content-body {
            padding: 1.75rem;
            flex: 1;
        }

        /* Utilities */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 8px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #FFFFFF;
        }
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: var(--surface);
            border-color: var(--border);
            color: var(--text-main);
        }
        .btn-secondary:hover {
            background-color: var(--bg);
        }

        .card {
            background-color: var(--surface);
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 1.25rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            margin-bottom: 1.5rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-success { background: var(--success-light); color: var(--success); }
        .badge-warning { background: var(--warning-light); color: #854D0E; }
        .badge-danger { background: var(--danger-light); color: var(--danger); }
        .badge-primary { background: var(--primary-light); color: var(--primary-dark); }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        th {
            text-align: left;
            padding: 0.75rem 1rem;
            background-color: var(--bg);
            color: var(--text-muted);
            font-weight: 600;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Alert notifications */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-size: 0.85rem;
        }
        .alert-success { background: var(--success-light); color: var(--success); border: 1px solid #BBF7D0; }
        .alert-error { background: var(--danger-light); color: var(--danger); border: 1px solid #FECACA; }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="brand-header">
            <div class="brand-logo-badge">M</div>
            <div>
                <div class="brand-title">Malah Laundry</div>
                <div class="brand-sub">Management System</div>
            </div>
        </div>

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

            <li class="nav-label">Master Data (Omnichannel)</li>
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

            <li class="nav-label">Laporan Masif (DLP)</li>
            <li class="nav-item">
                <a href="{{ route('reports.export') }}" class="nav-link">
                    <span>Export Rekapitulasi (Excel)</span>
                </a>
            </li>
        </ul>

        <div class="user-footer">
            <div class="user-info">
                <span class="user-name">{{ auth()->user()->name ?? 'Administrator' }}</span>
                <span class="user-role">{{ auth()->user()->role ?? 'owner' }}</span>
            </div>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn-logout" title="Keluar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-wrapper">
        <header class="topbar">
            <h1 class="page-title">@yield('page_title', 'Dashboard')</h1>
            <div class="topbar-actions">
                <div class="time-badge">{{ now()->translatedFormat('l, d M Y - H:i') }} WIB</div>
            </div>
        </header>

        <main class="content-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
