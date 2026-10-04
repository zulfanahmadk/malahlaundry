@extends('layouts.admin')
@section('title', 'Log Audit')
@section('page_title', 'Log Audit')
@section('content')
    <p style="margin-bottom:16px">Riwayat aktivitas sistem. Waktu ditampilkan dalam WIB.</p>
    <section class="card">
        <form method="GET" action="{{ route('admin.audit.index') }}" class="stack">
            <div class="stats-grid">
                <div class="form-group"><label for="from">Tanggal mulai</label><input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
                <div class="form-group"><label for="to">Tanggal akhir</label><input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div>
                <div class="form-group"><label for="feature">Fitur</label><select id="feature" name="feature"><option value="">Semua fitur</option>@foreach($features as $feature)<option value="{{ $feature }}" @selected(($filters['feature'] ?? '') === $feature)>{{ ucfirst(str_replace('-', ' ', $feature)) }}</option>@endforeach</select></div>
                <div class="form-group"><label for="outcome">Hasil</label><select id="outcome" name="outcome"><option value="">Semua hasil</option>@foreach(['success' => 'Berhasil', 'rejected' => 'Ditolak', 'error' => 'Gagal'] as $value => $label)<option value="{{ $value }}" @selected(($filters['outcome'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="form-group"><label for="role">Peran</label><select id="role" name="role"><option value="">Semua peran</option>@foreach(['admin' => 'Admin', 'owner' => 'Owner', 'cashier' => 'Kasir', 'anonymous' => 'Tanpa akun'] as $value => $label)<option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="form-group"><label for="actor-id">ID pengguna</label><input id="actor-id" type="number" min="1" name="actor_id" value="{{ $filters['actor_id'] ?? '' }}" placeholder="Semua pengguna"></div>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap"><button class="btn btn-primary">Terapkan filter</button><a class="btn btn-secondary" href="{{ route('admin.audit.index') }}">Reset filter</a></div>
        </form>
    </section>
    <section class="card">
        <div class="section-head"><h2>Aktivitas</h2><span>{{ number_format($logs->total(), 0, ',', '.') }} catatan</span></div>
        <div class="table-container"><table>
            <thead><tr><th>Waktu (WIB)</th><th>Pengguna</th><th>Fitur / aktivitas</th><th>Cabang</th><th>Hasil</th></tr></thead>
            <tbody>@forelse($logs as $log)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($log->occurred_at, 'UTC')->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H.i') }}</td>
                    <td>{{ $log->actor_id ? 'ID '.$log->actor_id : 'Tanpa akun' }}<br><small>{{ ['admin' => 'Admin', 'owner' => 'Owner', 'cashier' => 'Kasir'][$log->actor_role] ?? '—' }}</small></td>
                    <td><strong>{{ \App\Support\AuditActivity::label($log->action, $log->method) }}</strong><br>{{ ucfirst(str_replace('-', ' ', $log->feature)) }} · {{ $log->channel === 'api' ? 'Android / API' : 'Web' }}
                        <details><summary>Detail teknis</summary><span style="overflow-wrap:anywhere;white-space:normal">{{ $log->method }} {{ $log->action }}</span><br><small style="overflow-wrap:anywhere;white-space:normal">ID permintaan: {{ $log->request_id ?? '—' }}</small></details>
                    </td>
                    <td>{{ $log->branch_id ? 'ID '.$log->branch_id : '—' }}</td>
                    <td>{{ ['success' => 'Berhasil', 'rejected' => 'Ditolak', 'error' => 'Gagal'][$log->outcome] ?? $log->outcome }}<br><small>HTTP {{ $log->status }}</small></td>
                </tr>
            @empty<tr><td colspan="5">Belum ada catatan audit untuk filter ini.</td></tr>@endforelse</tbody>
        </table></div>
        @if($logs->hasPages())
            <nav aria-label="Halaman log audit" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:16px">
                @if($logs->previousPageUrl())<a class="btn btn-secondary" href="{{ $logs->previousPageUrl() }}">Sebelumnya</a>@endif
                <span>Halaman {{ $logs->currentPage() }} dari {{ $logs->lastPage() }}</span>
                @if($logs->nextPageUrl())<a class="btn btn-secondary" href="{{ $logs->nextPageUrl() }}">Berikutnya</a>@endif
            </nav>
        @endif
    </section>
@endsection
