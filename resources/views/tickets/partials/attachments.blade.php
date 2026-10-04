@if($attachments->isNotEmpty())
<ul class="ticket-attachments" aria-label="Lampiran aktivitas tiket">
    @foreach($attachments as $attachment)
    <li><a href="{{ route($admin ? 'admin.tickets.attachments.download' : 'tickets.attachments.download', [$ticket, $attachment]) }}">
        <span class="ticket-file-type">{{ strtoupper(pathinfo($attachment->original_name, PATHINFO_EXTENSION)) }}</span>
        <span class="ticket-file-name">{{ $attachment->original_name }}<small>{{ number_format(max(1, ceil($attachment->size / 1024)), 0, ',', '.') }} KB · Unduh</small></span>
    </a></li>
    @endforeach
</ul>
@endif
