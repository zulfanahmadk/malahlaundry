@extends('layouts.app')
@section('figma_node', '127-45')
@section('title', 'APK Android')
@section('page_title', 'APK Android')
@section('page_subtitle', 'Cek versi terbaru dan unduh aplikasi untuk perangkat toko')
@section('hide_branch', true)
@section('page_actions')
<a class="btn btn-secondary" href="{{ route('apk.index') }}"><x-figma-icon name="imgIconRefresh" />Cek versi terbaru</a>
@endsection
@section('content')
<section class="card" style="max-width:760px">
    <div class="section-head"><h2>Versi APK terbaru</h2><span class="badge badge-primary">Android</span></div>
    @if($release)
        <div class="form-grid">
            <div><span class="field-label">Versi aplikasi</span><p><strong>{{ $release->version_name }}</strong></p></div>
            <div><span class="field-label">Ukuran unduhan</span><p>{{ number_format($release->size / 1048576, 1, ',', '.') }} MB</p></div>
            <div><span class="field-label">Diterbitkan</span><p>{{ \App\Support\Workspace::date($release->created_at) }} WIB</p></div>
        </div>
        <h3 style="margin-top:20px">Catatan pembaruan</h3>
        <p style="white-space:pre-line;overflow-wrap:anywhere;margin-top:8px">{{ $release->notes ?: 'Tidak ada catatan pembaruan.' }}</p>
        @if($downloadAvailable)
            <a class="btn btn-primary" style="margin-top:20px" href="{{ route('apk.download', $release->id) }}">Unduh APK {{ $release->version_name }}</a>
            <p class="form-help" style="margin-top:12px">Buka file yang diunduh pada HP Android dan ikuti petunjuk pemasangan. Untuk memperbarui aplikasi, pasang APK tanpa menghapus aplikasi yang sudah ada.</p>
        @else
            <div class="alert alert-warning" role="status" style="margin-top:20px">File APK belum tersedia. Hubungi admin untuk menerbitkan APK kembali.</div>
        @endif
    @else
        <p class="empty">Admin belum menerbitkan APK untuk aplikasi toko.</p>
        <p class="form-help">Tekan Cek versi terbaru setelah admin mengunggah APK baru.</p>
    @endif
</section>
@endsection
