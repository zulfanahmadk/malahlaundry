@extends('layouts.app')
@section('figma_node', '127-651')
@section('title', 'Pelanggan')
@section('page_title', 'Daftar pelanggan')
@section('page_subtitle', 'Riwayat pelanggan pada cabang aktif')
@section('page_actions')<a class="btn btn-secondary" href="{{ route('customers.export', request()->query()) }}">
    <x-figma-icon name="imgIconDownload" />Ekspor data</a>@endsection
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Total pelanggan" :value="$stats['count']" note="Terdaftar pada cabang ini" icon="imgIconUsers" />
    <x-workspace-stat label="Pelanggan aktif" :value="$stats['active']" note="Pernah bertransaksi, belum diarsipkan" icon="imgIconUsers1" tone="green" />
    <x-workspace-stat label="Rata-rata transaksi" :value="\App\Support\Workspace::number($stats['visits'], 1).'×'" note="Per pelanggan" icon="imgIconUsers2" tone="teal" />
    <x-workspace-stat label="Nilai pelanggan" :value="\App\Support\Workspace::money($stats['spend'])" note="Rata-rata total belanja" icon="imgIconUsers3" tone="orange" />
</div>
<form class="toolbar" method="GET">
    <div class="search-field">
        <x-figma-icon name="imgIconSearch" />
        <input name="q" type="search" placeholder="Cari daftar pelanggan" aria-label="Cari nama atau WhatsApp" value="{{ request('q') }}">
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="segment" aria-label="Segmen pelanggan">
                <option value="">Semua pelanggan</option>
                <option value="active" @selected(request('segment') === 'active')>Aktif</option>
                <option value="new" @selected(request('segment') === 'new')>Belum bertransaksi</option>
                <option value="archived" @selected(request('segment') === 'archived')>Diarsipkan</option>
            </select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="sort" aria-label="Urutkan pelanggan">
                <option value="newest" @selected(request('sort', 'newest') === 'newest')>Urutkan: terbaru</option>
                <option value="spend" @selected(request('sort') === 'spend')>Belanja terbanyak</option>
                <option value="visits" @selected(request('sort') === 'visits')>Kunjungan terbanyak</option>
            </select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <button class="btn btn-secondary">Terapkan</button>
    <a class="btn btn-small" href="{{ route('customers.index') }}">Reset</a>
    <span class="count">{{ $customers->total() }} data</span>
</form>
<div class="card table-card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nama pelanggan</th>
                    <th>Nomor HP</th>
                    <th>Transaksi</th>
                    <th>Total belanja</th>
                    <th>Terakhir</th>
                    <th>Segmen</th>
                </tr>
            </thead>
            <tbody>@forelse($customers as $customer)<tr>
                    <td>
                        <a href="{{ route('customers.show', $customer->uuid) }}">{{ $customer->name }}</a>
                    </td>
                    <td>{{ $customer->phone }}</td>
                    <td>{{ $customer->transactions_count }}</td>
                    <td>{{ \App\Support\Workspace::money($customer->transactions_sum_total ?? 0) }}</td>
                    <td>{{ \App\Support\Workspace::date($customer->transactions_max_created_at) }}</td>
                    <td>
                        <span class="badge {{ !$customer->transactions_count ? 'badge-teal' : '' }}">{{ $customer->archived_at ? 'ARSIP' : ($customer->transactions_count ? 'AKTIF' : 'BARU') }}</span>
                    </td>
                </tr>@empty<tr>
                    <td colspan="6" class="empty">Belum ada pelanggan yang sesuai filter.</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
</div>{{ $customers->links('components.pagination') }}
<div class="insights">
    <div class="insight">
        <h3>Retensi pelanggan</h3>
        <p>{{ $stats['repeat'] }} pelanggan sudah bertransaksi lebih dari sekali.</p>
    </div>
    <div class="insight">
        <h3>Data pelanggan</h3>
        <p>Pencatatan dan perubahan pelanggan dilakukan dari aplikasi Android. Seluruh waktu ditampilkan dalam WIB.</p>
    </div>
</div>
@endsection
