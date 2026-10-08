<?php

namespace App\Support;

use App\Models\AccessRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class Access
{
    public static function features(): array
    {
        return [
            'dashboard' => 'Beranda', 'transactions' => 'Cucian / transaksi',
            'customers' => 'Pelanggan', 'reports' => 'Laporan', 'services' => 'Layanan',
            'branches' => 'Cabang', 'users' => 'Pengguna', 'attendances' => 'Presensi',
            'settings' => 'Toko & nota', 'hours' => 'Jam buka', 'templates' => 'Template pesan',
            'profile' => 'Profil akun', 'sync' => 'Sinkronisasi', 'notifications' => 'Notifikasi',
            'apk' => 'APK Android', 'tickets' => 'Tiket bantuan', 'roles' => 'Role & akses',
            'audit' => 'Log audit',
        ];
    }

    public static function actions(): array
    {
        return [
            'dashboard' => ['view' => 'Buka beranda'],
            'transactions' => ['view' => 'Lihat cucian', 'write' => 'Buat / ubah cucian (Android)'],
            'customers' => ['view' => 'Lihat pelanggan', 'write' => 'Kelola pelanggan (Android)', 'export' => 'Unduh data pelanggan'],
            'reports' => ['view' => 'Lihat laporan & omzet', 'export' => 'Unduh laporan Excel'],
            'services' => ['view' => 'Lihat layanan', 'write' => 'Tambah / ubah layanan'],
            'branches' => ['view' => 'Lihat / pilih cabang', 'write' => 'Tambah / ubah cabang'],
            'users' => ['view' => 'Lihat pengguna', 'write' => 'Tambah / ubah pengguna & akses'],
            'attendances' => ['view' => 'Lihat presensi', 'write' => 'Catat presensi (Android)', 'export' => 'Unduh data presensi'],
            'settings' => ['view' => 'Lihat pengaturan toko', 'write' => 'Ubah toko & nota'],
            'hours' => ['view' => 'Lihat jam buka', 'write' => 'Ubah jam buka'],
            'templates' => ['view' => 'Lihat template', 'write' => 'Ubah template pesan'],
            'profile' => ['view' => 'Buka profil', 'write' => 'Ubah profil / password'],
            'sync' => ['view' => 'Lihat status sinkronisasi'],
            'notifications' => ['view' => 'Lihat notifikasi', 'write' => 'Tandai sudah dibaca'],
            'apk' => ['view' => 'Lihat / unduh APK', 'write' => 'Unggah APK (admin)'],
            'tickets' => ['view' => 'Lihat tiket bantuan', 'write' => 'Buat / balas tiket'],
            'roles' => ['view' => 'Lihat role', 'write' => 'Buat / ubah / hapus role'],
            'audit' => ['view' => 'Lihat log audit'],
        ];
    }

    public static function defaults(string $base): array
    {
        $features = match ($base) {
            'admin' => ['dashboard', 'users', 'branches', 'apk', 'tickets', 'profile', 'notifications', 'audit', 'roles'],
            'cashier' => ['dashboard', 'transactions', 'customers', 'attendances', 'profile', 'sync', 'notifications', 'apk'],
            default => array_diff(array_keys(self::features()), ['audit']),
        };
        $permissions = [];
        foreach ($features as $feature) {
            foreach (array_keys(self::actions()[$feature]) as $action) {
                if ($feature === 'apk' && $action === 'write' && $base !== 'admin') {
                    continue;
                }
                $permissions[] = "$feature.$action";
            }
        }
        return $permissions;
    }

    public static function allowed(User $user, string $feature, string $action = 'view'): bool
    {
        $permission = "$feature.$action";
        if (! in_array($permission, self::defaults($user->role), true)) {
            return false;
        }
        if ($user->menu_permissions !== null && ! in_array($permission, $user->menu_permissions, true)) {
            return false;
        }
        if (! $user->access_role_id) {
            return true;
        }
        $role = $user->accessRole;
        return $role && $role->base_role === $user->role
            && ($role->branch_id === null || (int) $role->branch_id === (int) $user->branch_id)
            && in_array($permission, $role->permissions ?? [], true);
    }

    public static function permissions(User $user): array
    {
        return array_values(array_filter(self::defaults($user->role), function ($permission) use ($user) {
            [$feature, $action] = explode('.', $permission);
            return self::allowed($user, $feature, $action);
        }));
    }

    public static function routeFeature(?string $name): ?string
    {
        $name = preg_replace('/^admin\./', '', $name ?? '');
        $feature = explode('.', $name)[0];
        if ($feature === 'password') {
            return 'profile';
        }
        return array_key_exists($feature, self::features()) ? $feature : null;
    }

    public static function branchFeature(array $keys): string
    {
        $keys = array_diff($keys, ['id', '_token']);
        foreach ([
            'hours' => ['opening_hours'],
            'templates' => ['templates', 'message_preferences'],
            'settings' => ['name', 'store_name', 'phone', 'address', 'show_branch', 'logo_base64', 'receipt_terms', 'complaint_days'],
        ] as $feature => $fields) {
            if ($keys && ! array_diff($keys, $fields)) {
                return $feature;
            }
        }
        return 'branches';
    }

    public static function roleOptions(User $actor)
    {
        return AccessRole::query()->when(! $actor->isAdmin(), fn ($q) => $q
            ->where('base_role', '!=', 'admin')
            ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', request()->attributes->get('branch_id', $actor->branch_id))))
            ->orderBy('name')->get();
    }

    public static function validateAssignment(Request $request, array &$data, ?User $target = null): void
    {
        if ($scope = $request->user()->accessRole?->branch_id) {
            abort_if($target && (int) $target->branch_id !== (int) $scope, 403, 'Pengguna di luar akses cabang role.');
            if ((int) ($data['branch_id'] ?? $target?->branch_id) !== (int) $scope) {
                throw ValidationException::withMessages(['branch_id' => 'Role Anda hanya dapat mengelola pengguna cabang penempatan.']);
            }
        }
        $data['access_role_id'] = $request->exists('access_role_id') ? ($request->input('access_role_id') ?: null) : $target?->access_role_id;
        if ($target?->id === $request->user()->id && (int) $data['access_role_id'] !== (int) $target->access_role_id) {
            throw ValidationException::withMessages(['access_role_id' => 'Role akun sendiri tidak dapat diganti. Gunakan admin lain.']);
        }
        self::validateUserPermissions($request, $data, $target);
        $limitedActor = ! $request->user()->isAdmin() || $request->user()->access_role_id || $request->user()->menu_permissions !== null;
        if (! $data['access_role_id']) {
            if ($limitedActor && array_diff($data['menu_permissions'] ?? self::defaults($data['role']), self::permissions($request->user()))) {
                throw ValidationException::withMessages(['access_role_id' => 'Pilih role dengan akses yang tidak melebihi akun Anda.']);
            }
            return;
        }
        $role = self::roleOptions($request->user())->firstWhere('id', (int) $data['access_role_id']);
        if (! $role || $role->base_role !== $data['role'] || ($role->branch_id && (int) $role->branch_id !== (int) ($data['branch_id'] ?? $target?->branch_id))) {
            throw ValidationException::withMessages(['access_role_id' => 'Role tidak sesuai peran dasar atau cabang pengguna.']);
        }
        if ($data['menu_permissions'] !== null && array_diff($data['menu_permissions'], $role->permissions)) {
            throw ValidationException::withMessages(['menu_permissions' => 'Akses pengguna harus sesuai batas role yang dipilih.']);
        }
        if ($limitedActor && array_diff(array_intersect(self::defaults($data['role']), $role->permissions, $data['menu_permissions'] ?? $role->permissions), self::permissions($request->user()))) {
            throw ValidationException::withMessages(['access_role_id' => 'Tidak dapat memberikan akses melebihi akun Anda.']);
        }
    }

    private static function validateUserPermissions(Request $request, array &$data, ?User $target): void
    {
        $data['menu_permissions'] = $target?->menu_permissions;
        if (! $request->exists('access_mode')) {
            return;
        }
        $validated = $request->validate([
            'access_mode' => 'required|in:role,custom',
            'menu_permissions' => 'nullable|array',
            'menu_permissions.*' => ['string', 'distinct', \Illuminate\Validation\Rule::in(self::defaults($data['role']))],
        ]);
        $permissions = $validated['access_mode'] === 'custom' ? ($validated['menu_permissions'] ?? []) : null;
        if ($target?->id === $request->user()->id && $permissions !== $target->menu_permissions) {
            throw ValidationException::withMessages(['menu_permissions' => 'Akses akun sendiri tidak dapat diubah. Gunakan akun pengelola lain.']);
        }
        if ($permissions !== null) {
            foreach (['dashboard.view', 'profile.view'] as $required) {
                if (! in_array($required, $permissions, true)) {
                    throw ValidationException::withMessages(['menu_permissions' => 'Beranda dan Profil harus tetap dapat diakses.']);
                }
            }
            foreach ($permissions as $permission) {
                [$feature, $action] = explode('.', $permission);
                if ($action !== 'view' && ! in_array("$feature.view", $permissions, true)) {
                    throw ValidationException::withMessages(['menu_permissions' => 'Aktifkan akses lihat menu sebelum memilih tindakan lainnya.']);
                }
            }
        }
        $data['menu_permissions'] = $permissions;
    }
}
