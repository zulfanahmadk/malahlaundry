@extends('layouts.app')
@section('figma_node', '127-255')
@section('title', 'Cucian')
@section('page_title', 'Daftar cucian')
@section('page_subtitle', 'Pantau transaksi cabang aktif dari aplikasi Android')
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Semua" :value="$stats['total']" note="Transaksi tercatat" icon="imgIconCheck" />
    <x-workspace-stat label="Diterima" :value="$stats['received']" note="Sedang dikerjakan" icon="imgIconActivity" tone="teal" />
    <x-workspace-stat label="Siap diambil" :value="$stats['ready']" note="Menunggu pelanggan" icon="imgIconCheck1" tone="green" />
    <x-workspace-stat label="Belum lunas" :value="$stats['unpaid']" note="Belum lunas" icon="imgIconAlert" tone="orange" />
</div>
<form class="toolbar" method="GET">
    <div class="search-field">
        <x-figma-icon name="imgIconSearch" />
        <input name="q" type="search" placeholder="Cari daftar cucian" aria-label="Cari kode, nama, atau WhatsApp" value="{{ request('q') }}">
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="status" aria-label="Status cucian">
                <option value="">Semua status cucian</option>@foreach(['DITERIMA'=>'Diterima','SIAP_DIAMBIL'=>'Siap diambil','SELESAI'=>'Selesai'] as $key=>$label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="payment" aria-label="Status pembayaran">
                <option value="">Semua pembayaran</option>
                <option value="BELUM" @selected(request('payment') === 'BELUM')>Belum lunas</option>
                <option value="LUNAS" @selected(request('payment') === 'LUNAS')>Lunas</option>
            </select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <div class="form-group">
        <label for="from">Dari tanggal</label>
        <input id="from" type="date" name="from" value="{{ request('from') }}">
    </div>
    <div class="form-group">
        <label for="to">Sampai tanggal</label>
        <input id="to" type="date" name="to" value="{{ request('to') }}">
    </div>
    <button class="btn btn-secondary">Terapkan</button>
    <a class="btn btn-small" href="{{ route('transactions.index') }}">Reset</a>
    <span class="count">{{ $transactions->total() }} data</span>
</form>
<div class="card table-card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Pelanggan</th>
                    <th>Layanan</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Pembayaran</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)<tr>
                    <td class="table-code">
                        <a href="{{ route('transactions.show', $trx->uuid) }}">{{ $trx->transaction_number }}</a>
                        <small class="muted">{{ \App\Support\Workspace::date($trx->created_at) }}</small>
                    </td>
                    <td>@if($trx->customer)<a href="{{ route('customers.show', $trx->customer_uuid) }}">{{ $trx->customer->name }}</a>@else Pelanggan @endif</td>
                    <td>{{ $trx->items->map(fn ($i) => $i->service_name ?? $i->service?->name ?? 'Layanan')->join(', ') }}</td>
                    <td>{{ $trx->items->map(fn ($i) => \App\Support\Workspace::number($i->qty, 2).' '.(($i->unit ?? $i->service?->unit) === 'm2' ? 'm²' : ($i->unit ?? $i->service?->unit)))->join(', ') }}</td>
                    <td>
                        <span class="badge {{ $trx->laundry_status === 'SIAP_DIAMBIL' ? 'badge-success' : ($trx->laundry_status === 'DITERIMA' ? 'badge-teal' : '') }}">{{ str_replace('_', ' ', $trx->laundry_status) }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $trx->isPaid() ? 'badge-success' : 'badge-warning' }}">{{ $trx->isPaid() ? 'LUNAS' : 'BELUM LUNAS' }}</span>
                    </td>
                    <td class="table-nowrap">{{ \App\Support\Workspace::money($trx->total) }}</td>
                </tr>@empty<tr>
                    <td colspan="7" class="empty">Tidak ada cucian yang sesuai filter.</td>
                </tr>@endforelse
            </tbody>
        </table>
    </div>
</div>{{ $transactions->links('components.pagination') }}
<div class="insights">
    <div class="insight">
        <h3>Transaksi aktif</h3>
        <p>{{ $stats['received'] + $stats['ready'] }} transaksi sedang dipantau pada cabang ini.</p>
    </div>
    <div class="notice orange">
        <x-figma-icon name="imgIconAlert" />Pembuatan, pembayaran, pemindaian, dan penyelesaian transaksi tersedia di aplikasi Android. Rincian transaksi di web hanya dapat dibaca.</div>
</div>
@endsection
