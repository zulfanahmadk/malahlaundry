@extends('layouts.app')
@section('figma_node', '127-2929')
@section('title', 'Sinkronisasi')
@section('page_title', 'Sinkronisasi')
@section('page_subtitle', 'Pantau laporan sinkronisasi terakhir dari perangkat Android')
@section('page_actions')<a class="btn btn-primary" href="{{ route('sync.index', request()->query()) }}">
    <x-figma-icon name="imgIconRefresh" />Periksa sekarang</a>@endsection
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Perangkat tercatat" :value="$stats['devices']" note="Pernah melapor pada cabang ini" icon="imgIconDevice" />
    <x-workspace-stat label="Sinkron terakhir" :value="\App\Support\Workspace::date($stats['last'])" note="WIB · sinkron tanpa sisa antrean" icon="imgIconRefresh1" tone="green" :date="true" />
    <x-workspace-stat label="Antrean terakhir" :value="$stats['pending']" note="Jumlah saat perangkat melapor" icon="imgIconRefresh2" tone="teal" />
    <x-workspace-stat label="Perlu perhatian" :value="$stats['attention']" note="Tertunda, gagal, atau belum melapor" icon="imgIconDevice1" tone="orange" />
</div>
<form class="toolbar" method="GET">
    <div class="search-field">
        <x-figma-icon name="imgIconSearch" />
        <input name="q" type="search" placeholder="Cari perangkat" aria-label="Cari perangkat" value="{{ request('q') }}">
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="status" aria-label="Status sinkronisasi">
                <option value="">Semua status</option>
                <option value="current" @selected(request('status') === 'current')>Tanpa antrean</option>
                <option value="attention" @selected(request('status') === 'attention')>Perlu perhatian</option>
            </select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <button class="btn btn-secondary">Terapkan</button>
    <a class="btn btn-small" href="{{ route('sync.index') }}">Reset</a>
    <span class="count">{{ $devices->count() }} perangkat</span>
</form>
<div class="card table-card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Perangkat</th>
                    <th>Cabang</th>
                    <th>Versi</th>
                    <th>Sinkron terakhir</th>
                    <th>Antrean</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>@forelse($devices as $device)<tr>
                    <td>{{ $device->name }}<small class="muted">{{ $device->user?->name }} · Laporan {{ \App\Support\Workspace::date($device->last_seen_at) }}</small>@if($device->last_error)<small class="muted">{{ $device->last_error }}</small>@endif</td>
                    <td>{{ request()->attributes->get('branch')->name }}</td>
                    <td>{{ $device->app_version }}</td>
                    <td class="table-nowrap">{{ \App\Support\Workspace::date($device->last_synced_at) }}</td>
                    <td>{{ $device->pending_count }} data @if($device->failed_count)<br>
                        <small>{{ $device->failed_count }} gagal</small>@endif</td>
                    <td>
                        <span class="badge {{ $device->attention || $device->pending_count ? 'badge-warning' : 'badge-success' }}">{{ $device->status_label }}</span>
                    </td>
                </tr>@empty<tr>
                    <td colspan="6" class="empty">Belum ada laporan perangkat. Sinkronkan aplikasi Android versi terbaru untuk menampilkan data di sini.</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
</div>
<div class="insights">
    <div class="insight">
        <h3>Data terakhir perangkat</h3>
        <p>Jumlah antrean merupakan laporan terakhir. Perangkat yang sedang offline dapat memiliki tambahan data lokal yang belum diketahui server.</p>
    </div>
    <div class="notice">
        <x-figma-icon name="imgIconInfo" />Periksa sekarang memuat ulang laporan server. Untuk mengirim antrean, kasir menekan Sinkronkan di Android atau mengaktifkan sinkronisasi otomatis data.</div>
</div>
@endsection
