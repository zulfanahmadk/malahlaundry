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
    <form action="{{ route('attendances.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: 1.5rem; align-items: end;">
        <div class="form-group" style="flex: 2 1 180px; margin: 0;"><label for="q">Cari nama / username</label><input id="q" type="search" name="q" value="{{ request('q') }}" class="form-control"></div>
        <div class="form-group" style="flex: 1 1 150px; margin: 0;"><label for="user_id">Pengguna</label><select id="user_id" name="user_id" class="form-control"><option value="">Semua pengguna</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>@endforeach</select></div>
        <div class="form-group" style="flex: 1 1 150px; margin: 0;"><label for="status">Status shift</label><select id="status" name="status" class="form-control"><option value="">Semua shift</option><option value="active" @selected(request('status') === 'active')>Sedang bertugas</option><option value="finished" @selected(request('status') === 'finished')>Sudah pulang</option></select></div>
        <div class="form-group" style="flex: 1 1 145px; margin: 0;"><label for="from">Masuk dari tanggal</label><input id="from" type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
        <div class="form-group" style="flex: 1 1 145px; margin: 0;"><label for="to">Sampai tanggal</label><input id="to" type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
        <button class="btn btn-primary" type="submit">Filter absensi</button><a class="btn btn-secondary" href="{{ route('attendances.index') }}">Reset</a>
    </form>
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
                            <strong>{{ $att->check_in_time ? $att->check_in_time->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H.i') . ' WIB' : '-' }}</strong>
                        </td>
                        <td>
                            @if($att->check_in_photo_path)
                                <a href="{{ route('attendances.photo', [$att->uuid, 'check_in']) }}" target="_blank" rel="noopener" title="Klik untuk perbesar">
                                    <img src="{{ route('attendances.photo', [$att->uuid, 'check_in']) }}" class="selfie-card-img" alt="Selfie Masuk" loading="lazy">
                                </a>
                            @else
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Foto belum diunggah dari Android</span>
                            @endif
                        </td>
                        <td>
                            @if($att->check_out_time)
                                <strong>{{ $att->check_out_time->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H.i') . ' WIB' }}</strong>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.85rem;">Belum Check-Out</span>
                            @endif
                        </td>
                        <td>
                            @if($att->check_out_photo_path)
                                <a href="{{ route('attendances.photo', [$att->uuid, 'check_out']) }}" target="_blank" rel="noopener" title="Klik untuk perbesar">
                                    <img src="{{ route('attendances.photo', [$att->uuid, 'check_out']) }}" class="selfie-card-img" alt="Selfie Keluar" loading="lazy">
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
