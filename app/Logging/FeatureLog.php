<?php

namespace App\Logging;

use Illuminate\Http\Request;

class FeatureLog
{
    public const ROUTES = [
        'admin' => 'versi-apk', 'apk' => 'versi-apk', 'app-release' => 'versi-apk', 'app-releases' => 'versi-apk',
        'login' => 'autentikasi', 'logout' => 'autentikasi', 'auth' => 'autentikasi',
        'sync' => 'sinkronisasi', 'synchronization' => 'sinkronisasi',
        'transactions' => 'transaksi', 'customers' => 'pelanggan', 'services' => 'layanan',
        'users' => 'pengguna', 'attendances' => 'presensi', 'reports' => 'laporan',
        'n' => 'nota', 'nota' => 'nota', 'store' => 'pengaturan-toko', 'settings' => 'pengaturan-toko',
        'branches' => 'cabang', 'hours' => 'jam-buka', 'opening-hours' => 'jam-buka',
        'templates' => 'template-whatsapp', 'message-templates' => 'template-whatsapp',
        'profile' => 'profil', 'notifications' => 'notifikasi', 'dashboard' => 'dashboard',
    ];

    public static function features(): array
    {
        return array_values(array_unique([...array_values(self::ROUTES), 'aplikasi']));
    }

    public static function request(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }
        $request = request();
        return ! app()->runningInConsole() || $request->attributes->has('log_request_id') ? $request : null;
    }

    public static function resolve(?Request $request): string
    {
        if (! $request) {
            return 'aplikasi';
        }

        $uri = $request->route()?->uri() ?? $request->path();
        $uri = preg_replace('#^api/v1/#', '', $uri);
        if ($uri === 'auth/profile') {
            return 'profil';
        }

        $prefix = explode('.', $request->route()?->getName() ?? '')[0];
        return self::ROUTES[$prefix] ?? self::ROUTES[explode('/', $uri)[0]] ?? 'aplikasi';
    }
}
