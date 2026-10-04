@extends('layouts.app')
@section('figma_node', '127-1932')
@section('title', 'Presensi')
@section('page_title', 'Presensi')
@section('page_subtitle', 'Pantau kehadiran staf dari presensi yang tersinkron')
@section('page_actions')<a class="btn btn-secondary" href="{{ route('attendances.export', request()->query()) }}">
    <x-figma-icon name="imgIconDownload" />Ekspor presensi</a>@endsection
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Hadir hari ini" :value="$stats['present']" :note="'Dari '.$stats['staff'].' kasir aktif'" icon="imgIconUsers" tone="green" />
    <x-workspace-stat label="Tepat waktu" :value="$stats['on_time']" note="Mengikuti jam buka cabang" icon="imgIconClock" tone="teal" />
    <x-workspace-stat label="Terlambat" :value="$stats['late']" note="Masuk setelah jam buka" icon="imgIconAlert" tone="orange" />
    <x-workspace-stat label="Belum tercatat" :value="$stats['missing']" note="Presensi mungkin belum tersinkron" icon="imgIconAlert1" tone="red" />
</div>
<form class="toolbar" method="GET">
    <div class="search-field">
        <x-figma-icon name="imgIconSearch" />
        <input type="search" name="q" placeholder="Cari presensi" aria-label="Cari nama staf" value="{{ request('q') }}">
    </div>
    <div class="form-group">
        <label for="attendance-from">Dari tanggal</label>
        <input id="attendance-from" type="date" name="from" value="{{ request('from') }}">
    </div>
    <div class="form-group">
        <label for="attendance-to">Sampai tanggal</label>
        <input id="attendance-to" type="date" name="to" value="{{ request('to') }}">
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="user_id" aria-label="Pilih staf">
                <option value="">Semua staf</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>@endforeach</select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="status" aria-label="Status shift">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Berjalan</option>
                <option value="finished" @selected(request('status') === 'finished')>Selesai</option>
            </select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <button class="btn btn-secondary">Terapkan</button>
    <a class="btn btn-small" href="{{ route('attendances.index') }}">Reset</a>
    <span class="count">{{ $attendances->total() }} data</span>
</form>
<div class="card table-card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nama staf</th>
                    <th>Cabang</th>
                    <th>Masuk</th>
                    <th>Pulang</th>
                    <th>Durasi</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>@forelse($attendances as $attendance)
                @php($label = $hours->attendance($branch, $attendance->check_in_time))
                <tr>
                    <td>{{ $attendance->user?->name ?? 'Pengguna' }}</td>
                    <td>{{ $branch->name }}</td>
                    <td>{{ \App\Support\Workspace::date($attendance->check_in_time) }}@if($attendance->check_in_photo_path)<br>
                        <a class="photo-link" data-photo href="{{ route('attendances.photo', [$attendance->uuid, 'check_in']) }}">Lihat selfie masuk</a>@endif</td>
                    <td>{{ \App\Support\Workspace::date($attendance->check_out_time) }}@if($attendance->check_out_photo_path)<br>
                        <a class="photo-link" data-photo href="{{ route('attendances.photo', [$attendance->uuid, 'check_out']) }}">Lihat selfie pulang</a>@endif</td>
                    <td>{{ $attendance->check_out_time ? \App\Support\Workspace::number($attendance->check_in_time->diffInMinutes($attendance->check_out_time) / 60, 1).' jam' : 'Berjalan' }}</td>
                    <td>
                        <span class="badge {{ $label === 'TEPAT WAKTU' ? 'badge-success' : ($label === 'TERLAMBAT' ? 'badge-warning' : '') }}">{{ $label }}</span>
                    </td>
                </tr>@empty<tr>
                    <td colspan="6" class="empty">Belum ada presensi yang sesuai filter.</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
</div>{{ $attendances->links('components.pagination') }}
<div class="insights">
    <div class="insight">
        <h3>Aturan presensi</h3>
        <p>Kasir wajib selfie dari aplikasi Android. Owner tidak memerlukan presensi pribadi. Penilaian waktu menggunakan jam buka cabang dan pengecualian tanggal.</p>
    </div>
    <div class="notice orange">
        <x-figma-icon name="imgIconAlert" />Data presensi offline akan muncul setelah perangkat Android tersinkron. Belum tercatat tidak selalu berarti tidak hadir.</div>
</div>
<dialog class="dialog" id="photo-dialog">
    <div class="section-head">
        <h2>Selfie presensi</h2>
        <button class="btn btn-small btn-secondary" data-close-dialog type="button">Tutup</button>
    </div>
    <img class="photo-preview" alt="Selfie presensi staf">
</dialog>
@endsection
