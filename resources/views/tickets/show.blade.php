@extends($admin ? 'layouts.admin' : 'layouts.app')
@section('figma_node', '127-1725')
@section('title', $ticket->number)
@section('page_title', 'Detail tiket')
@section('page_subtitle', $ticket->number)
@section('page_actions')<a class="btn btn-secondary" href="{{ route($admin ? 'admin.tickets.index' : 'tickets.index') }}">Daftar tiket</a><a class="btn btn-secondary" href="{{ route($admin ? 'admin.tickets.show' : 'tickets.show', $ticket) }}">Perbarui</a>@endsection
@section('content')
<div class="ticket-detail-grid ticket-view">
    <div class="stack">
        <section class="card ticket-report">
            <div class="section-head"><span class="badge {{ $ticket->statusTone() }}">{{ $ticket->statusLabel() }}</span><small>{{ \App\Models\SupportTicket::TYPES[$ticket->type] }} · {{ \App\Models\SupportTicket::PLATFORMS[$ticket->platform] }}</small></div>
            <h2 class="ticket-subject">{{ $ticket->subject }}</h2>
            <p class="ticket-copy">{{ $ticket->description }}</p>
            <div class="ticket-meta"><span>Cabang: <strong>{{ $ticket->branch?->name ?? '—' }}</strong></span><span>Pengirim: <strong>{{ $ticket->submittedBy?->name ?? 'Akun tidak tersedia' }}</strong></span><span>Diajukan: {{ \App\Support\Workspace::date($ticket->created_at) }} WIB</span></div>
        </section>
        @if($ticket->result)<section class="card ticket-result {{ $ticket->status === 'REJECTED' ? 'ticket-result-rejected' : '' }}"><h2>{{ $ticket->status === 'REJECTED' ? 'Alasan penolakan' : 'Hasil penanganan' }}</h2><p class="ticket-copy">{{ $ticket->result }}</p></section>@endif
        <section class="card"><div class="section-head"><h2>Riwayat dan tanggapan</h2><span>{{ $ticket->updates->count() }} aktivitas</span></div>
            <ol class="ticket-timeline">@foreach($ticket->updates as $update)<li><div class="section-head"><strong>{{ $update->actor_role === 'admin' ? 'Admin' : 'Owner' }} · {{ $update->actor_name }}</strong><small>{{ \App\Support\Workspace::date($update->created_at) }} WIB</small></div><small>{{ \App\Models\SupportTicket::STATUSES[$update->status] }} · {{ $update->progress }}%</small><p class="ticket-copy">{{ $update->message }}</p></li>@endforeach</ol>
        </section>
        <section class="card"><h2 class="section-title">Tambahkan tanggapan</h2><form method="POST" action="{{ route($admin ? 'admin.tickets.reply' : 'tickets.reply', $ticket) }}" class="stack">@csrf<input type="hidden" name="_ticket_form" value="reply"><div class="form-group"><label for="reply-message">Tanggapan</label><textarea id="reply-message" name="message" rows="4" maxlength="10000" placeholder="Tambahkan informasi atau pertanyaan tentang tiket ini." required>{{ old('_ticket_form') === 'reply' ? old('message') : '' }}</textarea></div><button class="btn btn-secondary">Kirim tanggapan</button></form></section>
    </div>
    <aside class="stack">
        <section class="card"><div class="section-head"><h2>Progres</h2><strong>{{ $ticket->progress }}%</strong></div><progress class="ticket-progress" max="100" value="{{ $ticket->progress }}" aria-label="Progres penanganan tiket">{{ $ticket->progress }}%</progress><p class="ticket-section-text">{{ $ticket->statusLabel() }}</p><p class="form-help">Terakhir diperbarui {{ \App\Support\Workspace::date($ticket->updated_at) }} WIB</p></section>
        @if($admin && !$ticket->isClosed())
        @php($options = array_unique(array_merge($ticket->status === 'SUBMITTED' ? [] : [$ticket->status], \App\Models\SupportTicket::TRANSITIONS[$ticket->status])))
        <section class="card"><h2 class="section-title">Keputusan dan progres</h2><form method="POST" action="{{ route('admin.tickets.update', $ticket) }}" class="stack" data-ticket-status-form>@csrf
            <input type="hidden" name="_ticket_form" value="status"><input type="hidden" name="revision" value="{{ $ticket->revision }}">
            <div class="form-group"><label for="ticket-status">Status penanganan</label><select id="ticket-status" name="status" required><option value="" disabled @selected($ticket->status === 'SUBMITTED' && old('_ticket_form') !== 'status')>Pilih keputusan</option>@foreach($options as $value)<option value="{{ $value }}" @selected((old('_ticket_form') === 'status' ? old('status') : $ticket->status) === $value)>{{ \App\Models\SupportTicket::STATUSES[$value] }}</option>@endforeach</select></div>
            <div class="form-group" data-ticket-progress><label for="ticket-progress-input">Progres pengerjaan (%)</label><input id="ticket-progress-input" type="number" name="progress" min="1" max="99" value="{{ old('_ticket_form') === 'status' ? old('progress', max(1, $ticket->progress)) : max(1, $ticket->progress) }}"><p class="form-help">Isi 1–99%. Saat Selesai, progres menjadi 100%.</p></div>
            <div class="form-group"><label for="status-message">Catatan progres / hasil</label><textarea id="status-message" name="message" rows="5" maxlength="10000" required>{{ old('_ticket_form') === 'status' ? old('message') : '' }}</textarea><p class="form-help">Catatan ini terlihat oleh owner. Jelaskan alasan jika menolak, atau hasil perbaikan jika selesai.</p></div>
            <button class="btn btn-primary">Simpan pembaruan</button>
        </form></section>
        @elseif($ticket->isClosed())<section class="card"><h2>Tiket ditutup</h2><p class="ticket-section-text">{{ $ticket->status === 'REJECTED' ? 'Alasan penolakan tersedia pada tiket ini.' : 'Hasil penanganan tersedia pada tiket ini.' }}</p><p class="form-help">Riwayat dan tanggapan tetap dapat dilihat.</p></section>
        @else<section class="card"><h2>Penanganan oleh admin</h2><p class="ticket-section-text">Keputusan, progres, dan hasil penanganan akan muncul di halaman ini.</p></section>@endif
    </aside>
</div>
@endsection
