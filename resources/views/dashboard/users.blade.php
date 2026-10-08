@extends('layouts.app')
@section('figma_node', '127-1725')
@section('title', 'Pengguna')
@section('page_title', 'Pengguna')
@section('page_subtitle', 'Kelola akun dan penempatan staf')
@section('page_actions')<button class="btn btn-primary" data-open-dialog="user-create">
    <x-figma-icon name="imgIconPlus" />Tambah pengguna</button>@endsection
@section('content')
<div class="stats-grid">
    <x-workspace-stat label="Total pengguna" :value="$stats['total']" note="Terdaftar pada cabang ini" icon="imgIconUsers" />
    <x-workspace-stat label="Akun aktif" :value="$stats['active']" note="Bisa mengakses aplikasi" icon="imgIconCheck" tone="green" />
    <x-workspace-stat label="Perangkat tercatat" :value="$stats['devices']" note="Pernah melaporkan sinkronisasi" icon="imgIconBuilding" tone="teal" />
    <x-workspace-stat label="Nonaktif" :value="$stats['inactive']" note="Akses dinonaktifkan" icon="imgIconAlert" tone="orange" />
</div>
<form class="toolbar" method="GET">
    <div class="search-field">
        <x-figma-icon name="imgIconSearch" />
        <input type="search" name="q" placeholder="Cari pengguna" aria-label="Cari nama atau username" value="{{ request('q') }}">
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="role" aria-label="Peran pengguna">
                <option value="">Semua peran</option>
                <option value="owner" @selected(request('role') === 'owner')>Owner</option>
                <option value="cashier" @selected(request('role') === 'cashier')>Kasir</option>
            </select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <div class="form-group">
        <span class="select-field">
            <select name="active" aria-label="Status pengguna">
                <option value="">Semua status</option>
                <option value="1" @selected(request('active') === '1')>Aktif</option>
                <option value="0" @selected(request('active') === '0')>Nonaktif</option>
            </select>
            <x-figma-icon name="imgIconChevron" />
        </span>
    </div>
    <button class="btn btn-secondary">Terapkan</button>
    <a class="btn btn-small" href="{{ route('users.index') }}">Reset</a>
    <span class="count">{{ $users->count() }} data</span>
</form>
<div class="card table-card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nama pengguna</th>
                    <th>Username</th>
                    <th>Peran</th>
                    <th>Cabang</th>
                    <th>Aktivitas / aksi</th>
                </tr>
            </thead>
            <tbody>@forelse($users as $user)<tr>
                    <td>{{ $user->name }}<small class="muted">{{ $user->active ? 'Aktif' : 'Nonaktif' }}</small>
                    </td>
                    <td>{{ $user->username }}</td>
                    <td>
                        <span class="badge {{ $user->isOwner() ? 'badge-primary' : '' }}">{{ $user->isOwner() ? 'OWNER' : 'KASIR' }}</span>@if($user->accessRole)<small class="muted">{{ $user->accessRole->name }}</small>@endif
                    </td>
                    <td>{{ request()->attributes->get('branch')->name }}</td>
                    <td>
                        <small class="table-nowrap">{{ \App\Support\Workspace::date($user->last_login_at) }}</small>
                        <div class="table-actions table-actions-secondary">
                            <a class="btn btn-small btn-secondary" href="{{ route('users.edit', $user) }}">Edit</a>
                            <form action="{{ route('users.toggle', $user->id) }}" method="POST">@csrf<button class="btn btn-small {{ $user->active ? 'btn-danger' : 'btn-secondary' }}">{{ $user->active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </div>
                    </td>
                </tr>@empty<tr>
                    <td colspan="5" class="empty">Belum ada pengguna yang sesuai filter.</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
</div>
<div class="insights">
    <div class="insight">
        <h3>Akses owner</h3>
        <p>Mengelola cabang, layanan, pengguna, laporan, dan pengaturan melalui web atau aplikasi Android.</p>
    </div>
    <div class="insight">
        <h3>Akses kasir</h3>
        <p>Mencatat transaksi, presensi, dan pengiriman pesan WhatsApp manual di aplikasi Android pada cabang penempatan.</p>
    </div>
</div>
<dialog class="dialog" id="user-create">
    <div class="section-head">
        <h2>Tambah pengguna</h2>
        <button class="btn btn-secondary btn-small" data-close-dialog type="button">Tutup</button>
    </div>
    <form action="{{ route('users.store') }}" method="POST">@csrf<input type="hidden" name="_workspace_dialog" value="user-create">
        <div class="form-grid">
            <div class="form-group">
                <label>Nama lengkap<input name="name" value="{{ old('name') }}" maxlength="255" required>
                </label>
            </div>
            <div class="form-group">
                <label>Username<input name="username" value="{{ old('username') }}" maxlength="50" autocomplete="off" required>
                </label>
            </div>
            <div class="form-group">
                <label>Peran<span class="select-field">
                        <select name="role">
                            <option value="cashier">Kasir</option>
                            <option value="owner" @selected(old('role') === 'owner')>Owner</option>
                        </select>
                        <x-figma-icon name="imgIconChevron" />
                    </span>
                </label>
            </div>
            <div class="form-group">
                <label>Cabang penempatan<span class="select-field">
                        <select name="branch_id">@foreach(\App\Models\Branch::orderBy('name')->get() as $option)<option value="{{ $option->id }}" @selected((int) old('branch_id', request()->attributes->get('branch_id')) === $option->id)>{{ $option->name }}</option>@endforeach</select>
                        <x-figma-icon name="imgIconChevron" />
                    </span>
                </label>
            </div>
            <x-access-role-select />
            <div class="form-group span-all">
                <label>Password<input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                </label>
            </div>
        </div>
        <button class="btn btn-primary">Simpan pengguna</button>
    </form>
</dialog>
@endsection
