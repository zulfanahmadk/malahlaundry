<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Nota Tidak Ditemukan - Malah Laundry</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #F8FAFC;
            color: #1E293B;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }
        .card {
            background: #FFFFFF;
            max-width: 440px;
            width: 100%;
            border-radius: 16px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
            border: 1px solid #E2E8F0;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .icon-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background-color: #FEE2E2;
            color: #DC2626;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
        }
        h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 0.5rem;
        }
        p {
            font-size: 0.875rem;
            color: #64748B;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            display: inline-block;
            background-color: #0284C7;
            color: #FFFFFF;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: background-color 0.15s ease;
        }
        .btn-primary:hover {
            background-color: #0369A1;
        }
        .btn-primary:focus-visible {
            outline: 2px solid #0284C7;
            outline-offset: 3px;
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="icon-circle">
            <svg aria-hidden="true" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
        </div>
        <h1>Nota Tidak Ditemukan</h1>
        <p>Pastikan tautan nota dari WhatsApp sudah lengkap. Jika cucian baru diserahkan, nota mungkin belum tersinkronisasi. Coba kembali beberapa saat lagi atau hubungi kasir.</p>
        <a href="{{ url()->current() }}" class="btn-primary">Coba Lagi</a>
    </main>
</body>
</html>
