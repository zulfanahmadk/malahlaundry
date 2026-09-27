<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Nota Digital - {{ $transaction->transaction_number }} - Malah Laundry</title>
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
            line-height: 1.5;
            padding: 1rem;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .receipt-container {
            width: 100%;
            max-width: 480px;
            background: var(--surface);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin: 1rem auto 2rem;
        }

        /* Header */
        .receipt-header {
            background-color: var(--primary);
            color: #FFFFFF;
            padding: 1.5rem;
            text-align: center;
        }

        .brand-name {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
        }

        .brand-sub {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .badge-paperless {
            display: inline-block;
            margin-top: 0.75rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* QR Code Section */
        .qr-section {
            background: #FFFFFF;
            padding: 1.5rem 1rem;
            text-align: center;
            border-bottom: 1px dashed var(--border);
        }

        .qr-box {
            display: inline-block;
            background: #FFFFFF;
            padding: 0.75rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .qr-box img {
            display: block;
            width: 160px;
            height: 160px;
        }

        .qr-caption {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-main);
            margin-top: 0.75rem;
        }

        .qr-subcaption {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.15rem;
        }

        /* Transaction Meta */
        .receipt-body {
            padding: 1.25rem;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.65rem;
            font-size: 0.85rem;
            gap: 1rem;
        }

        .meta-label {
            color: var(--text-muted);
        }

        .meta-val {
            font-weight: 600;
            color: var(--text-main);
            text-align: right;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .trx-number {
            font-family: monospace;
            background: var(--bg);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        /* Status Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .badge-success {
            background-color: var(--success-light);
            color: var(--success);
        }

        .badge-warning {
            background-color: var(--warning-light);
            color: #854D0E;
        }

        .badge-danger {
            background-color: var(--danger-light);
            color: var(--danger);
        }

        .badge-primary {
            background-color: var(--primary-light);
            color: var(--primary-dark);
        }

        /* Progress Stepper */
        .stepper-section {
            margin: 1.25rem 0;
            padding: 1rem;
            background-color: var(--bg);
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .stepper-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
        }

        .status-message {
            margin-top: 1rem;
            font-size: 0.8rem;
            line-height: 1.6;
            color: var(--text-main);
        }

        .stepper {
            display: flex;
            justify-content: space-between;
            position: relative;
        }

        .stepper::before {
            content: '';
            position: absolute;
            top: 14px;
            left: 16px;
            right: 16px;
            height: 2px;
            background-color: #CBD5E1;
            z-index: 1;
        }

        .step-item {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }

        .step-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: #FFFFFF;
            border: 2px solid #CBD5E1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            color: #94A3B8;
            margin-bottom: 0.35rem;
        }

        .step-item.active .step-circle {
            border-color: var(--primary);
            background-color: var(--primary);
            color: #FFFFFF;
        }

        .step-item.completed .step-circle {
            border-color: var(--success);
            background-color: var(--success);
            color: #FFFFFF;
        }

        .step-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: #64748B;
        }

        .step-item.active .step-label {
            color: var(--primary);
            font-weight: 700;
        }

        .step-item.completed .step-label {
            color: var(--success);
        }

        /* Items Table */
        .items-section {
            margin-top: 1.25rem;
            border-top: 1px solid var(--border);
            padding-top: 1.25rem;
        }

        .section-heading {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.75rem;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0.5rem 0;
            border-bottom: 1px solid #F1F5F9;
            font-size: 0.85rem;
        }

        .item-info {
            flex: 1;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .item-name {
            font-weight: 600;
            color: var(--text-main);
        }

        .item-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.1rem;
        }

        .item-total {
            font-weight: 600;
            color: var(--text-main);
            text-align: right;
            margin-left: 0.5rem;
        }

        /* Summary Box */
        .summary-box {
            margin-top: 1.25rem;
            background: var(--bg);
            border-radius: 12px;
            padding: 1rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            margin-bottom: 0.4rem;
        }

        .summary-row.total {
            border-top: 1px solid var(--border);
            padding-top: 0.5rem;
            margin-top: 0.5rem;
            margin-bottom: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        /* Footer */
        .receipt-footer {
            padding: 1.25rem;
            text-align: center;
            border-top: 1px dashed var(--border);
            background-color: #FAFAFA;
        }

        .footer-note {
            font-size: 0.75rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .print-btn-wrap {
            margin-top: 1rem;
        }

        .btn-print {
            background-color: #FFFFFF;
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-print:hover {
            background-color: var(--bg);
            border-color: #CBD5E1;
        }

        .btn-print:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 3px;
        }

        @media print {
            body {
                background: #FFFFFF;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                border: none;
                max-width: 100%;
            }
            .print-btn-wrap {
                display: none;
            }
        }
    </style>
</head>
<body>
    <main class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <h1 class="brand-name">Malah Laundry</h1>
            <p class="brand-sub">Cucian terawat, hari lebih ringan.</p>
            <div class="badge-paperless">Nota Digital</div>
        </div>

        <!-- QR Code Section -->
        <div class="qr-section">
            <div class="qr-box">
                <img src="{{ $qrCode }}" alt="QR Code Nota Transaksi" width="160" height="160">
            </div>
            <div class="qr-caption">{{ $transaction->laundry_status === 'SELESAI' ? 'Bukti Pengambilan Laundry' : 'QR Code Pengambilan Laundry' }}</div>
            <div class="qr-subcaption">{{ $transaction->laundry_status === 'SELESAI' ? 'Cucian pada transaksi ini sudah diambil.' : 'Tunjukkan layar ponsel ini kepada kasir saat mengambil cucian.' }}</div>
        </div>

        <div class="receipt-body">
            <!-- Metadata Transaksi -->
            <div class="meta-row">
                <span class="meta-label">Nomor Transaksi</span>
                <span class="meta-val trx-number">{{ $transaction->transaction_number }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Tanggal Diterima</span>
                <span class="meta-val">{{ $transaction->created_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i') }} WIB</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Nama Pelanggan</span>
                <span class="meta-val">{{ $customer->name ?? 'Pelanggan Umum' }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Nomor WhatsApp</span>
                <span class="meta-val">{{ $maskedPhone }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Status Pembayaran</span>
                <span class="meta-val">
                    @if($transaction->payment_status === 'LUNAS')
                        <span class="badge badge-success">LUNAS</span>
                    @else
                        <span class="badge badge-danger">BELUM LUNAS</span>
                    @endif
                </span>
            </div>

            <!-- Stepper Progress Pengerjaan -->
            @php
                $statusList = ['DITERIMA' => 'Diterima', 'DIPROSES' => 'Diproses', 'SIAP_DIAMBIL' => 'Siap Ambil', 'SELESAI' => 'Selesai'];
                $currentIndex = array_search($transaction->laundry_status, array_keys($statusList), true);
                $statusMessage = match ($transaction->laundry_status) {
                    'DITERIMA' => 'Cucian Anda sudah diterima dan menunggu proses pencucian.',
                    'DIPROSES' => 'Cucian Anda sedang kami proses. Kami akan mengabari saat siap diambil.',
                    'SIAP_DIAMBIL' => 'Cucian Anda siap diambil. Tunjukkan QR code ini kepada kasir.',
                    'SELESAI' => 'Cucian Anda sudah diambil. Terima kasih telah menggunakan layanan kami.',
                    default => 'Hubungi kasir untuk mengetahui status cucian Anda.',
                };
            @endphp
            <div class="stepper-section">
                <div class="stepper-title">Status Pengerjaan Cucian</div>
                <div class="stepper" role="list" aria-label="Progres cucian">
                    @foreach($statusList as $status => $label)
                        @php
                            $isCurrent = $transaction->laundry_status === $status;
                            $isCompleted = $currentIndex !== false && ($currentIndex > $loop->index || $transaction->laundry_status === 'SELESAI');
                        @endphp
                        <div class="step-item {{ $isCompleted ? 'completed' : ($isCurrent ? 'active' : '') }}" role="listitem" @if($isCurrent) aria-current="step" @endif>
                            <div class="step-circle" aria-hidden="true">{{ $isCompleted ? '✓' : $loop->iteration }}</div>
                            <div class="step-label">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
                <p class="status-message">{{ $statusMessage }}</p>
            </div>

            <!-- Daftar Layanan -->
            <div class="items-section">
                <div class="section-heading">Rincian Layanan</div>
                @foreach($items as $item)
                    <div class="item-row">
                        <div class="item-info">
                            <div class="item-name">{{ $item->service->name ?? 'Layanan Laundry' }}</div>
                            <div class="item-sub">
                                {{ rtrim(rtrim(number_format($item->qty, 2, ',', '.'), '0'), ',') }} {{ $item->service->unit ?? 'satuan' }} × Rp{{ number_format($item->price, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="item-total">
                            Rp{{ number_format($item->qty * $item->price, 0, ',', '.') }}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Summary Total -->
            <div class="summary-box">
                <div class="summary-row">
                    <span class="meta-label">Subtotal</span>
                    <span>Rp{{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row total">
                    <span>Total Tagihan</span>
                    <span>Rp{{ number_format($transaction->total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <p class="footer-note">
                Terima kasih atas kepercayaan Anda menggunakan <strong>Malah Laundry</strong>.<br>
                Simpan link atau tangkapan layar halaman ini untuk bukti pengambilan.
                Jaga kerahasiaan tautan nota Anda.
            </p>
            <div class="print-btn-wrap">
                <button type="button" class="btn-print" onclick="window.print()">Simpan sebagai PDF</button>
            </div>
        </div>
    </main>
</body>
</html>
