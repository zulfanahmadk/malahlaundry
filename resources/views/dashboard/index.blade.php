@extends('layouts.app')
@section('figma_node', '127-45')
@section('title', 'Beranda')
@section('page_title', 'Ringkasan toko')
@section('page_subtitle', \App\Support\Workspace::date(now()).' WIB · Data yang diterima server')
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Omzet hari ini" :value="\App\Support\Workspace::money($stats['today_omzet'])" note="Transaksi lunas yang masuk hari ini" icon="imgIconWallet" tone="green" />
    <x-workspace-stat label="Transaksi aktif" :value="$stats['active_laundry_count']" :note="$stats['ready_pickup_count'].' siap diambil'" icon="imgIconCheck" tone="teal" />
    <x-workspace-stat label="Rata-rata nota" :value="\App\Support\Workspace::money($stats['average'])" note="Transaksi hari ini" icon="imgIconReceipt" />
    <x-workspace-stat label="Belum lunas" :value="$stats['unpaid_count']" note="Transaksi menunggu pembayaran" icon="imgIconAlert" tone="orange" />
</div>
<div class="two-column">
    <section class="card">
        <div class="section-head">
            <h2>Omzet 7 hari</h2>
            <span class="chart-note">{{ \App\Support\Workspace::money($weekly->sum('value')) }}</span>
        </div>
        <x-workspace-chart :points="$weekly" />
    </section>
    <div class="stack">
        <div class="insight">
            <h3>Perlu perhatian</h3>
            <p>{{ $overdue }} cucian sudah siap diambil lebih dari 3 hari.</p>
            <a class="btn btn-small" href="{{ route('notifications.index') }}">Lihat pemberitahuan</a>
        </div>
        <div class="insight">
            <h3>Kinerja cabang</h3>
            <p>Omzet bulan ini {{ \App\Support\Workspace::money($stats['month_omzet']) }}.</p>
            <a class="btn btn-small" href="{{ route('reports.index') }}">Lihat laporan toko</a>
        </div>
        <div class="insight">
            <h3>Operasional</h3>
            <p>{{ $todayAttendances->unique('user_id')->count() }} staf tercatat masuk hari ini. {{ $stats['today_transactions_count'] }} transaksi diterima server hari ini.</p>
        </div>
    </div>
</div>
<div class="two-column">
    <section>
        <div class="section-head">
            <h2>Aktivitas cucian terbaru</h2>
            <a href="{{ route('transactions.index') }}">Lihat semua</a>
        </div>
        <div class="card table-card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Pelanggan</th>
                            <th>Layanan</th>
                            <th>Status</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $trx)<tr>
                            <td>
                                <a href="{{ route('transactions.show', $trx->uuid) }}">{{ $trx->transaction_number }}</a>
                                <small class="muted">{{ \App\Support\Workspace::date($trx->created_at) }}</small>
                            </td>
                            <td>{{ $trx->customer?->name ?? 'Pelanggan' }}</td>
                            <td>{{ $trx->items->pluck('service_name')->filter()->join(', ') ?: 'Layanan' }}</td>
                            <td>
                                <span class="badge {{ $trx->laundry_status === 'SIAP_DIAMBIL' ? 'badge-success' : ($trx->laundry_status === 'DITERIMA' ? 'badge-teal' : '') }}">{{ str_replace('_', ' ', $trx->laundry_status) }}</span>
                            </td>
                            <td>{{ \App\Support\Workspace::money($trx->total) }}</td>
                        </tr>@empty<tr>
                            <td colspan="5" class="empty">Belum ada transaksi tersinkron pada cabang ini.</td>
                        </tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection
