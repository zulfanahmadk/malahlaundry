@extends('layouts.app')
@section('figma_node', '127-1725')
@section('title', 'Edit Pengguna')
@section('page_title', 'Update Data Pengguna')
@section('content')
<div class="card" style="max-width: 680px;">
    <h2 class="section-title" style="margin-bottom: 1.25rem;">Edit {{ $user->name }}</h2>
    <form action="{{ route('users.update', $user) }}" method="POST" class="stack">
        @csrf
        <div class="form-group">
            <label for="name">Nama lengkap</label>
            <input id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" maxlength="255" required>
        </div>
        <div class="form-group">
            <label for="username">Username</label>
            <input id="username" name="username" class="form-control" value="{{ old('username', $user->username) }}" maxlength="50" required>
        </div>
        <div class="form-group">
            <label for="role">Peran</label>
            <span class="select-field">
                <select id="role" name="role" class="form-control">
                    <option value="cashier" @selected(old('role', $user->role) === 'cashier')>Kasir</option>
                    <option value="owner" @selected(old('role', $user->role) === 'owner')>Owner</option>
                </select>
                <x-figma-icon name="imgIconChevron" />
            </span>
        </div>
        <div class="form-group">
            <label for="branch_id">Cabang penempatan</label>
            <span class="select-field">
                <select id="branch_id" name="branch_id" class="form-control">@foreach(\App\Models\Branch::orderBy('name')->get() as $branchOption)<option value="{{ $branchOption->id }}" @selected((int) old('branch_id', $user->branch_id) === $branchOption->id)>{{ $branchOption->name }}</option>@endforeach</select>
                <x-figma-icon name="imgIconChevron" />
            </span>
        </div>
        <div class="form-group">
            <label for="active">Status akun</label>
            <span class="select-field">
                <select id="active" name="active" class="form-control">
                    <option value="1" @selected((int) old('active', $user->active) === 1)>Aktif</option>
                    <option value="0" @selected((int) old('active', $user->active) === 0)>Nonaktif</option>
                </select>
                <x-figma-icon name="imgIconChevron" />
            </span>
        </div>
        <div class="form-group">
            <label for="password">Password baru (opsional)</label>
            <input type="password" id="password" name="password" class="form-control" minlength="8" maxlength="72" autocomplete="new-password" placeholder="Kosongkan untuk mempertahankan password">
        </div>
        <p style="color: var(--text-muted); font-size: .85rem; margin-bottom: 1rem;">Perubahan username atau password akan meminta perangkat Android pengguna untuk login kembali.</p>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Simpan perubahan</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
