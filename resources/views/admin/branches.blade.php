@extends('layouts.admin')
@section('title', 'Cabang')
@section('page_title', 'Cabang')
@section('page_subtitle', 'Lihat penempatan owner dan kasir di seluruh cabang')
@section('content')
<div class="insight" style="margin-bottom:20px"><h3>Akses cabang</h3><p>Owner dapat mengakses seluruh cabang. Jumlah di tabel menunjukkan cabang penempatan setiap akun. Kasir hanya dapat mengakses cabang penempatan.</p></div>
<div class="card table-card"><div class="table-container"><table><thead><tr><th>Cabang</th><th>Kode</th><th>Status</th><th>Owner ditempatkan</th><th>Kasir ditempatkan</th><th>Pengguna</th></tr></thead><tbody>
@forelse($branches as $branch)<tr><td>{{ $branch->name }}</td><td>{{ $branch->code }}</td><td>{{ $branch->active ? 'Aktif' : 'Nonaktif' }}</td><td>{{ $counts->get($branch->id)?->owners ?? 0 }}</td><td>{{ $counts->get($branch->id)?->cashiers ?? 0 }}</td><td><a class="btn btn-small btn-secondary" href="{{ route('admin.users.index', ['branch_id' => $branch->id]) }}">Lihat pengguna</a></td></tr>@empty<tr><td colspan="6" class="empty">Belum ada cabang.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
