@extends('layouts.app')
@section('figma_node', '127-2735')
@section('title', 'Profil')
@section('page_title', 'Profil')
@section('page_subtitle', 'Pengaturan akun owner dan keamanan akses')
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Cabang dikelola" :value="\App\Models\Branch::count()" note="Akses owner" icon="imgIconBuilding" />
    <x-workspace-stat label="Login terakhir" :value="\App\Support\Workspace::date($user->last_login_at)" note="WIB" icon="imgIconClock" tone="teal" :date="true" />
    <x-workspace-stat label="Sesi Android" :value="$tokens->count()" note="Token akses yang masih berlaku" icon="imgIconDevice" />
    <x-workspace-stat label="Keamanan" value="Password" note="Konfirmasi untuk perubahan profil" icon="imgIconShield" tone="green" />
</div>
<div class="settings-top">
    <span class="badge">{{ $user->username }} · Owner</span>
    <span class="badge badge-success">Akun aktif</span>
</div>
<form class="two-column" method="POST" action="{{ route('profile.update') }}">@csrf<div class="stack">
        <section class="card">
            <div class="section-head">
                <h2>Informasi utama</h2>
                <small>Profil akun</small>
            </div>
            <div class="form-grid four">@foreach(['name'=>'Nama lengkap','username'=>'Username','phone'=>'WhatsApp','email'=>'Email'] as $key=>$label)<div class="form-group">
                    <label for="profile-{{ $key }}">{{ $label }}</label>
                    <input id="profile-{{ $key }}" name="{{ $key }}" type="{{ $key === 'email' ? 'email' : ($key === 'phone' ? 'tel' : 'text') }}" value="{{ old($key, $user->$key) }}" maxlength="{{ $key === 'username' ? 50 : ($key === 'phone' ? 40 : 255) }}" @required(in_array($key,['name','username']))>
                </div>@endforeach</div>
        </section>
        <section class="card">
            <h2>Keamanan akun</h2>
            <div class="form-grid" style="margin-top:18px">
                <div class="form-group span-all">
                    <label for="current-password">Password saat ini</label>
                    <input id="current-password" name="current_password" type="password" autocomplete="current-password" required>
                </div>
                <div class="form-group">
                    <label for="new-password">Password baru (opsional)</label>
                    <input id="new-password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm-password">Konfirmasi password baru</label>
                    <input id="confirm-password" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password">
                </div>
            </div>
            <x-workspace-switch name="notify_login" label="Pemberitahuan login" note="Tampilkan aktivitas login terakhir pada notifikasi owner." :checked="old('notify_login', $user->notify_login)" icon="imgIconSemantic" />
            <x-workspace-switch name="revoke_tokens" label="Keluar dari seluruh sesi Android" note="Diterapkan saat disimpan. Data dan antrean lokal tetap tersimpan." :checked="old('revoke_tokens', false)" icon="imgIconSemantic1" />
            @if(config('session.driver') === 'database')<x-workspace-switch name="revoke_web" label="Keluar dari sesi web lainnya" note="Diterapkan saat disimpan. Sesi web ini dipertahankan." :checked="old('revoke_web', false)" icon="imgIconSemantic2" />@endif<p class="form-help">Perubahan username atau password juga mencabut akses Android dan sesi web lain. Sesi web ini tetap dipertahankan.</p>
        </section>
    </div>
    <aside class="stack">
        <section class="card">
            <div class="section-head">
                <h2>Aktivitas akun</h2>
                <small>Terakhir tercatat</small>
            </div>
            <x-workspace-markers :first="$user->name.' · Owner'" :second="'Konfirmasi password untuk menyimpan perubahan.'" />
            <dl class="detail-meta">
                <dt>Login terakhir</dt>
                <dd>{{ \App\Support\Workspace::date($user->last_login_at) }}</dd>
                <dt>Akun diperbarui</dt>
                <dd>{{ \App\Support\Workspace::date($user->updated_at) }}</dd>
            </dl>@forelse($tokens->take(5) as $token)<p class="form-help">{{ $token->name }} · {{ \App\Support\Workspace::date($token->last_used_at ?? $token->created_at) }} WIB</p>@empty<p class="form-help">Belum ada sesi Android aktif.</p>@endforelse<button class="btn btn-primary" style="margin-top:16px">
                <x-figma-icon name="imgIconSave" />Simpan perubahan</button>
        </section>
        <div class="notice">
            <x-figma-icon name="imgIconInfo" />Konfirmasi password melindungi perubahan akun dan pencabutan akses perangkat.</div>
    </aside>
</form>
@endsection
