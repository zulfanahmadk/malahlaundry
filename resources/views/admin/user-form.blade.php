@extends('layouts.admin')
@section('title', $user->exists ? 'Edit pengguna' : 'Tambah pengguna')
@section('page_title', $user->exists ? 'Edit pengguna' : 'Tambah pengguna')
@section('page_subtitle', 'Atur akun, peran, dan penempatan cabang')
@section('content')
<div class="card" style="max-width:680px">
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="stack" data-admin-user-form>@csrf
        <div class="form-group"><label for="name">Nama lengkap</label><input id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required></div>
        <div class="form-group"><label for="username">Username</label><input id="username" name="username" value="{{ old('username', $user->username) }}" maxlength="50" required autocomplete="off"></div>
        <div class="form-group"><label for="role">Peran</label><select id="role" name="role" required>@foreach(['cashier' => 'Kasir', 'owner' => 'Owner', 'admin' => 'Admin'] as $value => $label)<option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="form-group" data-staff-branch><label for="branch-id">Cabang penempatan</label><select id="branch-id" name="branch_id">@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((int) old('branch_id', $user->branch_id ?? $branches->first()?->id) === $branch->id)>{{ $branch->name }}{{ $branch->active ? '' : ' · Nonaktif' }}</option>@endforeach</select></div>
        <p class="form-help" data-branch-access style="margin-bottom:16px">Owner dapat mengakses semua cabang. Kasir hanya dapat mengakses cabang penempatan. Admin mengelola sistem.</p>
        <div class="form-group"><label for="active">Status akun</label><select id="active" name="active"><option value="1" @selected((int) old('active', $user->active) === 1)>Aktif</option><option value="0" @selected((int) old('active', $user->active) === 0)>Nonaktif</option></select></div>
        <div class="form-group"><label for="password">{{ $user->exists ? 'Password baru (opsional)' : 'Password' }}</label><input id="password" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" @if(!$user->exists) required @endif><p class="form-help">{{ $user->exists ? 'Kosongkan untuk mempertahankan password saat ini.' : 'Minimal 8 karakter.' }}</p></div>
        <p class="form-help" style="margin-bottom:16px">Perubahan username, password, peran, atau cabang meminta perangkat pengguna untuk login kembali.</p>
        <div class="filter-actions"><button class="btn btn-primary">Simpan pengguna</button><a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Batal</a></div>
    </form>
</div>
@endsection
