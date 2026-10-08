@props(['user' => null])
@php
    $accessDefaults = collect(['admin', 'owner', 'cashier'])->mapWithKeys(fn ($base) => [$base => \App\Support\Access::defaults($base)]);
    $actor = auth()->user();
    $grantablePermissions = $actor->isAdmin() && !$actor->access_role_id && $actor->menu_permissions === null
        ? $accessDefaults->flatten()->unique()->values()->all() : \App\Support\Access::permissions($actor);
    $selectedPermissions = old('menu_permissions', $user?->menu_permissions ?? ($user?->exists ? \App\Support\Access::permissions($user) : ['dashboard.view', 'profile.view']));
    $customAccess = old('access_mode', $user?->menu_permissions !== null ? 'custom' : 'role') === 'custom';
@endphp
<div class="form-group"><label for="access-role-id">Role & akses menu</label>
<select id="access-role-id" name="access_role_id"><option value="">Akses bawaan sesuai peran</option>
@foreach(\App\Support\Access::roleOptions(auth()->user()) as $option)<option value="{{ $option->id }}" data-permissions='@json($option->permissions)' data-base-role="{{ $option->base_role }}" data-branch-id="{{ $option->branch_id ?? 0 }}" @selected((int) old('access_role_id', $user?->access_role_id) === $option->id)>{{ $option->name }} ({{ $option->base_role }})</option>@endforeach
</select><p class="form-help">Pilih role yang sesuai peran dasar dan cabang penempatan.</p></div>
<div data-user-access data-defaults='@json($accessDefaults)' data-grantable='@json($grantablePermissions)'>
    <div class="form-group"><label>Akses menu pengguna
        <select name="access_mode"><option value="role" @selected(!$customAccess)>Ikuti akses role / peran</option><option value="custom" @selected($customAccess)>Atur khusus untuk pengguna ini</option></select>
    </label></div>
    <fieldset data-user-permissions style="border:0;padding:0;margin:0" @disabled(!$customAccess) @if(!$customAccess) hidden @endif>
        <legend>Menu dan tindakan yang boleh diakses</legend>
        <p class="form-help">Akses khusus hanya berlaku untuk pengguna ini dan tidak mengubah pengguna lain. Beranda dan Profil wajib tersedia. Pilihan tetap mengikuti batas role.</p>
        <x-permission-options name="menu_permissions" :selected="$selectedPermissions" />
    </fieldset>
</div>
@once
@push('scripts')
<script>
document.querySelectorAll('select[name="access_role_id"]').forEach(profile => {
    const base = profile.form.querySelector('select[name="role"]');
    const branch = profile.form.querySelector('select[name="branch_id"]');
    const access = profile.form.querySelector('[data-user-access]');
    const mode = access.querySelector('[name="access_mode"]');
    const fieldset = access.querySelector('[data-user-permissions]');
    const defaults = JSON.parse(access.dataset.defaults);
    const grantable = JSON.parse(access.dataset.grantable);
    const refreshPermissions = () => {
        const selectedRole = profile.selectedOptions[0];
        const available = selectedRole?.value ? JSON.parse(selectedRole.dataset.permissions) : defaults[base.value];
        fieldset.hidden = mode.value !== 'custom';
        fieldset.disabled = mode.value !== 'custom';
        fieldset.querySelectorAll('input[type="checkbox"]').forEach(input => {
            const supported = available.includes(input.value) && defaults[base.value].includes(input.value);
            input.closest('[data-permission-option]').style.display = supported ? 'flex' : 'none';
            input.disabled = !supported || !grantable.includes(input.value);
            if (input.disabled) input.checked = false;
        });
        fieldset.querySelectorAll('[data-access-feature]').forEach(row => {
            row.hidden = ![...row.querySelectorAll('[data-permission-option]')].some(option => option.style.display !== 'none');
        });
    };
    const refresh = () => {
        [...profile.options].forEach(option => {
            if (!option.value) return;
            option.hidden = option.dataset.baseRole !== base.value || (branch && option.dataset.branchId !== '0' && option.dataset.branchId !== branch.value);
        });
        if (profile.selectedOptions[0]?.hidden) profile.value = '';
        refreshPermissions();
    };
    profile.addEventListener('change', () => {
        const option = profile.selectedOptions[0];
        if (option?.dataset.baseRole) { base.value = option.dataset.baseRole; base.dispatchEvent(new Event('change')); }
    });
    base.addEventListener('change', refresh);
    branch?.addEventListener('change', refresh);
    mode.addEventListener('change', () => {
        if (mode.value === 'custom') {
            const available = profile.value ? JSON.parse(profile.selectedOptions[0].dataset.permissions) : defaults[base.value];
            fieldset.querySelectorAll('input[type="checkbox"]').forEach(input => { input.checked = available.includes(input.value) && grantable.includes(input.value); });
        }
        refreshPermissions();
    });
    profile.addEventListener('change', refreshPermissions);
    fieldset.addEventListener('change', event => {
        const input = event.target;
        if (!input.matches('input[type="checkbox"]')) return;
        const row = input.closest('[data-access-feature]');
        if (input.dataset.permissionAction === 'view' && !input.checked) row.querySelectorAll('input').forEach(item => { item.checked = false; });
        if (input.dataset.permissionAction !== 'view' && input.checked) row.querySelector('[data-permission-action="view"]').checked = true;
    });
    refresh();
});
</script>
@endpush
@endonce
