@extends('layouts.admin')
@section('title', 'Versi APK')
@section('page_title', 'Versi APK')
@section('content')
    <section class="card">
        <h2>Upload APK baru</h2>
        <p class="form-help">Versi dan package dibaca otomatis dari APK. APK utama dan QA dikelola terpisah. Gunakan APK bertanda tangan sama dengan aplikasi yang sudah terpasang.</p>
        <form method="POST" action="{{ route('admin.apk.upload') }}" enctype="multipart/form-data" class="stack">@csrf
            <div class="form-group"><label for="apk">File APK (maksimal 100 MB)</label><input id="apk" type="file" name="apk" accept=".apk,application/vnd.android.package-archive" required></div>
            <div class="form-group"><label for="notes">Catatan pembaruan</label><textarea id="notes" name="notes" maxlength="4000" rows="4">{{ old('notes') }}</textarea></div>
            <button class="btn btn-primary">Upload dan terbitkan</button>
        </form>
    </section>
    <section class="card" style="margin-top:20px"><h2>Riwayat APK</h2><div class="table-container"><table>
        <thead><tr><th>Versi</th><th>Jenis aplikasi</th><th>Ukuran</th><th>Diterbitkan</th><th>Catatan</th><th>APK</th></tr></thead>
        <tbody>@forelse($releases as $release)<tr><td>{{ $release->version_name }}<br><small>Code {{ $release->version_code }}</small></td><td>{{ str_ends_with($release->package_name, '.qa') ? 'QA' : 'Utama' }}</td><td>{{ number_format($release->size / 1048576, 1) }} MB</td><td>{{ \App\Support\Workspace::date($release->created_at) }} WIB</td><td style="white-space:pre-line">{{ $release->notes }}</td><td><a href="{{ url('/api/v1/app-releases/'.$release->id.'/download') }}">Unduh</a></td></tr>@empty<tr><td colspan="6">Belum ada APK yang diterbitkan.</td></tr>@endforelse</tbody>
    </table></div>{{ $releases->links() }}</section>
    <section class="card" style="margin-top:20px"><h2>Ganti password admin</h2><form method="POST" action="{{ route('admin.password') }}" class="stack">@csrf
        <div class="form-group"><label for="current-password">Password saat ini</label><input id="current-password" type="password" name="current_password" autocomplete="current-password" required></div>
        <div class="form-group"><label for="new-password">Password baru</label><input id="new-password" type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required></div>
        <div class="form-group"><label for="confirm-password">Konfirmasi password baru</label><input id="confirm-password" type="password" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password" required></div>
        <button class="btn btn-primary">Simpan password</button>
    </form></section>
@endsection
