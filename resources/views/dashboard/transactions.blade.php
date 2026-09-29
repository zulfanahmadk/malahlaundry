@extends('layouts.app')

@section('title', 'Riwayat Transaksi')
@section('page_title', 'Riwayat Transaksi Laundry')

@section('content')
<style>
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
        background: var(--surface);
        padding: 1rem;
        border-radius: 12px;
        border: 1px solid var(--border);
    }
    .filter-input {
        flex: 1 1 160px;
        min-width: 0;
        max-width: 100%;
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        outline: none;
        background: #FFFFFF;
    }
    .filter-input:focus {
        border-color: var(--primary);
    }
    .filter-search {
        flex: 2 1 240px;
    }
    .filter-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .filter-date {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1 1 210px;
        min-width: 0;
        font-size: 0.85rem;
    }
</style>

<div class="card" style="padding: 0; overflow: hidden;">
    <!-- Filter -->
    <form action="{{ route('transactions.index') }}" method="GET" class="filter-bar" style="margin: 0; border: none; border-bottom: 1px solid var(--border); border-radius: 0;">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari No. TRX, nama pelanggan, atau HP..." class="filter-input filter-search" aria-label="Cari transaksi atau pelanggan">
        <select name="status" class="filter-input" aria-label="Status cucian">
            <option value="">Semua Status Cucian</option>
            <option value="DITERIMA" {{ request('status') === 'DITERIMA' ? 'selected' : '' }}>DITERIMA</option>
            <option value="SIAP_DIAMBIL" {{ request('status') === 'SIAP_DIAMBIL' ? 'selected' : '' }}>SIAP DIAMBIL</option>
            <option value="SELESAI" {{ request('status') === 'SELESAI' ? 'selected' : '' }}>SELESAI</option>
        </select>
        <select name="payment" class="filter-input" aria-label="Status pembayaran">
            <option value="">Semua Status Bayar</option>
            <option value="LUNAS" {{ request('payment') === 'LUNAS' ? 'selected' : '' }}>LUNAS</option>
            <option value="BELUM" {{ request('payment') === 'BELUM' ? 'selected' : '' }}>BELUM LUNAS</option>
        </select>
        <label class="filter-date">Dari <input type="date" name="from" value="{{ request('from') }}" class="filter-input"></label>
        <label class="filter-date">Sampai <input type="date" name="to" value="{{ request('to') }}" class="filter-input"></label>
        <div class="filter-actions">
            <a href="{{ route('reports.export', request()->only(['q', 'status', 'payment', 'from', 'to'])) }}" class="btn btn-secondary">Ekspor Hasil (Excel)</a>
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            @if(request()->anyFilled(['q', 'status', 'payment', 'from', 'to']))
                <a href="{{ route('transactions.index') }}" class="btn btn-secondary" style="padding: 0.5rem 0.75rem;">Reset</a>
            @endif
        </div>
    </form>

    <div class="table-responsive">
        <table style="min-width: 1060px;">
            <thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>Waktu (WIB)</th>
                    <th>Tanggal Pengambilan (WIB)</th>
                    <th>Pelanggan</th>
                    <th>Layanan &amp; Qty</th>
                    <th>Total</th>
                    <th>Status Cucian</th>
                    <th>Pembayaran</th>
                    <th>Kasir</th>
                    <th>Nota Digital</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                    <tr>
                        <td><strong>{{ $trx->transaction_number }}</strong></td>
                        <td>{{ $trx->created_at->translatedFormat('d M Y, H:i') }}</td>
                        <td>{{ $trx->picked_up_at?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                        <td>
                            <div>{{ $trx->customer->name ?? 'Pelanggan Umum' }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $trx->customer->phone ?? '-' }}</div>
                        </td>
                        <td>
                            @foreach($trx->items as $item)
                                <div style="font-size: 0.8rem;">
                                    {{ $item->service_name ?? $item->service->name ?? 'Layanan' }} ({{ $item->qty }} {{ $item->unit ?? $item->service->unit ?? '' }})
                                </div>
                            @endforeach
                        </td>
                        <td><strong>Rp{{ number_format($trx->total, 0, ',', '.') }}</strong></td>
                        <td>
                            @if($trx->laundry_status === 'SELESAI')
                                <span class="badge badge-success">Selesai</span>
                            @elseif($trx->laundry_status === 'SIAP_DIAMBIL')
                                <span class="badge badge-success">Siap Diambil</span>
                            @else
                                <span class="badge badge-primary">Diterima</span>
                            @endif
                        </td>
                        <td>
                            @if($trx->payment_status === 'LUNAS')
                                <span class="badge badge-success">Lunas</span>
                            @else
                                <span class="badge badge-danger">Belum Lunas</span>
                            @endif
                        </td>
                        <td><span style="font-size: 0.8rem; color: var(--text-muted);">{{ $trx->user->name ?? '-' }}</span></td>
                        <td>
                            <a href="{{ $trx->public_receipt_url }}" target="_blank" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                Buka Nota &amp; QR
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 2rem;">Tidak ada transaksi ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div style="padding: 1rem;">
            {{ $transactions->links('components.pagination') }}
        </div>
    @endif
</div>
@endsection
