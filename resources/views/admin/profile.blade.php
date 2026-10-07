@extends('layouts.admin')
@section('title', 'Profil akun')
@section('page_title', 'Profil akun')
@section('page_subtitle', 'Keamanan akun admin')
@section('content')
    <section class="card"><h2 class="section-title">Ganti password admin</h2><form method="POST" action="{{ route('admin.password') }}" class="stack">@csrf
        <div class="form-group"><label for="current-password">Password saat ini</label><input id="current-password" type="password" name="current_password" autocomplete="current-password" required></div>
        <div class="form-group"><label for="new-password">Password baru</label><input id="new-password" type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required></div>
        <div class="form-group"><label for="confirm-password">Konfirmasi password baru</label><input id="confirm-password" type="password" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password" required></div>
        <button class="btn btn-primary">Simpan password</button>
    </form></section>
@endsection
