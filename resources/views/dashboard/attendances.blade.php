@extends('layouts.app')

@section('title', 'Absensi Kasir')
@section('page_title', 'Monitoring Absensi Kasir (Selfie Check-In / Out)')

@section('content')
<style>
    .selfie-card-img {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid var(--border);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
</style>

<div class="card">
    <div class="section-header">
        <h2 class="section-title">Riwayat Absensi Shift Kasir</h2>
        <span style="font-size: 0.8rem; color: var(--text-muted);">Foto selfie terekam otomatis dari kamera depan Android POS</span>
    </div>

    <div class="table-responsive">
        <table style="min-width: 960px;">
            <thead>
                <tr>
                    <th>Kasir</th>
                    <th>Perangkat (Device ID)</th>
                    <th>Waktu Masuk</th>
                    <th>Foto Selfie Masuk</th>
                    <th>Waktu Keluar</th>
                    <th>Foto Selfie Keluar</th>
                    <th>Status Shift</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $att)
                    <tr>
                        <td><strong>{{ $att->user->name ?? 'Kasir' }}</strong></td>
                        <td><code>{{ $att->device_id ?? 'Android POS' }}</code></td>
                        <td>
                            <strong>{{ $att->check_in_time ? $att->check_in_time->translatedFormat('d M Y, H:i') . ' WIB' : '-' }}</strong>
                        </td>
                        <td>
                            @if($att->check_in_photo_path)
                                <a href="{{ Storage::disk('public')->url($att->check_in_photo_path) }}" target="_blank" title="Klik untuk perbesar">
                                    <img src="{{ Storage::disk('public')->url($att->check_in_photo_path) }}" class="selfie-card-img" alt="Selfie Masuk">
                                </a>
                            @else
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Tidak ada foto</span>
                            @endif
                        </td>
                        <td>
                            @if($att->check_out_time)
                                <strong>{{ $att->check_out_time->translatedFormat('d M Y, H:i') . ' WIB' }}</strong>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.85rem;">Belum Check-Out</span>
                            @endif
                        </td>
                        <td>
                            @if($att->check_out_photo_path)
                                <a href="{{ Storage::disk('public')->url($att->check_out_photo_path) }}" target="_blank" title="Klik untuk perbesar">
                                    <img src="{{ Storage::disk('public')->url($att->check_out_photo_path) }}" class="selfie-card-img" alt="Selfie Keluar">
                                </a>
                            @else
                                <span style="font-size: 0.75rem; color: var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td>
                            @if($att->check_out_time)
                                <span class="badge badge-success">Shift Selesai</span>
                            @else
                                <span class="badge badge-warning">Sedang Bertugas</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada log absensi selfie.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($attendances->hasPages())
        <div style="padding: 1rem;">
            {{ $attendances->links('components.pagination') }}
        </div>
    @endif
</div>
@endsection
