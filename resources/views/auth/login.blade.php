<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - {{ $store['name'] }} Web Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0284C7;
            --primary-dark: #0369A1;
            --bg: #F8FAFC;
            --surface: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --danger: #DC2626;
            --danger-light: #FEE2E2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }

        .login-card {
            background-color: var(--surface);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
            max-width: 400px;
            width: 100%;
            padding: 2.25rem 2rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-badge {
            width: 48px;
            height: 48px;
            background-color: var(--primary);
            color: #FFFFFF;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .login-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .login-subtitle {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 0.35rem;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 0.65rem 0.85rem;
            font-size: 0.875rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            background-color: #FFFFFF;
            color: var(--text-main);
            outline: none;
            transition: border-color 0.15s ease;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }

        .btn-submit {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary);
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease;
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            background-color: var(--primary-dark);
        }

        .error-message {
            background-color: var(--danger-light);
            color: var(--danger);
            border: 1px solid #FECACA;
            border-radius: 8px;
            padding: 0.65rem 0.85rem;
            font-size: 0.8rem;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            @if($store['logo_url'])<img src="{{ $store['logo_url'] }}" alt="Logo toko" style="width: 64px; height: 64px; object-fit: contain;">@else<div class="brand-badge">{{ mb_substr($store['name'], 0, 1) }}</div>@endif
            <h1 class="login-title">{{ $store['name'] }}</h1>
            <p class="login-subtitle">Masuk ke Web Dashboard Owner</p>
        </div>

        @if($errors->any())
            <div class="error-message">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="Masukkan username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn-submit">Masuk ke Dashboard</button>
        </form>
    </div>
<script src="{{ asset('js/password-toggle.js') }}" defer></script>
</body>
</html>
