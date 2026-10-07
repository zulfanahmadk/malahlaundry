@extends('layouts.admin')
@section('title', 'Versi APK')
@section('page_title', 'Versi APK')
@section('content')
    <section class="card">
        <h2 class="section-title">Upload APK baru</h2>
        <p class="form-help">Versi dan package dibaca otomatis dari APK. APK utama dan QA dikelola terpisah. Gunakan APK bertanda tangan sama dengan aplikasi yang sudah terpasang.</p>
        <form method="POST" action="{{ route('admin.apk.upload') }}" enctype="multipart/form-data" class="stack">@csrf
            <div class="form-group"><label for="apk">File APK (maksimal 100 MB)</label><input id="apk" type="file" name="apk" accept=".apk,application/vnd.android.package-archive" required></div>
            <div class="form-group"><label for="notes">Catatan pembaruan</label><textarea id="notes" name="notes" maxlength="4000" rows="4">{{ old('notes') }}</textarea></div>
            <button class="btn btn-primary">Upload dan terbitkan</button>
        </form>
    </section>
    <section class="card"><h2 class="section-title">Riwayat APK</h2><div class="table-container"><table>
        <thead><tr><th>Versi</th><th>Jenis aplikasi</th><th>Ukuran</th><th>Diterbitkan</th><th>Catatan</th><th>APK</th></tr></thead>
        <tbody>@forelse($releases as $release)<tr><td>{{ $release->version_name }}<br><small>Code {{ $release->version_code }}</small></td><td>{{ str_ends_with($release->package_name, '.qa') ? 'QA' : 'Utama' }}</td><td>{{ number_format($release->size / 1048576, 1) }} MB</td><td>{{ \App\Support\Workspace::date($release->created_at) }} WIB</td><td style="white-space:pre-line">{{ $release->notes }}</td><td><a href="{{ url('/api/v1/app-releases/'.$release->id.'/download') }}">Unduh</a></td></tr>@empty<tr><td colspan="6">Belum ada APK yang diterbitkan.</td></tr>@endforelse</tbody>
    </table></div>{{ $releases->links() }}</section>
@endsection
