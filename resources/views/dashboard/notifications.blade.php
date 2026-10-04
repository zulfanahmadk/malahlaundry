@extends('layouts.app')
@section('figma_node', '127-3130')
@section('title', 'Notifikasi')
@section('page_title', 'Notifikasi')
@section('page_subtitle', 'Pusat pemberitahuan operasional cabang aktif')
@section('page_actions')<form action="{{ route('notifications.read') }}" method="POST">@csrf<button class="btn btn-primary">
        <x-figma-icon name="imgIconCheck" />Tandai semua dibaca</button>
</form>@endsection
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Belum dibaca" :value="$all->where('read', false)->count()" note="Perlu perhatian" icon="imgIconBell" />
    <x-workspace-stat label="Operasional" :value="$all->whereIn('category', ['CUCIAN','PRESENSI'])->count()" note="Cucian dan presensi perlu perhatian" icon="imgIconActivity" tone="teal" />
    <x-workspace-stat label="Sinkronisasi" :value="$all->where('category', 'SINKRON')->count()" note="Laporan perangkat" icon="imgIconRefresh" tone="orange" />
    <x-workspace-stat label="Keamanan" :value="$all->where('category', 'AKUN')->count()" note="Login terakhir akun ini" icon="imgIconShield" tone="green" />
</div>
<form class="toolbar" method="GET">
    <div class="search-field">
        <x-figma-icon name="imgIconSearch" />
        <input name="q" type="search" placeholder="Cari notifikasi" aria-label="Cari notifikasi" value="{{ request('q') }}">
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="category" aria-label="Kategori notifikasi">
                <option value="">Semua kategori</option>@foreach(['CUCIAN'=>'Cucian','PRESENSI'=>'Presensi','SINKRON'=>'Sinkronisasi','AKUN'=>'Akun'] as $key=>$label)<option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>@endforeach</select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="read" aria-label="Status dibaca">
                <option value="">Semua status</option>
                <option value="0" @selected(request('read') === '0')>Belum dibaca</option>
                <option value="1" @selected(request('read') === '1')>Sudah dibaca</option>
            </select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <button class="btn btn-secondary">Terapkan</button>
    <a class="btn btn-small" href="{{ route('notifications.index') }}">Reset</a>
    <span class="count">{{ $notifications->count() }} data</span>
</form>
<div class="card table-card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Kategori</th>
                    <th>Pemberitahuan</th>
                    <th>Cabang</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>@forelse($notifications as $notification)<tr>
                    <td>{{ \App\Support\Workspace::date($notification['time']) }}</td>
                    <td>
                        <span class="badge {{ $notification['category'] === 'CUCIAN' ? 'badge-teal' : ($notification['category'] === 'SINKRON' ? 'badge-success' : ($notification['category'] === 'PRESENSI' ? 'badge-warning' : 'badge-primary')) }}">{{ $notification['category'] }}</span>
                    </td>
                    <td>
                        <a href="{{ $notification['url'] }}">{{ $notification['title'] }}</a>
                    </td>
                    <td>{{ request()->attributes->get('branch')->name }}</td>
                    <td>
                        <span class="badge {{ $notification['read'] ? '' : 'badge-primary' }}">{{ $notification['read'] ? 'DIBACA' : 'BARU' }}</span>
                    </td>
                </tr>@empty<tr>
                    <td colspan="5" class="empty">Belum ada notifikasi yang sesuai filter.</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
</div>
<div class="insights">
    <div class="insight">
        <h3>Pemberitahuan operasional</h3>
        <p>Cucian yang menunggu pengambilan dan perangkat yang perlu perhatian dihitung dari data yang telah diterima server.</p>
    </div>
    <div class="insight">
        <h3>Saluran owner</h3>
        <p>Pemberitahuan ditampilkan di web. Pesan WhatsApp pelanggan dan pengingat tetap dikirim manual oleh kasir / user.</p>
    </div>
</div>
@endsection
