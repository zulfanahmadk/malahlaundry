@extends('layouts.admin')
@section('title', 'Ringkasan sistem')
@section('page_title', 'Ringkasan sistem')
@section('content')
    <div class="section-head"><p>Jumlah pengguna dan cabang di seluruh sistem.</p><a class="btn btn-secondary" href="{{ route('admin.dashboard') }}">Perbarui ringkasan</a></div>
    <div class="stats-grid">
        <x-workspace-stat label="Pengguna toko" :value="number_format($summary['users'], 0, ',', '.')" :note="$summary['owners'].' owner · '.$summary['cashiers'].' kasir · '.$summary['active_users'].' aktif'" icon="imgIconUsers" />
        <x-workspace-stat label="Cabang" :value="number_format($summary['branches'], 0, ',', '.')" :note="$summary['active_branches'].' aktif · '.($summary['branches'] - $summary['active_branches']).' nonaktif'" icon="imgIconBuilding" tone="green" />
        <x-workspace-stat label="Layanan" :value="number_format($summary['services'], 0, ',', '.')" :note="$summary['active_services'].' aktif · '.($summary['services'] - $summary['active_services']).' nonaktif'" icon="imgIconCheck" tone="teal" />
        <x-workspace-stat label="Rilis APK" :value="number_format($summary['apk_releases'], 0, ',', '.')" note="Aplikasi utama dan QA" icon="imgIconBuilding" tone="orange" />
    </div>
    <section class="card" style="margin-top:20px">
        <h2>Pengelolaan pengguna</h2>
        <p>Kelola akun admin, owner, dan kasir. Lihat penempatan dan akses cabang setiap pengguna.</p>
        <a class="btn btn-primary" href="{{ route('admin.users.index') }}">Kelola pengguna</a>
    </section>
    <section class="card" style="margin-top:20px">
        <h2>Pembaruan aplikasi Android</h2>
        <p>Terbitkan APK baru dan lihat riwayat versi aplikasi.</p>
        <a class="btn btn-primary" href="{{ route('admin.apk.index') }}">Kelola versi APK</a>
    </section>
@endsection
