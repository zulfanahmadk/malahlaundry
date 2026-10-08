@extends('layouts.admin')
@section('title', 'Riwayat login')
@section('page_title', 'Riwayat login · '.$user->name)
@section('page_subtitle', 'IP, perangkat, dan lokasi yang diizinkan pengguna')
@section('content')
<div class="card table-card"><div class="table-container"><table>
<thead><tr><th>Waktu</th><th>Kanal / perangkat</th><th>IP</th><th>Lokasi</th></tr></thead>
<tbody>@forelse($logins as $login)<tr>
<td>{{ \App\Support\Workspace::date($login->created_at) }} WIB</td>
<td>{{ $login->channel }}<small class="muted">{{ $login->device }}</small><details><summary>Detail perangkat</summary>{{ $login->user_agent ?: 'Tidak tercatat' }}</details></td>
<td>{{ $login->ip_address ?: 'Tidak tercatat' }}</td>
<td>@if($login->latitude !== null)<a href="https://www.google.com/maps?q={{ $login->latitude }},{{ $login->longitude }}" target="_blank" rel="noopener noreferrer">{{ $login->latitude }}, {{ $login->longitude }}</a><small class="muted">Dilaporkan {{ \App\Support\Workspace::date($login->location_at) }} WIB</small>@else Belum diizinkan / belum tersedia @endif</td>
</tr>@empty<tr><td colspan="4" class="empty">Belum ada riwayat. Data dicatat pada login berikutnya.</td></tr>@endforelse</tbody>
</table></div></div>
<nav class="toolbar">@if($logins->previousPageUrl())<a class="btn btn-secondary" href="{{ $logins->previousPageUrl() }}">Sebelumnya</a>@endif<span>Halaman {{ $logins->currentPage() }} dari {{ $logins->lastPage() }}</span>@if($logins->nextPageUrl())<a class="btn btn-secondary" href="{{ $logins->nextPageUrl() }}">Berikutnya</a>@endif<a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Kembali ke pengguna</a></nav>
@endsection
