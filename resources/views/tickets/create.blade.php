@extends('layouts.app')
@section('figma_node', '127-1725')
@section('title', 'Tiket baru')
@section('page_title', 'Tiket baru')
@section('page_subtitle', 'Laporkan masalah atau ajukan peningkatan aplikasi')
@section('page_actions')<a class="btn btn-secondary" href="{{ route('tickets.index') }}">Kembali ke tiket</a>@endsection
@section('content')
<div class="ticket-detail-grid">
    <section class="card">
        <h2 class="section-title">Detail laporan</h2>
        <form method="POST" action="{{ route('tickets.store') }}" class="stack">@csrf
            <input type="hidden" name="submission_uuid" value="{{ old('submission_uuid', $submissionUuid) }}">
            <div class="form-grid">
                <div class="form-group"><label for="type">Jenis tiket</label><select id="type" name="type" required>@foreach(\App\Models\SupportTicket::TYPES as $value => $label)<option value="{{ $value }}" @selected(old('type', 'BUGS') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="form-group"><label for="platform">Aplikasi terkait</label><select id="platform" name="platform" required>@foreach(\App\Models\SupportTicket::PLATFORMS as $value => $label)<option value="{{ $value }}" @selected(old('platform', 'WEB') === $value)>{{ $label }}</option>@endforeach</select></div>
            </div>
            <div class="form-group"><label for="subject">Judul laporan</label><input id="subject" name="subject" value="{{ old('subject') }}" maxlength="180" placeholder="Contoh: QR nota tidak terbaca di Android" required></div>
            <div class="form-group"><label for="description">Uraian laporan</label><textarea id="description" name="description" rows="9" maxlength="10000" placeholder="Jelaskan masalah, langkah yang dilakukan, dan hasil yang diharapkan. Untuk enhancement, jelaskan fitur yang Anda butuhkan." required>{{ old('description') }}</textarea></div>
            <div class="filter-actions"><button class="btn btn-primary">Kirim tiket ke admin</button><a class="btn btn-secondary" href="{{ route('tickets.index') }}">Batal</a></div>
        </form>
    </section>
    <aside class="stack">
        <section class="card"><h2>Cabang laporan</h2><p class="ticket-section-text">{{ request()->attributes->get('branch')->name }}</p><p class="form-help">Cabang aktif dicatat saat tiket dikirim. Pilih cabang di bagian atas sebelum mengirim laporan.</p></section>
        <section class="card"><h2>Setelah tiket dikirim</h2><ol class="ticket-steps"><li>Admin meninjau laporan Anda.</li><li>Keputusan diterima atau ditolak muncul pada tiket.</li><li>Jika diterima, pantau progres dan catatan pengerjaan.</li><li>Hasil akhir tersedia saat tiket selesai.</li></ol><p class="form-help">Anda dapat menambahkan tanggapan pada halaman tiket.</p></section>
    </aside>
</div>
@endsection
