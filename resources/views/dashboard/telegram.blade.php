@extends('layouts.app')
@section('title', 'Telegram Owner')
@section('page_title', 'Notifikasi Telegram')
@section('page_subtitle', 'Penerima transaksi untuk akun owner Anda di cabang aktif')
@section('content')
<div class="settings-top">
    <a class="btn btn-secondary" href="{{ route('settings.edit') }}">Kembali ke Toko & Nota</a>
    <span class="badge {{ $botConfigured ? 'badge-success' : '' }}">{{ $botConfigured ? 'Token bot sudah dikonfigurasi' : 'Token bot belum dikonfigurasi' }}</span>
</div>
<div class="two-column">
    <div class="stack">
        <form class="card" action="{{ route('settings.telegram.update') }}" method="POST">@csrf
            <div class="section-head"><h2>Penerima dan jenis pesan</h2><small>{{ request()->attributes->get('branch')->name }}</small></div>
            <p class="form-help">Pengaturan ini hanya berlaku untuk akun Anda di cabang aktif. Untuk cabang lain, pilih cabang di bagian atas halaman.</p>
            <div class="form-group">
                <label for="telegram-chat-id">Chat ID penerima</label>
                <input id="telegram-chat-id" name="chat_id" type="text" inputmode="text" maxlength="32" pattern="-?[1-9][0-9]{0,19}" value="{{ old('chat_id', $subscription->chat_id) }}" placeholder="Contoh: 123456789 atau -1001234567890" autocomplete="off">
                <p class="form-help">Isi angka dari message.chat.id setelah penerima menekan Start dan mengirim pesan ke bot. Untuk grup, tambahkan bot dan gunakan Chat ID grup.</p>
            </div>
            <x-workspace-switch name="enabled" label="Aktifkan notifikasi Telegram" note="Pesan otomatis dikirim untuk transaksi baru setelah fitur diaktifkan." :checked="old('enabled', $subscription->enabled)" />
            <x-workspace-switch name="notify_created" label="Transaksi baru" note="Dikirim setelah transaksi dan rincian layanan berhasil tersimpan di server." :checked="old('notify_created', $subscription->notify_created)" />
            <x-workspace-switch name="notify_picked_up" label="Cucian sudah diambil" note="Dikirim saat status menjadi Selesai dan waktu pengambilan tercatat." :checked="old('notify_picked_up', $subscription->notify_picked_up)" />
            <button class="btn btn-primary" type="submit">Simpan pengaturan Telegram</button>
        </form>
    </div>
    <aside class="stack">
        <section class="card">
            <h2>Periksa koneksi</h2>
            <p class="form-help">Simpan Chat ID terlebih dahulu. Pesan percobaan dapat dikirim walaupun notifikasi otomatis belum diaktifkan.</p>
            @if(!$botConfigured)
                <div class="notice">Token bot perlu dikonfigurasi oleh pengelola server sebelum pesan dapat dikirim.</div>
            @endif
            <form action="{{ route('settings.telegram.test') }}" method="POST">@csrf
                <button class="btn btn-secondary" type="submit" @disabled(!$botConfigured || !$subscription->chat_id)>Kirim pesan percobaan</button>
            </form>
        </section>
        <section class="card">
            <h2>Dari testing ke owner</h2>
            <p class="form-help">Owner membuka bot dan menekan Start. Ganti Chat ID testing dengan Chat ID owner, simpan, lalu kirim pesan percobaan. Transfer ownership bot tidak mengganti penerima secara otomatis.</p>
            <p class="form-help">Saat Android offline, pesan dikirim setelah sinkronisasi berhasil. Waktu pada pesan mengikuti waktu transaksi dalam WIB.</p>
        </section>
    </aside>
</div>
<section class="card" style="margin-top:24px">
    <div class="section-head"><h2>Riwayat pengiriman</h2><a class="btn btn-secondary" href="{{ route('settings.telegram.edit') }}">Perbarui riwayat</a></div>
    <p class="form-help">Status Terkirim berarti Telegram sudah mengonfirmasi pesan. Pesan yang gagal sementara dicoba ulang otomatis hingga 5 kali.</p>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Waktu</th><th>Pesan</th><th>Chat ID</th><th>Status</th><th>Percobaan</th><th>Hasil / tindakan</th></tr></thead>
            <tbody>
                @forelse($deliveries as $delivery)
                    <tr>
                        <td>{{ \App\Support\Workspace::date($delivery->created_at) }} WIB</td>
                        <td>{{ ['created' => 'Transaksi baru', 'picked_up' => 'Sudah diambil', 'test' => 'Pesan percobaan'][$delivery->event] ?? $delivery->event }}
                            @if($delivery->transaction_uuid)<br><a href="{{ route('transactions.show', $delivery->transaction_uuid) }}">Lihat transaksi</a>@endif
                        </td>
                        <td>{{ $delivery->chat_id }}</td>
                        <td><span class="badge {{ $delivery->status === 'sent' ? 'badge-success' : '' }}">{{ ['pending' => 'Menunggu', 'sent' => 'Terkirim', 'failed' => 'Gagal', 'cancelled' => 'Dibatalkan'][$delivery->status] ?? $delivery->status }}</span></td>
                        <td>{{ $delivery->attempts }}/5</td>
                        <td>
                            @if($delivery->sent_at){{ \App\Support\Workspace::date($delivery->sent_at) }} WIB @else{{ $delivery->last_error ?? 'Menunggu proses pengiriman.' }}@endif
                            @if($delivery->status === 'failed' && $delivery->chat_id === $subscription->chat_id)
                                <form action="{{ route('settings.telegram.retry', $delivery->id) }}" method="POST">@csrf<button class="btn btn-small btn-secondary" type="submit">Coba kirim ulang</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><strong>Belum ada pengiriman Telegram</strong><p>Simpan penerima lalu kirim pesan percobaan. Transaksi lama tidak dikirim ulang saat fitur diaktifkan.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $deliveries->links() }}
</section>
@endsection
