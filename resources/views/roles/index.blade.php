@extends('layouts.app')
@section('title', 'Role & akses')
@section('page_title', 'Role & akses')
@section('page_subtitle', 'Pilih menu yang terlihat dan tindakan yang dapat digunakan')
@section('content')
@php
    $prefix = auth()->user()->isAdmin() ? 'admin.' : '';
@endphp
<p class="form-help">Role bawaan tetap tersedia. Role buatan membatasi akses sesuai peran dasarnya. Role owner yang dibuat di sini berlaku untuk cabang aktif.</p>
@foreach($roles->concat([new \App\Models\AccessRole(['name' => '', 'base_role' => 'cashier', 'permissions' => ['dashboard.view','profile.view']])]) as $accessRole)
@php
    $formKey = (string) ($accessRole->id ?? 'new');
    $restoreInput = (string) old('_role_form') === $formKey;
    $formName = $restoreInput ? old('name') : $accessRole->name;
    $formBase = $restoreInput ? old('base_role') : $accessRole->base_role;
    $formPermissions = $restoreInput ? old('permissions', []) : ($accessRole->permissions ?? []);
@endphp
<details class="card" style="margin-bottom:16px" @if(!$accessRole->exists || $restoreInput) open @endif>
    <summary><strong>{{ $accessRole->exists ? $accessRole->name : 'Buat role baru' }}</strong> {{ $accessRole->exists ? ' · '.$accessRole->base_role : '' }}</summary>
    @if($accessRole->exists && !auth()->user()->isAdmin() && !$accessRole->branch_id)
    <p class="form-help">Role bersama dikelola admin. Anda dapat memilihnya pada formulir pengguna.</p>
    <p>{{ collect($accessRole->permissions)->filter(fn($permission) => str_ends_with($permission, '.view'))->map(fn($permission) => $features[explode('.', $permission)[0]] ?? $permission)->join(', ') }}</p>
    @else
    <form class="stack" method="POST" action="{{ $accessRole->exists ? route($prefix.'roles.update', $accessRole) : route($prefix.'roles.store') }}">@csrf<input type="hidden" name="_role_form" value="{{ $formKey }}">
        <div class="form-group"><label>Nama role<input name="name" value="{{ $formName }}" maxlength="100" required placeholder="Contoh: Supervisor"></label></div>
        <div class="form-group"><label>Peran dasar<select name="base_role" data-role-base>@foreach((auth()->user()->isAdmin() ? ['cashier'=>'Kasir','owner'=>'Owner','admin'=>'Admin'] : ['cashier'=>'Kasir','owner'=>'Owner']) as $value => $label)<option value="{{ $value }}" @selected($formBase === $value)>{{ $label }}</option>@endforeach</select></label></div>
        <p class="form-help">Centang menu dan tindakan yang diizinkan. Unduh data hanya tersedia untuk pelanggan, laporan, dan presensi. Tindakan berlabel Android digunakan di aplikasi.</p>
        <x-permission-options :selected="$formPermissions" />
        <button class="btn btn-primary">{{ $accessRole->exists ? 'Simpan role' : 'Buat role' }}</button>
    </form>
    @if($accessRole->exists)<form method="POST" action="{{ route($prefix.'roles.delete', $accessRole) }}" style="margin-top:12px">@csrf<button class="btn btn-danger">Hapus role</button></form>@endif
    @endif
</details>
@endforeach
@endsection
@push('scripts')
<script>
const roleDefaults = @json($roleDefaults);
const actorPermissions = @json($actorPermissions);
document.querySelectorAll('[data-role-base]').forEach(select => {
    const refresh = () => {
        select.form.querySelectorAll('input[name="permissions[]"]').forEach(input => {
            const supported = roleDefaults[select.value].includes(input.value);
            input.closest('[data-permission-option]').style.display = supported ? 'flex' : 'none';
            input.disabled = !supported || !actorPermissions.includes(input.value);
            if (input.disabled) input.checked = false;
        });
        select.form.querySelectorAll('[data-access-feature]').forEach(row => {
            row.hidden = ![...row.querySelectorAll('[data-permission-option]')].some(option => option.style.display !== 'none');
        });
    };
    select.addEventListener('change', refresh); refresh();
    select.form.querySelector('[data-permission-options]').addEventListener('change', event => {
        const input = event.target;
        if (!input.matches('input[type="checkbox"]')) return;
        const row = input.closest('[data-access-feature]');
        if (input.dataset.permissionAction === 'view' && !input.checked) row.querySelectorAll('input').forEach(item => { item.checked = false; });
        if (input.dataset.permissionAction !== 'view' && input.checked) row.querySelector('[data-permission-action="view"]').checked = true;
    });
});
</script>
@endpush
