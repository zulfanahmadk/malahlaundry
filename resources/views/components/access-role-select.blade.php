@props(['user' => null])
<div class="form-group"><label for="access-role-id">Role & akses menu</label>
<select id="access-role-id" name="access_role_id"><option value="">Akses bawaan sesuai peran</option>
@foreach(\App\Support\Access::roleOptions(auth()->user()) as $option)<option value="{{ $option->id }}" data-base-role="{{ $option->base_role }}" data-branch-id="{{ $option->branch_id ?? 0 }}" @selected((int) old('access_role_id', $user?->access_role_id) === $option->id)>{{ $option->name }} ({{ $option->base_role }})</option>@endforeach
</select><p class="form-help">Pilih role yang sesuai peran dasar dan cabang penempatan.</p></div>
@once
@push('scripts')
<script>
document.querySelectorAll('select[name="access_role_id"]').forEach(profile => {
    const base = profile.form.querySelector('select[name="role"]');
    const branch = profile.form.querySelector('select[name="branch_id"]');
    const refresh = () => {
        [...profile.options].forEach(option => {
            if (!option.value) return;
            option.hidden = option.dataset.baseRole !== base.value || (branch && option.dataset.branchId !== '0' && option.dataset.branchId !== branch.value);
        });
        if (profile.selectedOptions[0]?.hidden) profile.value = '';
    };
    profile.addEventListener('change', () => {
        const option = profile.selectedOptions[0];
        if (option?.dataset.baseRole) { base.value = option.dataset.baseRole; base.dispatchEvent(new Event('change')); }
    });
    base.addEventListener('change', refresh);
    branch?.addEventListener('change', refresh);
    refresh();
});
</script>
@endpush
@endonce
