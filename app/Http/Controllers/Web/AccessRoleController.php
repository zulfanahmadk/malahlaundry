<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AccessRole;
use App\Models\User;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccessRoleController extends Controller
{
    public function index(Request $request)
    {
        $defaults = collect(['admin', 'owner', 'cashier'])->mapWithKeys(fn ($base) => [$base => Access::defaults($base)]);
        $permissions = $request->user()->isAdmin() && ! $request->user()->access_role_id
            ? $defaults->flatten()->unique()->values()->all() : Access::permissions($request->user());
        return view('roles.index', ['roles' => Access::roleOptions($request->user()), 'features' => Access::features(),
            'roleDefaults' => $defaults, 'actorPermissions' => $permissions]);
    }

    public function save(Request $request, ?AccessRole $accessRole = null)
    {
        $actor = $request->user();
        if ($accessRole) {
            abort_unless($actor->isAdmin() || ((int) $accessRole->branch_id === (int) $request->attributes->get('branch_id') && $accessRole->base_role !== 'admin'), 403);
            abort_if(User::where('access_role_id', $accessRole->id)->whereKey($actor->id)->exists(), 422, 'Role yang sedang Anda gunakan tidak dapat diubah sendiri.');
        }
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'base_role' => ['required', Rule::in($actor->isAdmin() ? ['admin', 'owner', 'cashier'] : ['owner', 'cashier'])],
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', 'distinct', Rule::in(Access::defaults($request->input('base_role', 'cashier')))],
        ]);
        $data['permissions'] = $data['permissions'] ?? [];
        if (! in_array('dashboard.view', $data['permissions'], true) || ! in_array('profile.view', $data['permissions'], true)) {
            return back()->withErrors(['permissions' => 'Role harus mengizinkan Beranda dan Profil agar akun tetap dapat digunakan.'])->withInput();
        }
        abort_if((! $actor->isAdmin() || $actor->access_role_id) && array_diff($data['permissions'], Access::permissions($actor)), 403, 'Tidak dapat memberikan akses melebihi akun Anda.');
        foreach ($data['permissions'] as $permission) {
            [$feature, $action] = explode('.', $permission);
            if ($action !== 'view' && ! in_array("$feature.view", $data['permissions'], true)) {
                return back()->withErrors(['permissions' => 'Akses ubah atau ekspor juga memerlukan akses lihat.'])->withInput();
            }
        }
        $data['branch_id'] = $actor->isAdmin() ? $accessRole?->branch_id : $request->attributes->get('branch_id');
        DB::transaction(function () use ($accessRole, $data) {
            if ($accessRole && $accessRole->base_role !== $data['base_role']) {
                abort_if(User::where('access_role_id', $accessRole->id)->exists(), 422, 'Peran dasar role yang sedang digunakan tidak dapat diubah.');
            }
            ($accessRole ?? new AccessRole())->fill($data)->save();
        });
        return back()->with('success', 'Role dan akses berhasil disimpan.');
    }

    public function delete(Request $request, AccessRole $accessRole)
    {
        abort_unless($request->user()->isAdmin() || ((int) $accessRole->branch_id === (int) $request->attributes->get('branch_id') && $accessRole->base_role !== 'admin'), 403);
        abort_if(User::where('access_role_id', $accessRole->id)->exists(), 422, 'Role masih dipakai pengguna. Ganti role pengguna sebelum menghapus.');
        $accessRole->delete();
        return back()->with('success', 'Role dihapus.');
    }
}
