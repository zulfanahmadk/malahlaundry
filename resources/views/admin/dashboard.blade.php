@extends('layouts.admin')
@section('title', 'Ringkasan sistem')
@section('page_title', 'Ringkasan sistem')
@section('content')
    <div class="section-head"><p>Jumlah pengguna dan cabang di seluruh sistem.</p><a class="btn btn-secondary" href="{{ route('admin.dashboard') }}">Perbarui ringkasan</a></div>
    <div class="stats-grid">
        <section class="stat-card" aria-label="Jumlah pengguna toko">
            <h2 class="stat-label">Pengguna toko</h2>
            <p class="stat-value">{{ number_format($summary['users'], 0, ',', '.') }}</p>
            <p class="stat-note">{{ $summary['owners'] }} owner · {{ $summary['cashiers'] }} kasir</p>
            <p class="form-help">{{ $summary['active_users'] }} aktif · {{ $summary['users'] - $summary['active_users'] }} nonaktif</p>
        </section>
        <section class="stat-card" aria-label="Jumlah cabang">
            <h2 class="stat-label">Cabang</h2>
            <p class="stat-value">{{ number_format($summary['branches'], 0, ',', '.') }}</p>
            <p class="stat-note">{{ $summary['active_branches'] }} aktif · {{ $summary['branches'] - $summary['active_branches'] }} nonaktif</p>
        </section>
        <section class="stat-card" aria-label="Jumlah layanan">
            <h2 class="stat-label">Layanan</h2>
            <p class="stat-value">{{ number_format($summary['services'], 0, ',', '.') }}</p>
            <p class="stat-note">{{ $summary['active_services'] }} aktif · {{ $summary['services'] - $summary['active_services'] }} nonaktif</p>
        </section>
        <section class="stat-card" aria-label="Jumlah rilis APK">
            <h2 class="stat-label">Rilis APK</h2>
            <p class="stat-value">{{ number_format($summary['apk_releases'], 0, ',', '.') }}</p>
            <p class="stat-note">Aplikasi utama dan QA</p>
        </section>
    </div>
    <section class="card" style="margin-top:20px">
        <h2>Pembaruan aplikasi Android</h2>
        <p>Terbitkan APK baru dan lihat riwayat versi aplikasi.</p>
        <a class="btn btn-primary" href="{{ route('admin.apk.index') }}">Kelola versi APK</a>
    </section>
@endsection
