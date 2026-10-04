@extends('layouts.app')
@section('figma_node', '127-2541')
@section('title', 'Template pesan')
@section('page_title', 'Template pesan')
@section('page_subtitle', 'Kelola pesan operasional untuk pelanggan · pengiriman manual')
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Template tersedia" :value="count($store['templates'])" note="Mengikuti cabang aktif" icon="imgIconMessage" tone="green" />
    <x-workspace-stat label="Pengiriman" value="Manual" note="Kasir menekan Kirim di WhatsApp" icon="imgIconMessage1" />
    <x-workspace-stat label="Siap diambil" value="Tersedia" note="Pratinjau dari aplikasi Android" icon="imgIconMessage2" tone="teal" />
    <x-workspace-stat label="Pengingat" value="Manual" note="Kasir/user membuka pesan sendiri" icon="imgIconAlert" tone="orange" />
</div>
<div class="settings-top">
    <span class="badge">{{ $store['branch_name'] ?? 'Cabang utama' }} · WhatsApp</span>
    <span class="badge badge-success">Data tersimpan</span>
</div>
<form class="two-column" action="{{ route('templates.update') }}" method="POST">@csrf<div class="stack">
        <section class="card">
            <div class="section-head">
                <h2>Informasi utama</h2>
                <small>Template pesan</small>
            </div>
            <div class="form-grid four">
                <div>
                    <span class="field-label">Cabang</span>
                    <p>{{ $store['branch_name'] ?? 'Utama' }}</p>
                </div>
                <div>
                    <span class="field-label">Pemicu pratinjau</span>
                    <p>Status transaksi dipilih kasir</p>
                </div>
                <div>
                    <span class="field-label">Saluran</span>
                    <p>WhatsApp</p>
                </div>
                <div>
                    <span class="field-label">Pengiriman</span>
                    <p>Dikirim oleh kasir / user</p>
                </div>
            </div>
        </section>
        <section class="card">
            <h2>Preferensi</h2>
            <x-workspace-switch name="message_preferences[WA_DITERIMA]" label="Template diterima" note="Izinkan kasir membuka pesan saat cucian diterima." :checked="old('message_preferences.WA_DITERIMA', $store['message_preferences']['WA_DITERIMA'] ?? true)" icon="imgIconSemantic" />
            <x-workspace-switch name="message_preferences[WA_SIAP_DIAMBIL]" label="Template siap diambil" note="Izinkan kasir membuka pesan saat status siap diambil." :checked="old('message_preferences.WA_SIAP_DIAMBIL', $store['message_preferences']['WA_SIAP_DIAMBIL'] ?? true)" icon="imgIconSemantic1" />
            <x-workspace-switch name="message_preferences[WA_REMINDER]" label="Pengingat pengambilan" note="Izinkan pratinjau pengingat; kasir tetap mengirim secara manual." :checked="old('message_preferences.WA_REMINDER', $store['message_preferences']['WA_REMINDER'] ?? true)" icon="imgIconSemantic2" />
        </section>
        <section class="card">
            <h2>Isi pesan</h2>@foreach(['WA_DITERIMA'=>'Cucian diterima','WA_SIAP_DIAMBIL'=>'Cucian siap diambil','WA_SELESAI'=>'Cucian selesai','WA_REMINDER'=>'Pengingat pengambilan'] as $key=>$label)<div class="form-group" style="margin-top:18px">
                <label for="template-{{ $key }}">{{ $label }}</label>
                <textarea id="template-{{ $key }}" name="templates[{{ $key }}]" data-template-input="{{ $key }}" rows="5" maxlength="4000" required>{{ old('templates.'.$key, $store['templates'][$key] ?? \App\Services\StoreConfiguration::TEMPLATES[$key]) }}</textarea>
            </div>@endforeach<p class="form-help">Variabel: {nama}, {outlet}, {no_transaksi}, {items}, {total}, {status_bayar}, {sisa_bayar}, {url_nota}, {alamat_outlet}, {telepon_outlet}, {tanggal_siap}. Variabel diganti dengan data transaksi saat kasir membuka pratinjau di Android.</p>
        </section>
    </div>
    <aside class="stack">
        <section class="card">
            <div class="section-head">
                <h2>Pratinjau pesan</h2>
                <small>Siap diambil</small>
            </div>
            <x-workspace-markers :first="$store['branch_name'] ?? 'Cabang utama'" :second="'Variabel diganti dari data transaksi. Pengiriman manual.'" />
            <div class="message-preview" data-template-preview="WA_SIAP_DIAMBIL">{{ old('templates.WA_SIAP_DIAMBIL', $store['templates']['WA_SIAP_DIAMBIL']) }}</div>
            <p class="form-help">Pratinjau menggunakan variabel template. Pesan pelanggan dibuka dari transaksi di aplikasi Android.</p>
            <button class="btn btn-primary" style="margin-top:14px">
                <x-figma-icon name="imgIconSave" />Simpan perubahan</button>
        </section>
        <div class="notice">
            <x-figma-icon name="imgIconInfo" />Aplikasi membuka WhatsApp dengan teks yang sudah diisi. Kasir atau user tetap menekan tombol Kirim sendiri.</div>
    </aside>
</form>
@endsection
