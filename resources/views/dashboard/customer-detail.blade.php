@extends('layouts.app')
@section('figma_node', '127-880')
@section('title', 'Detail pelanggan')
@section('page_title', 'Detail pelanggan')
@section('page_subtitle', 'Riwayat dan perilaku pelanggan · hanya-baca')
@section('hide_branch', 'true')
@section('page_actions')<a class="btn btn-secondary" href="{{ route('customers.index') }}">
    <x-figma-icon name="imgIconArrowLeft" />Kembali ke daftar</a>@endsection
@section('content')
<div class="detail-stats five">
    <div class="customer-summary">
        <span class="avatar">{{ \App\Support\Workspace::initials($customer->name) }}</span>
        <div>
            <h2>{{ $customer->name }}</h2>
            <small>{{ $customer->phone }}</small>
            <small>Pelanggan sejak {{ \App\Support\Workspace::date($customer->created_at) }} WIB</small>
        </div>
        <span class="badge badge-success">{{ $customer->archived_at ? 'DIARSIPKAN' : 'PELANGGAN AKTIF' }}</span>
    </div>
    <x-workspace-stat label="Total transaksi" :value="$stats['count']" note="Seluruh riwayat" icon="imgIconWallet" />
    <x-workspace-stat label="Total belanja" :value="\App\Support\Workspace::money($stats['total'])" note="Nilai pelanggan" icon="imgIconUsers" tone="green" />
    <x-workspace-stat label="Rata-rata cucian" :value="\App\Support\Workspace::number($stats['kg'], 1).' kg'" note="Per kunjungan, layanan kg" icon="imgIconWash" tone="teal" />
    <x-workspace-stat label="Jarak kunjungan" :value="$stats['gap'] !== null ? \App\Support\Workspace::number($stats['gap'], 1).' hari' : '—'" note="Rata-rata" icon="imgIconClock" tone="orange" />
</div>
<div class="two-column">
    <section class="stack">
        <div>
            <div class="section-head">
                <h2>Riwayat transaksi</h2>
                <small>{{ $stats['count'] }} transaksi</small>
            </div>
            <div class="card table-card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Tanggal</th>
                                <th>Layanan</th>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>@forelse($transactions as $trx)<tr>
                                <td class="table-code">
                                    <a href="{{ route('transactions.show', $trx->uuid) }}">{{ $trx->transaction_number }}</a>
                                </td>
                                <td class="table-nowrap">{{ \App\Support\Workspace::date($trx->created_at) }}</td>
                                <td>{{ $trx->items->pluck('service_name')->filter()->join(', ') ?: 'Layanan' }}</td>
                                <td>
                                    <span class="badge {{ $trx->laundry_status === 'DITERIMA' ? 'badge-teal' : ($trx->laundry_status === 'SIAP_DIAMBIL' ? 'badge-success' : '') }}">{{ str_replace('_', ' ', $trx->laundry_status) }}</span>
                                </td>
                                <td class="table-nowrap">{{ \App\Support\Workspace::money($trx->total) }}</td>
                            </tr>@empty<tr>
                                <td colspan="5" class="empty">Pelanggan ini belum memiliki transaksi.</td>
                            </tr>@endforelse</tbody>
                    </table>
                </div>
            </div>
        </div>{{ $transactions->links('components.pagination') }}<div class="notice">
            <x-figma-icon name="imgIconInfo" />Riwayat ini dapat dilihat di web; operasi transaksi tersedia di aplikasi Android.</div>
    </section>
    <aside class="stack">
        <div class="insight">
            <h3>Preferensi</h3>
            <p>Layanan yang paling sering digunakan: {{ $favorite?->service_name ?? 'Belum ada riwayat.' }}</p>
        </div>
        <div class="insight">
            <h3>Catatan pelanggan</h3>
            <p>{{ $customer->notes ?: 'Belum ada catatan pelanggan.' }}</p>
        </div>
        <div class="insight">
            <h3>Alamat pelanggan</h3>
            <p>{{ $customer->address ?: 'Belum tercatat.' }}</p>
        </div>
    </aside>
</div>
@endsection
