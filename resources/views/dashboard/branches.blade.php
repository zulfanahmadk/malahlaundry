@extends('layouts.app')
@section('figma_node', '127-1524')
@section('title', 'Cabang')
@section('page_title', 'Cabang')
@section('page_subtitle', 'Kelola seluruh cabang Malah Laundry')
@section('page_actions')<a class="btn btn-primary" href="{{ route('branches.create') }}">
    <x-figma-icon name="imgIconPlus" />Tambah cabang</a>@endsection
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Total cabang" :value="$stats['total']" :note="$stats['active'].' aktif'" icon="imgIconBuilding" />
    <x-workspace-stat label="Transaksi aktif" :value="$rows->sum('active_orders')" note="Pada cabang dalam daftar" icon="imgIconBuilding1" tone="teal" />
    <x-workspace-stat label="Total staf" :value="$stats['staff']" note="Seluruh cabang" icon="imgIconUsers" />
    <x-workspace-stat label="Perlu ditinjau" :value="$stats['inactive']" note="Cabang nonaktif" icon="imgIconBuilding2" tone="orange" />
</div>
<form class="toolbar" method="GET">
    <div class="search-field">
        <x-figma-icon name="imgIconSearch" />
        <input type="search" name="q" placeholder="Cari cabang" aria-label="Cari cabang atau alamat" value="{{ request('q') }}">
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="active" aria-label="Status cabang">
                <option value="">Semua status</option>
                <option value="1" @selected(request('active') === '1')>Aktif</option>
                <option value="0" @selected(request('active') === '0')>Nonaktif</option>
            </select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="sort" aria-label="Urutkan cabang">
                <option value="name" @selected(request('sort') !== 'activity')>Urutkan: nama</option>
                <option value="activity" @selected(request('sort') === 'activity')>Urutkan: aktivitas</option>
            </select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <button class="btn btn-secondary">Terapkan</button>
    <a class="btn btn-small" href="{{ route('branches.index') }}">Reset</a>
</form>
<div class="card table-card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nama cabang</th>
                    <th>Alamat</th>
                    <th>Transaksi aktif</th>
                    <th>Staf</th>
                    <th>Sinkron terakhir</th>
                    <th>Status / aksi</th>
                </tr>
            </thead>
            <tbody>@forelse($rows as $branch)<tr>
                    <td>
                        <a href="{{ route('branches.edit', $branch) }}">{{ $branch->store_name }} — {{ $branch->name }}</a>
                        <small class="muted">{{ $branch->code }}</small>
                    </td>
                    <td>{{ $branch->address ?: 'Belum tercatat' }}</td>
                    <td>{{ $branch->active_orders }}</td>
                    <td>{{ $branch->staff_count }}</td>
                    <td>{{ \App\Support\Workspace::date($branch->last_sync) }}</td>
                    <td>
                        <span class="badge {{ $branch->active ? 'badge-success' : '' }}">{{ $branch->active ? 'AKTIF' : 'NONAKTIF' }}</span>
                        <a class="btn btn-small btn-secondary" href="{{ route('branches.edit', $branch) }}">Edit</a>
                    </td>
                </tr>@empty<tr>
                    <td colspan="6" class="empty">Tidak ada cabang yang sesuai filter.</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
</div>
<div class="insights">
    <div class="insight">
        <h3>Cabang dipilih</h3>
        <p>{{ request()->attributes->get('branch')->name }} · {{ request()->attributes->get('branch')->code }} · {{ request()->attributes->get('branch')->phone }}</p>
        <p>{{ app(\App\Services\BranchHours::class)->status(request()->attributes->get('branch')) }} · WIB</p>
    </div>
    <div class="notice">
        <x-figma-icon name="imgIconInfo" />Data laporan, layanan, pengguna, nota, dan operasional mengikuti cabang yang dipilih.</div>
</div>
@endsection
