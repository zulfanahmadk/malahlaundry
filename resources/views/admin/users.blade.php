@extends('layouts.admin')
@section('figma_node', '127-1725')
@section('title', 'Pengguna')
@section('page_title', 'Pengguna')
@section('page_subtitle', 'Kelola semua akun dan lihat penempatan cabang')
@section('page_actions')<a class="btn btn-primary" href="{{ route('admin.users.create') }}"><x-figma-icon name="imgIconPlus" />Tambah pengguna</a>@endsection
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Total pengguna" :value="$stats['total']" note="Seluruh akun sistem" icon="imgIconUsers" />
    <x-workspace-stat label="Owner" :value="$stats['owners']" note="Akses seluruh cabang" icon="imgIconBuilding" tone="green" />
    <x-workspace-stat label="Kasir" :value="$stats['cashiers']" note="Sesuai cabang penempatan" icon="imgIconUsers" tone="teal" />
    <x-workspace-stat label="Admin" :value="$stats['admins']" note="Administrasi sistem" icon="imgIconUsers" tone="orange" />
</div>
<form class="toolbar" method="GET">
    <div class="search-field"><x-figma-icon name="imgIconSearch" /><input type="search" name="q" placeholder="Cari nama atau username" aria-label="Cari nama atau username" value="{{ request('q') }}"></div>
    <div class="form-group"><select name="role" aria-label="Peran pengguna"><option value="">Semua peran</option>@foreach(['admin' => 'Admin', 'owner' => 'Owner', 'cashier' => 'Kasir'] as $value => $label)<option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="form-group"><select name="branch_id" aria-label="Cabang penempatan"><option value="">Semua penempatan</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
    <div class="form-group"><select name="active" aria-label="Status pengguna"><option value="">Semua status</option><option value="1" @selected(request('active') === '1')>Aktif</option><option value="0" @selected(request('active') === '0')>Nonaktif</option></select></div>
    <button class="btn btn-secondary">Terapkan</button><a class="btn btn-small" href="{{ route('admin.users.index') }}">Reset</a><span class="count">{{ $users->total() }} pengguna</span>
</form>
<div class="card table-card"><div class="table-container"><table>
    <thead><tr><th>Nama pengguna</th><th>Username</th><th>Peran</th><th>Penempatan dan akses cabang</th><th>Aktivitas / aksi</th></tr></thead>
    <tbody>@forelse($users as $user)<tr>
        <td>{{ $user->name }}<small class="muted">{{ $user->active ? 'Aktif' : 'Nonaktif' }}</small></td>
        <td>{{ $user->username }}</td><td><span class="badge {{ $user->isCashier() ? '' : 'badge-primary' }}">{{ ['admin' => 'ADMIN', 'owner' => 'OWNER', 'cashier' => 'KASIR'][$user->role] }}</span></td>
        <td>@if($user->isAdmin())Administrasi sistem<small class="muted">Tanpa akses operasional cabang</small>@else{{ $user->branch?->name ?? 'Cabang tidak tersedia' }}<small class="muted">{{ $user->branch?->code }}{{ $user->branch && !$user->branch->active ? ' · Nonaktif' : '' }}</small>
            @if($user->isOwner())<details><summary>Akses seluruh cabang ({{ $branches->count() }})</summary>@foreach($branches as $branch)<div>{{ $branch->name }}{{ $branch->active ? '' : ' · Nonaktif' }}</div>@endforeach</details>@else<small class="muted">Akses hanya cabang penempatan</small>@endif
        @endif</td>
        <td><small class="table-nowrap">{{ \App\Support\Workspace::date($user->last_login_at) }}</small><div class="table-actions table-actions-secondary">
            <a class="btn btn-small btn-secondary" href="{{ route('admin.users.edit', $user) }}">Edit</a>
            @if($user->id !== auth()->id())<form action="{{ route('admin.users.toggle', $user) }}" method="POST">@csrf<button class="btn btn-small {{ $user->active ? 'btn-danger' : 'btn-secondary' }}">{{ $user->active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>@endif
        </div></td>
    </tr>@empty<tr><td colspan="5" class="empty">Belum ada pengguna yang sesuai filter.</td></tr>@endforelse</tbody>
</table></div></div>
@if($users->hasPages())<nav aria-label="Halaman pengguna" class="toolbar">@if($users->previousPageUrl())<a class="btn btn-secondary" href="{{ $users->previousPageUrl() }}">Sebelumnya</a>@endif<span>Halaman {{ $users->currentPage() }} dari {{ $users->lastPage() }}</span>@if($users->nextPageUrl())<a class="btn btn-secondary" href="{{ $users->nextPageUrl() }}">Berikutnya</a>@endif</nav>@endif
@endsection
