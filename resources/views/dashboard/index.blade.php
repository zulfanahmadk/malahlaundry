@extends('layouts.app')

@section('title', 'Dashboard Ringkasan')
@section('page_title', 'Ringkasan & Analitik Bisnis')

@section('content')
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
        gap: 1.25rem;
        margin-bottom: 1.75rem;
    }
    .stat-card {
        min-width: 0;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.25rem;
    }
    .stat-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }
    .stat-val {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-main);
        overflow-wrap: anywhere;
    }
    .stat-sub {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.25rem;
    }

    .selfie-thumb {
        width: 36px;
        height: 36px;
        border-radius: 6px;
        object-fit: cover;
        border: 1px solid var(--border);
    }
</style>

<!-- Statistik Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Omzet Hari Ini</div>
        <div class="stat-val" style="color: var(--primary);">Rp{{ number_format($stats['today_omzet'], 0, ',', '.') }}</div>
        <div class="stat-sub">{{ $stats['today_transactions_count'] }} transaksi hari ini</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Omzet Bulan Ini</div>
        <div class="stat-val">Rp{{ number_format($stats['month_omzet'], 0, ',', '.') }}</div>
        <div class="stat-sub">Bulan {{ now()->translatedFormat('F Y') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Cucian Aktif</div>
        <div class="stat-val" style="color: var(--warning);">{{ $stats['active_laundry_count'] }}</div>
        <div class="stat-sub">Diterima, diproses, dan siap diambil</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Siap Diambil</div>
        <div class="stat-val" style="color: var(--success);">{{ $stats['ready_pickup_count'] }}</div>
        <div class="stat-sub">Menunggu pelanggan datang</div>
    </div>
</div>

<!-- Transaksi Terbaru -->
<div class="card">
    <div class="section-header">
        <h2 class="section-title">Transaksi Terkini (Paperless POS)</h2>
        <div class="section-actions">
            <a href="{{ route('reports.export') }}" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Ekspor Laporan (Excel)
            </a>
            <a href="{{ route('transactions.index') }}" class="btn btn-primary">Lihat Semua</a>
        </div>
    </div>

    <div class="table-responsive">
        <table style="min-width: 800px;">
            <thead>
                <tr>
                    <th>No Transaksi</th>
                    <th>Waktu (WIB)</th>
                    <th>Pelanggan</th>
                    <th>Total</th>
                    <th>Status Cucian</th>
                    <th>Pembayaran</th>
                    <th>Nota Digital</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTransactions as $trx)
                    <tr>
                        <td><strong>{{ $trx->transaction_number }}</strong></td>
                        <td>{{ $trx->created_at->translatedFormat('d M Y, H:i') }}</td>
                        <td>
                            <div>{{ $trx->customer->name ?? 'Pelanggan Umum' }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $trx->customer->phone ?? '-' }}</div>
                        </td>
                        <td><strong>Rp{{ number_format($trx->total, 0, ',', '.') }}</strong></td>
                        <td>
                            @if($trx->laundry_status === 'SELESAI')
                                <span class="badge badge-success">Selesai</span>
                            @elseif($trx->laundry_status === 'SIAP_DIAMBIL')
                                <span class="badge badge-success">Siap Diambil</span>
                            @else
                                <span class="badge badge-primary">Diterima</span>
                            @endif
                        </td>
                        <td>
                            @if($trx->payment_status === 'LUNAS')
                                <span class="badge badge-success">Lunas</span>
                            @else
                                <span class="badge badge-danger">Belum Lunas</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('nota.public', $trx->uuid) }}" target="_blank" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                Buka Nota &amp; QR
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada transaksi tercatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Absensi Kasir Hari Ini -->
<div class="card">
    <div class="section-header">
        <h2 class="section-title">Absensi Kasir Hari Ini (Selfie Check-In)</h2>
        <a href="{{ route('attendances.index') }}" class="btn btn-secondary">Riwayat Lengkap</a>
    </div>

    <div class="table-responsive">
        <table style="min-width: 560px;">
            <thead>
                <tr>
                    <th>Kasir</th>
                    <th>Waktu Masuk</th>
                    <th>Foto Masuk</th>
                    <th>Waktu Keluar</th>
                    <th>Foto Keluar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($todayAttendances as $att)
                    <tr>
                        <td><strong>{{ $att->user->name ?? 'Kasir' }}</strong></td>
                        <td>{{ $att->check_in_time ? $att->check_in_time->format('H:i:s \W\I\B') : '-' }}</td>
                        <td>
                            @if($att->check_in_photo_path)
                                <a href="{{ Storage::disk('public')->url($att->check_in_photo_path) }}" target="_blank">
                                    <img src="{{ Storage::disk('public')->url($att->check_in_photo_path) }}" class="selfie-thumb" alt="Selfie Masuk">
                                </a>
                            @else
                                <span style="font-size: 0.75rem; color: var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td>{{ $att->check_out_time ? $att->check_out_time->format('H:i:s \W\I\B') : 'Belum Keluar' }}</td>
                        <td>
                            @if($att->check_out_photo_path)
                                <a href="{{ Storage::disk('public')->url($att->check_out_photo_path) }}" target="_blank">
                                    <img src="{{ Storage::disk('public')->url($att->check_out_photo_path) }}" class="selfie-thumb" alt="Selfie Keluar">
                                </a>
                            @else
                                <span style="font-size: 0.75rem; color: var(--text-muted);">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Belum ada data absensi kasir hari ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
