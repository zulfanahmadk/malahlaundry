<?php

namespace App\Support;

class AuditActivity
{
    public static function label(string $action, string $method): string
    {
        return match (true) {
            in_array($action, ['login', 'api/v1/auth/login'], true) => 'Masuk akun',
            in_array($action, ['logout', 'api/v1/auth/logout'], true) => 'Keluar akun',
            $action === 'admin/audit' => 'Lihat log audit',
            $action === 'admin/apk' && $method === 'POST' => 'Terbitkan APK',
            $action === 'admin/password' => 'Ganti password admin',
            $action === 'branches/select' => 'Pilih cabang',
            str_ends_with($action, '/export') => 'Ekspor data',
            str_starts_with($action, 'api/v1/sync/') => 'Sinkronisasi data',
            str_ends_with($action, '/toggle') => 'Ubah status akun',
            $action === 'profile' || $action === 'api/v1/auth/profile' => 'Ubah profil',
            $action === 'notifications/read' => 'Tandai notifikasi dibaca',
            in_array($method, ['POST', 'PUT', 'PATCH'], true) => 'Simpan perubahan',
            $method === 'DELETE' => 'Hapus data',
            default => 'Akses halaman',
        };
    }
}
