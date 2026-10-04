@extends('layouts.app')
@section('figma_node', '127-1087')
@section('title', 'Laporan toko')
@section('page_title', 'Laporan toko')
@section('page_subtitle', 'Rekap transaksi cabang aktif berdasarkan tanggal masuk')
@section('page_actions')<a class="btn btn-secondary" href="{{ route('reports.export', $filters) }}">
    <x-figma-icon name="imgIconDownload" />Unduh laporan</a>@endsection
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Omzet periode ini" :value="\App\Support\Workspace::money($stats['income'])" note="Total transaksi lunas" icon="imgIconWallet" />
    <x-workspace-stat label="Transaksi" :value="$stats['count']" note="Dalam periode dipilih" icon="imgIconReceipt" tone="teal" />
    <x-workspace-stat label="Rata-rata nota" :value="\App\Support\Workspace::money($stats['average'])" note="Seluruh transaksi dalam periode" icon="imgIconReceipt1" />
    <x-workspace-stat label="Pembayaran lunas" :value="\App\Support\Workspace::number($stats['paid'], 1).'%'" note="Dari transaksi periode ini" icon="imgIconCheck" tone="green" />
</div>
<form class="toolbar" method="GET">
    <div class="search-field">
        <x-figma-icon name="imgIconSearch" />
        <input type="search" name="q" placeholder="Cari laporan toko" aria-label="Cari kode atau pelanggan" value="{{ request('q') }}">
    </div>
    <div class="form-group">
        <label for="report-from">Dari tanggal</label>
        <input id="report-from" type="date" name="from" value="{{ $filters['from'] }}" required>
    </div>
    <div class="form-group">
        <label for="report-to">Sampai tanggal</label>
        <input id="report-to" type="date" name="to" value="{{ $filters['to'] }}" required>
    </div>
    <button class="btn btn-secondary">Terapkan</button>
    <a class="btn btn-small" href="{{ route('reports.index') }}">Bulan ini</a>
    <span class="count">{{ $services->count() }} layanan ditampilkan</span>
</form>
<div class="two-column">
    <div class="card">
        <div class="section-head">
            <h2>Tren 6 bulan</h2>
            <small>Transaksi lunas · 6 bulan hingga bulan ini</small>
        </div>
        <x-workspace-chart :points="$months" />
        <div class="card table-card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Layanan</th>
                            <th>Order</th>
                            <th>Pendapatan</th>
                            <th>Kontribusi</th>
                        </tr>
                    </thead>
                    <tbody>@forelse($services as $service)<tr>
                            <td>{{ $service->service_name ?: 'Layanan' }}</td>
                            <td>{{ $service->orders }}</td>
                            <td>{{ \App\Support\Workspace::money($service->revenue) }}</td>
                            <td>{{ \App\Support\Workspace::number($total ? $service->revenue / $total * 100 : 0, 1) }}%</td>
                        </tr>@empty<tr>
                            <td colspan="4" class="empty">Belum ada transaksi pada periode ini.</td>
                        </tr>@endforelse</tbody>
                </table>
            </div>
        </div>
        <p class="form-help">Kontribusi layanan berdasarkan nilai seluruh order dalam periode, termasuk yang belum lunas.</p>
    </div>
    <aside class="stack">
        <div class="insight">
            <h3>Metode pembayaran</h3>
            <p>@forelse($methods as $method){{ $method->payment_method ?? 'Belum tercatat' }}: {{ $method->count }} transaksi{{ !$loop->last ? ' · ' : '' }}@empty Belum ada pembayaran lunas. @endforelse</p>
        </div>
        <div class="insight">
            <h3>Status cucian</h3>
            <p>@forelse($statuses as $status){{ str_replace('_', ' ', $status->laundry_status) }}: {{ $status->count }}{{ !$loop->last ? ' · ' : '' }}@empty Belum ada transaksi. @endforelse</p>
        </div>
        <div class="insight">
            <h3>Performa cabang</h3>
            <p>Data mengikuti cabang aktif. Pilih cabang di bagian atas untuk melihat laporan cabang lain.</p>
        </div>
    </aside>
</div>
@endsection
