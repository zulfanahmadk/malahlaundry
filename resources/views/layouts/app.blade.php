<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'Beranda') · {{ $store['name'] }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('figma/e5cb0.svg') }}">
        <link rel="stylesheet" href="{{ asset('css/workspace.css').'?v='.filemtime(public_path('css/workspace.css')) }}">
        @stack('styles')
    </head>
    <body class="workspace-page">
        @php
            $navGroups = [
                ['Operasional', 'imgSidebarIconOperasional', [['Cucian','transactions.*','transactions.index','imgSidebarIconCucian'],['Pelanggan','customers.*','customers.index','imgSidebarIconPelanggan'],['Laporan','reports.*','reports.index','imgSidebarIconLaporan']]],
                ['Manajemen', 'imgSidebarIconManajemen', [['Layanan','services.*','services.index','imgSidebarIconLayanan'],['Cabang','branches.*','branches.index','imgSidebarIconCabang'],['Pengguna','users.*','users.index','imgSidebarIconPengguna'],['Presensi','attendances.*','attendances.index','imgSidebarIconPresensi']]],
                ['Pengaturan', 'imgSidebarIconPengaturan', [['Toko & Nota','settings.*','settings.edit','imgSidebarIconTokoNota'],['Jam Buka','hours.*','hours.edit','imgSidebarIconJamBuka'],['Template Pesan','templates.*','templates.edit','imgSidebarIconTemplatePesan'],['Profil','profile.*','profile.edit','imgSidebarIconProfil'],['Sinkronisasi','sync.*','sync.index','imgSidebarIconSinkronisasi'],['APK Android','apk.*','apk.index','imgIconDevice'],['Notifikasi','notifications.*','notifications.index','imgSidebarIconNotifikasi']]],
            ];
            $initials = \App\Support\Workspace::initials(auth()->user()->name);
            $currentBranch = request()->attributes->get('branch');
        @endphp
        <aside class="sidebar" id="sidebar" aria-label="Menu utama">
            <div class="sidebar-brand">
                <a class="brand" href="{{ route('dashboard') }}">
                    <span class="brand-mark">
                        <x-figma-icon name="imgSidebarIconBrand" />
                    </span>
                    <span>
                        <strong>Malah Laundry</strong>
                        <small>Owner workspace</small>
                    </span>
                </a>
                <button class="sidebar-close" type="button" data-sidebar-close aria-label="Tutup menu" title="Tutup menu">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <nav>
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                    <x-figma-icon name="imgSidebarIconBeranda" />Beranda</a>
                @foreach($navGroups as [$group, $icon, $links])
                @php($activeGroup = collect($links)->contains(fn ($link) => request()->routeIs($link[1])))
                <details class="nav-group {{ $activeGroup ? 'is-active' : '' }}" data-nav-group="{{ \Illuminate\Support\Str::slug($group) }}" open>
                    <summary class="nav-group-title">
                        <span>
                            <x-figma-icon :name="$icon" />{{ $group }}</span>
                        <x-figma-icon class="nav-group-chevron" :name="$activeGroup && $group !== 'Operasional' ? 'imgSidebarChevronExpanded1' : 'imgSidebarChevronExpanded'" />
                    </summary>
                    <div class="nav-group-links">
                        @foreach($links as [$label, $pattern, $route, $navIcon])<a class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}" href="{{ route($route) }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>
                            <x-figma-icon :name="$navIcon" />{{ $label }}</a>@endforeach
                    </div>
                </details>
                @endforeach
            </nav>
            <div class="sidebar-profile">
                <span class="avatar">{{ $initials }}</span>
                <details>
                    <summary>
                        <strong>{{ auth()->user()->name }}</strong>
                        <br>
                        <small>Owner</small>
                    </summary>
                    <a href="{{ route('profile.edit') }}">Profil akun</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="logout">Keluar akun</button>
                    </form>
                </details>
            </div>
        </aside>
        <button class="overlay" data-sidebar-close aria-label="Tutup menu" tabindex="-1">
        </button>
        <div class="main-wrapper">
            <header class="topbar">
                <div class="topbar-heading">
                    <button class="menu-toggle" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="true" aria-label="Tutup menu" title="Tutup menu">
                        <span aria-hidden="true">☰</span>
                    </button>
                    <div>
                        <h1>@yield('page_title', 'Ringkasan toko')</h1>
                        <p>@yield('page_subtitle', $store['name'])</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    @yield('page_actions')
                    @if(!$__env->hasSection('hide_branch'))
                    <form action="{{ route('branches.select') }}" method="POST" class="branch-select">@csrf<span class="select-field">
                            <select name="branch_id" aria-label="Cabang aktif" data-branch-select>@foreach($branches as $branchOption)<option value="{{ $branchOption->id }}" @selected($branchOption->id === $currentBranch->id)>{{ $branchOption->store_name }} — {{ $branchOption->name }}{{ $branchOption->active ? '' : ' (nonaktif)' }}</option>@endforeach</select>
                            <x-figma-icon name="imgIconChevron" />
                        </span>
                        <noscript>
                            <button class="btn btn-secondary" type="submit">Pilih cabang</button>
                        </noscript>
                    </form>
                    @endif
                    <button type="button" class="bell-button" id="notification-toggle" aria-label="Buka pemberitahuan" aria-expanded="false" aria-controls="notification-popover">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                        @if($workspaceNotifications->where('read', false)->isNotEmpty())<span class="unread-dot" aria-label="Ada notifikasi belum dibaca"></span>@endif
                    </button>
                    <span class="online">Web aktif</span>
                </div>
                <section class="notifications-popover" id="notification-popover" hidden aria-label="Notifikasi terbaru">
                    <div class="popover-head">
                        <h2>Notifikasi</h2>
                        <span class="badge badge-primary">{{ $workspaceNotifications->where('read', false)->count() }} baru</span>
                    </div>
                    @forelse($workspaceNotifications->take(4) as $notification)<a class="notification-row {{ $notification['read'] ? '' : 'unread' }}" href="{{ $notification['url'] }}">
                        <span class="stat-icon {{ in_array($notification['category'], ['CUCIAN','PRESENSI']) ? 'orange' : ($notification['category'] === 'AKUN' ? 'green' : '') }}">
                            <x-figma-icon node="127-45" :name="match($notification['category']) {'CUCIAN', 'PRESENSI' => 'imgIconAlert1', 'SINKRON' => ($notification['stale'] ?? false) ? 'imgIconDevice' : 'imgIconRefresh', default => 'imgIconCheck1'}" />
                        </span>
                        <span>
                            <strong>{{ $notification['title'] }}</strong>
                            <small>{{ \App\Support\Workspace::date($notification['time']) }} WIB</small>
                        </span>@if(!$notification['read'])<x-figma-icon name="imgUnreadDot" node="127-45" />@endif</a>@empty<p class="empty">Belum ada pemberitahuan.</p>@endforelse
                    <a class="popover-footer" href="{{ route('notifications.index') }}">Lihat semua notifikasi →</a>
                </section>
            </header>
            <main class="content-body" id="main-content">
                @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
                @if($errors->any())<div class="alert alert-danger" role="alert" data-reopen-dialog="{{ old('_workspace_dialog', '') }}">
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>@endif
                @yield('content')
            </main>
        </div>
        <script src="{{ asset('js/workspace.js').'?v='.filemtime(public_path('js/workspace.js')) }}" defer>
        </script>
        @stack('scripts')
    </body>
</html>
