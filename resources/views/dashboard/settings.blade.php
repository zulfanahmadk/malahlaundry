@extends('layouts.app')
@section('figma_node', '127-2150')
@section('title', 'Toko & Nota')
@section('page_title', 'Toko & nota')
@section('page_subtitle', 'Identitas cabang dan tampilan nota digital')
@section('content')
@php
    $complete = collect([$store['store_name'] ?? $store['name'], $store['phone'], $store['address'], $store['receipt_terms'], $store['logo_url']])->filter()->count();
    $termLines = $store['receipt_terms'] ? count(preg_split('/\r?\n/', trim($store['receipt_terms']))) : 0;
@endphp
<div class="stats-grid">
    <x-workspace-stat label="Profil toko" :value="$complete.'/5'" note="Identitas yang sudah diisi" icon="imgIconCheck" tone="green" />
    <x-workspace-stat label="Nota digital" value="Aktif" note="Dapat dibuka dari URL nota" icon="imgIconBuilding" />
    <x-workspace-stat label="Ketentuan nota" :value="$termLines.' baris'" note="Mengikuti cabang aktif" icon="imgIconReceipt" tone="teal" />
    <x-workspace-stat label="Logo" :value="$store['logo_url'] ? 'Tersimpan' : 'Belum ada'" note="JPG, PNG atau WebP" icon="imgIconCheck" tone="orange" />
</div>
<div class="settings-top">
    <span class="badge">{{ $store['branch_name'] ?? 'Cabang utama' }} · Pengaturan aktif</span>
    <span class="badge badge-success">Data tersimpan</span>
</div>
<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="two-column">@csrf
    <div class="stack">
        <section class="card">
            <div class="section-head">
                <h2>Informasi utama</h2>
                <small>Cabang aktif</small>
            </div>
            <div class="form-grid four">
                <div class="form-group">
                    <label for="store-name">Nama toko</label>
                    <input id="store-name" name="name" value="{{ old('name', $store['store_name'] ?? $store['name']) }}" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="store-branch">Nama cabang</label>
                    <input id="store-branch" name="branch_name" value="{{ old('branch_name', $store['branch_name'] ?? '') }}" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="store-phone">WhatsApp toko</label>
                    <input id="store-phone" name="phone" value="{{ old('phone', $store['phone']) }}" maxlength="40" type="tel">
                </div>
                <div class="form-group">
                    <label for="store-address">Alamat</label>
                    <textarea id="store-address" name="address" maxlength="1000">{{ old('address', $store['address']) }}</textarea>
                </div>
                <div class="form-group span-all">
                    <label for="receipt-terms">Ketentuan nota</label>
                    <textarea id="receipt-terms" name="receipt_terms" rows="4" maxlength="5000">{{ old('receipt_terms', $store['receipt_terms']) }}</textarea>
                    <p class="form-help">Variabel: {nama_toko}, {cabang}, {nomor_nota}, {hari_komplain}.</p>
                </div>
                <div class="form-group">
                    <label for="complaint-days">Batas komplain (hari)</label>
                    <input id="complaint-days" name="complaint_days" type="number" min="0" max="365" value="{{ old('complaint_days', $store['complaint_days'] ?? 3) }}" required>
                </div>
                <div class="form-group">
                    <label for="store-logo">Logo toko</label>
                    <input id="store-logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp">
                    <p class="form-help">Maksimal 1 MB. Logo akan diperkecil dan disimpan sebagai PNG.</p>@if($store['logo_url'])<label class="checkline">
                        <input type="checkbox" name="remove_logo" value="1">Hapus logo saat disimpan</label>@endif</div>
            </div>
        </section>
        <section class="card">
            <h2>Preferensi</h2>
            <x-workspace-switch name="show_branch" label="Tampilkan cabang" note="Nama cabang tampil pada nota dan identitas toko." :checked="old('show_branch', $store['show_branch'] ?? true)" icon="imgIconSemantic" />
            <x-workspace-switch name="receipt_preferences[show_phone]" label="Tampilkan WhatsApp toko" note="Nomor kontak toko ditampilkan pada nota digital." :checked="old('receipt_preferences.show_phone', $store['receipt_preferences']['show_phone'] ?? true)" icon="imgIconSemantic1" />
            <x-workspace-switch name="receipt_preferences[show_terms]" label="Tampilkan ketentuan" note="Ketentuan cabang ditampilkan pada nota digital." :checked="old('receipt_preferences.show_terms', $store['receipt_preferences']['show_terms'] ?? true)" icon="imgIconSemantic2" />
        </section>
    </div>
    <aside class="stack">
        <section class="card">
            <div class="section-head">
                <h2>Pratinjau nota</h2>
                <small>Data tersimpan</small>
            </div>
            <x-workspace-markers :first="$store['name']" :second="'Kontak dan ketentuan mengikuti preferensi cabang.'" />
            <div class="receipt-preview">@if($store['logo_url'])<img src="{{ $store['logo_url'] }}" alt="Logo toko">@endif<h3>{{ $store['name'] }}</h3>
                <p>{{ $store['address'] }}</p>@if($store['receipt_preferences']['show_phone'] ?? true)<p>{{ $store['phone'] }}</p>@endif<p>Nota digital · {{ \App\Support\Workspace::date(now()) }} WIB</p>@if($store['receipt_preferences']['show_terms'] ?? true)<p>{{ strtr($store['receipt_terms'], ['{nama_toko}'=>$store['store_name'] ?? $store['name'], '{cabang}'=>$store['branch_name'] ?? '', '{nomor_nota}'=>'Nomor nota transaksi', '{hari_komplain}'=>(string) ($store['complaint_days'] ?? 3)]) }}</p>@endif</div>
            <button class="btn btn-primary" style="margin-top:14px">
                <x-figma-icon name="imgIconSave" />Simpan perubahan</button>
        </section>
        <div class="notice">
            <x-figma-icon name="imgIconInfo" />Perubahan berlaku pada nota cabang ini dan perangkat Android setelah sinkronisasi berikutnya.</div>
    </aside>
</form>
@endsection
