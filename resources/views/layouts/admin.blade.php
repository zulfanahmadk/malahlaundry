<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') · Admin Sistem</title>
    <link rel="stylesheet" href="{{ asset('css/workspace.css').'?v='.filemtime(public_path('css/workspace.css')) }}">
</head>
<body>
<main class="content-body" style="max-width:1100px;margin:auto">
    <header class="section-head">
        <div><h1>@yield('page_title')</h1><p>Admin sistem · {{ auth()->user()->name }}</p></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-secondary">Keluar</button></form>
    </header>
    <nav aria-label="Menu admin" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px">
        <a class="btn {{ request()->routeIs('admin.dashboard') ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>Ringkasan</a>
        <a class="btn {{ request()->routeIs('admin.apk.*') ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('admin.apk.index') }}" @if(request()->routeIs('admin.apk.*')) aria-current="page" @endif>Versi APK</a>
    </nav>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
<script src="{{ asset('js/workspace.js').'?v='.filemtime(public_path('js/workspace.js')) }}" defer></script>
</body></html>
