@extends('layouts.app')
@section('figma_node', '127-489')
@section('title', 'Detail cucian')
@section('page_title', 'Detail cucian')
@section('page_subtitle', $transaction->transaction_number.' · transaksi hanya-baca')
@section('hide_branch', 'true')
@section('page_actions')<a class="btn btn-secondary" href="{{ route('transactions.index') }}">
    <x-figma-icon name="imgIconArrowLeft" />Kembali ke daftar</a>@endsection
@section('content')
<div class="detail-stats">
    <div class="customer-summary">
        <span class="avatar">{{ \App\Support\Workspace::initials($transaction->customer?->name ?? 'Pelanggan') }}</span>
        <div>
            <h2>{{ $transaction->customer?->name ?? 'Pelanggan' }}</h2>
            <small>{{ $transaction->customer?->phone }}</small>
            <small>Masuk {{ \App\Support\Workspace::date($transaction->created_at) }} WIB</small>
        </div>
    </div>
    <x-workspace-stat label="Status" :value="str_replace('_', ' ', $transaction->laundry_status)" note="Status terakhir tersinkron" icon="imgIconCheck" tone="green" />
    <x-workspace-stat label="Total" :value="\App\Support\Workspace::money($transaction->total)" :note="$transaction->isPaid() ? 'Lunas' : 'Belum lunas'" icon="imgIconAlert" tone="orange" />
    <x-workspace-stat label="Layanan" :value="$transaction->items->count()" note="Tercatat" icon="imgIconService" tone="teal" />
</div>
<div class="two-column">
    <section class="stack">
        <div>
            <div class="section-head">
                <h2>Rincian layanan</h2>
                <small>{{ $transaction->items->count() }} layanan</small>
            </div>
            <div class="card table-card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Layanan</th>
                                <th>Keterangan</th>
                                <th>Nilai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>@foreach($transaction->items as $item)<tr>
                                <td>{{ $item->service_name ?? $item->service?->name ?? 'Layanan' }}</td>
                                <td>{{ \App\Support\Workspace::number($item->qty, 2) }} {{ ($item->unit ?? $item->service?->unit) === 'm2' ? 'm²' : ($item->unit ?? $item->service?->unit) }} × {{ \App\Support\Workspace::money($item->price) }}</td>
                                <td>{{ \App\Support\Workspace::money($item->subtotal) }}</td>
                                <td>
                                    <span class="badge badge-success">{{ str_replace('_', ' ', $transaction->laundry_status) }}</span>
                                </td>
                            </tr>@endforeach</tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="notice">
            <x-figma-icon name="imgIconInfo" />Riwayat ini dapat dilihat di web; operasi transaksi tersedia di aplikasi Android.</div>
        <a href="{{ $transaction->public_receipt_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">Buka nota digital</a>
    </section>
    <aside class="stack">
        <div class="insight">
            <h3>Informasi transaksi</h3>
            <p>Kasir {{ $transaction->user?->name ?? '—' }} · Cabang {{ request()->attributes->get('branch')->name }} · Sumber aplikasi Android</p>
            <dl class="detail-meta">
                <dt>Estimasi selesai</dt>
                <dd>{{ \App\Support\Workspace::date($transaction->estimated_at) }}</dd>
            </dl>
        </div>
        <div class="insight">
            <h3>Riwayat status</h3>
            <dl class="detail-meta">
                <dt>Diterima</dt>
                <dd>{{ \App\Support\Workspace::date($transaction->created_at) }}</dd>
                <dt>Siap diambil</dt>
                <dd>{{ \App\Support\Workspace::date($transaction->ready_at) }}</dd>
                <dt>Diambil</dt>
                <dd>{{ \App\Support\Workspace::date($transaction->picked_up_at) }}</dd>
            </dl>
            <p>Seluruh waktu menggunakan WIB.</p>
        </div>
        <div class="insight">
            <h3>Riwayat pembayaran</h3>
            <p>{{ $transaction->isPaid() ? 'Pembayaran lunas' : 'Belum ada pembayaran tercatat.' }}</p>@if($transaction->isPaid())<dl class="detail-meta">
                <dt>Dibayar</dt>
                <dd>{{ \App\Support\Workspace::date($transaction->paid_at) }}</dd>
                <dt>Metode</dt>
                <dd>{{ $transaction->payment_method ?? 'Belum tercatat' }}</dd>
            </dl>@endif</div>
    </aside>
</div>
@endsection
