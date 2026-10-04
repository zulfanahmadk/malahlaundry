@extends($admin ? 'layouts.admin' : 'layouts.app')
@section('figma_node', '127-1725')
@section('title', $admin ? 'Tiket masuk' : 'Tiket bantuan')
@section('page_title', $admin ? 'Tiket masuk' : 'Tiket bantuan')
@section('page_subtitle', $admin ? 'Tinjau laporan owner dan perbarui progres penanganan' : 'Kirim laporan dan pantau progres tiket Anda')
@section('page_actions')
    <a class="btn btn-secondary" href="{{ route($admin ? 'admin.tickets.index' : 'tickets.index', request()->query()) }}">Perbarui</a>
    @if(!$admin)<a class="btn btn-primary" href="{{ route('tickets.create') }}"><x-figma-icon name="imgIconPlus" />Tiket baru</a>@endif
@endsection
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Total tiket" :value="$stats['total']" :note="$stats['rejected'].' ditolak'" icon="imgIconUsers" />
    <x-workspace-stat label="Menunggu keputusan" :value="$stats['submitted']" note="Diajukan ke admin" icon="imgIconAlert" tone="orange" />
    <x-workspace-stat label="Dalam penanganan" :value="$stats['working']" note="Diterima atau dikerjakan" icon="imgIconBuilding" tone="teal" />
    <x-workspace-stat label="Selesai" :value="$stats['resolved']" note="Hasil sudah tersedia" icon="imgIconCheck" tone="green" />
</div>
<form class="toolbar" method="GET">
    <div class="search-field"><x-figma-icon name="imgIconSearch" /><input type="search" name="q" aria-label="Cari tiket" placeholder="Cari nomor atau judul tiket" value="{{ request('q') }}"></div>
    <div class="form-group"><select name="type" aria-label="Jenis tiket"><option value="">Semua jenis</option>@foreach(\App\Models\SupportTicket::TYPES as $value => $label)<option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="form-group"><select name="status" aria-label="Status tiket"><option value="">Semua status</option>@foreach(\App\Models\SupportTicket::STATUSES as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
    <button class="btn btn-secondary">Terapkan</button><a class="btn btn-small" href="{{ route($admin ? 'admin.tickets.index' : 'tickets.index') }}">Reset</a><span class="count">{{ $tickets->total() }} tiket</span>
</form>
<div class="card table-card"><div class="table-container"><table>
    <thead><tr><th>Tiket</th>@if($admin)<th>Pengirim</th>@endif<th>Cabang laporan</th><th>Status / progres</th><th>Diperbarui</th></tr></thead>
    <tbody>@forelse($tickets as $ticket)<tr>
        <td class="ticket-title"><a href="{{ route($admin ? 'admin.tickets.show' : 'tickets.show', $ticket) }}"><strong class="ticket-number">{{ $ticket->number }}</strong><span class="ticket-subject">{{ $ticket->subject }}</span></a><small>{{ \App\Models\SupportTicket::TYPES[$ticket->type] }} · {{ \App\Models\SupportTicket::PLATFORMS[$ticket->platform] }}</small></td>
        @if($admin)<td>{{ $ticket->submittedBy?->name ?? 'Akun tidak tersedia' }}<small>{{ $ticket->submittedBy?->username }}</small></td>@endif
        <td>{{ $ticket->branch?->name ?? '—' }}</td>
        <td><span class="badge {{ $ticket->statusTone() }}">{{ $ticket->statusLabel() }}</span><div class="ticket-progress-line"><progress max="100" value="{{ $ticket->progress }}" aria-label="Progres {{ $ticket->number }}">{{ $ticket->progress }}%</progress><small>{{ $ticket->progress }}%</small></div></td>
        <td>{{ \App\Support\Workspace::date($ticket->updated_at) }} WIB</td>
    </tr>@empty<tr><td colspan="{{ $admin ? 5 : 4 }}" class="empty">{{ $admin ? 'Belum ada tiket owner yang sesuai filter.' : 'Belum ada tiket yang sesuai filter. Gunakan Tiket baru untuk mengirim laporan.' }}</td></tr>@endforelse</tbody>
</table></div></div>
@if($tickets->hasPages())<nav class="toolbar" aria-label="Halaman tiket">@if($tickets->previousPageUrl())<a class="btn btn-secondary" href="{{ $tickets->previousPageUrl() }}">Sebelumnya</a>@endif<span>Halaman {{ $tickets->currentPage() }} dari {{ $tickets->lastPage() }}</span>@if($tickets->nextPageUrl())<a class="btn btn-secondary" href="{{ $tickets->nextPageUrl() }}">Berikutnya</a>@endif</nav>@endif
@endsection
