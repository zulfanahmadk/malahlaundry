<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Masuk · Malah Laundry</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('figma/e5cb0.svg') }}">
        <link rel="stylesheet" href="{{ asset('css/workspace.css').'?v='.filemtime(public_path('css/workspace.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/action-loading.css').'?v='.filemtime(public_path('css/action-loading.css')) }}">
    </head>
    <body class="login-page">
        <main class="login-shell">
            <section class="login-brand">
                <img class="glow glow-one" src="{{ \App\Support\Workspace::asset('127-3', 'imgGlow') }}" alt="">
                <img class="glow glow-two" src="{{ \App\Support\Workspace::asset('127-3', 'imgGlow1') }}" alt="">
                <div class="brand">
                    <span class="brand-mark">◎</span>
                    <span>
                        <strong>Malah Laundry</strong>
                        <small>Owner workspace</small>
                    </span>
                </div>
                <div class="login-story">
                    <p class="eyebrow">Operasional tercatat rapi</p>
                    <h1>Pantau seluruh toko dari satu ruang kerja.</h1>
                    <p class="promise">Ringkasan omzet, cucian, pelanggan, layanan, cabang, serta presensi dalam tampilan yang jelas dan konsisten.</p>
                </div>
                <div class="login-footer">
                    <span>✓ Akses owner</span>
                    <span>☁ Data langsung</span>
                </div>
            </section>
            <section class="login-form-panel">
                <h2>Masuk ke Malah Laundry</h2>
                <p class="intro">Gunakan akun owner atau admin yang telah terdaftar.</p>
                @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
                <form action="{{ url('/login') }}" method="POST">@csrf
                    <div class="form-group">
                        <label for="username">Nama pengguna</label>
                        <input id="username" name="username" value="{{ old('username') }}" placeholder="Masukkan username" autocomplete="username" maxlength="50" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Kata sandi</label>
                        <input id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" maxlength="72" required>
                    </div>
                    <label class="checkline">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>Ingat perangkat ini</label>
                    <button class="btn btn-primary" type="submit">Masuk</button>
                </form>
                <p class="form-help">Kasir menggunakan aplikasi Android untuk transaksi dan presensi.</p>
                <p class="login-form-footer">Malah Laundry Web Admin · v1.6.0</p>
            </section>
        </main>
        <script src="{{ asset('js/workspace.js').'?v='.filemtime(public_path('js/workspace.js')) }}" defer></script>
        <script src="{{ asset('js/action-loading.js').'?v='.filemtime(public_path('js/action-loading.js')) }}" defer></script>
    </body>
</html>
