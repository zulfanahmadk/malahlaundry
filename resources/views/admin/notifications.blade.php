@extends('layouts.admin')
@section('figma_node', '127-1725')
@section('title', 'Notifikasi')
@section('page_title', 'Notifikasi')
@section('page_subtitle', 'Pemberitahuan tiket baru dari owner')
@section('page_actions')
<a class="btn btn-secondary" href="{{ route('admin.notifications.index', request()->query()) }}">Perbarui</a>
<form method="POST" action="{{ route('admin.notifications.read') }}">@csrf<input type="hidden" name="through_ticket_id" value="{{ $notificationSummary['through_ticket_id'] }}"><button class="btn btn-primary">Tandai semua dibaca</button></form>
@endsection
@section('content')
<div class="stats-grid admin-notification-stats">
    <x-workspace-stat label="Belum dibaca" :value="$notificationSummary['unread']" note="Tiket baru untuk akun admin ini" icon="imgIconBell" tone="orange" />
    <x-workspace-stat label="Sudah dibaca" :value="$notificationSummary['total'] - $notificationSummary['unread']" note="Status baca disimpan per admin" icon="imgIconCheck" tone="green" />
    <x-workspace-stat label="Total notifikasi" :value="$notificationSummary['total']" note="Satu pemberitahuan per tiket" icon="imgSidebarIconTemplatePesan" />
</div>
<form class="toolbar" method="GET">
    <div class="search-field"><x-figma-icon name="imgIconSearch" /><input type="search" name="q" aria-label="Cari notifikasi" value="{{ request('q') }}" placeholder="Cari nomor atau judul tiket"></div>
    <div class="form-group"><select name="read" aria-label="Status dibaca"><option value="">Semua status</option><option value="0" @selected(request('read') === '0')>Belum dibaca</option><option value="1" @selected(request('read') === '1')>Sudah dibaca</option></select></div>
    <button class="btn btn-secondary">Terapkan</button><a class="btn btn-small" href="{{ route('admin.notifications.index') }}">Reset</a><span class="count">{{ $tickets->total() }} notifikasi</span>
</form>
<section class="card table-card"><div class="table-container"><table>
    <thead><tr><th>Waktu</th><th>Notifikasi tiket</th><th>Pengirim / cabang</th><th>Status baca</th><th>Penanganan</th></tr></thead>
    <tbody>@forelse($tickets as $ticket)<tr>
        <td>{{ \App\Support\Workspace::date($ticket->created_at) }} WIB</td>
        <td class="ticket-title"><a href="{{ route('admin.tickets.show', $ticket) }}"><strong class="ticket-number">{{ $ticket->number }}</strong><span class="ticket-subject">{{ $ticket->subject }}</span></a><small>{{ \App\Models\SupportTicket::TYPES[$ticket->type] }} · Tiket baru dari owner</small></td>
        <td>{{ $ticket->submittedBy?->name ?? 'Akun tidak tersedia' }}<small class="muted">{{ $ticket->branch?->name ?? 'Cabang tidak tersedia' }}</small></td>
        <td><span class="badge {{ $ticket->notification_read_at === null ? 'badge-primary' : 'badge-success' }}">{{ $ticket->notification_read_at === null ? 'BELUM DIBACA' : 'DIBACA' }}</span></td>
        <td><span class="badge {{ $ticket->statusTone() }}">{{ $ticket->statusLabel() }}</span></td>
    </tr>@empty<tr><td colspan="5" class="empty">Belum ada notifikasi tiket yang sesuai filter.</td></tr>@endforelse</tbody>
</table></div></section>
@if($tickets->hasPages())<nav class="toolbar" aria-label="Halaman notifikasi">@if($tickets->previousPageUrl())<a class="btn btn-secondary" href="{{ $tickets->previousPageUrl() }}">Sebelumnya</a>@endif<span>Halaman {{ $tickets->currentPage() }} dari {{ $tickets->lastPage() }}</span>@if($tickets->nextPageUrl())<a class="btn btn-secondary" href="{{ $tickets->nextPageUrl() }}">Berikutnya</a>@endif</nav>@endif
<p class="form-help">Membuka detail tiket otomatis menandainya dibaca untuk akun Anda. Status baca tidak mengubah keputusan atau progres tiket.</p>
@endsection
