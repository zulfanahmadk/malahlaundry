# Deployment produksi

Aktif sejak 29 September 2026.

- Dashboard dan API: `https://laundry.malahproject.com` (`/api/v1` untuk API).
- Nota: `https://nota.malahproject.com/n/{uuid}`. Root menampilkan petunjuk; login/dashboard/API pada domain nota ditolak.
- Server: `malahsite`, aplikasi `/var/www/malahlaundry`, Nginx, PHP-FPM 8.4.
- Nginx: `/etc/nginx/sites-available/malahlaundry`, document root `/var/www/malahlaundry/public`.
- Cloudflare Tunnel yang sudah ada tetap digunakan. Aturan wildcard `*.malahproject.com` saat deployment mendahului aturan laundry dan mengarah ke port 8080. Virtual host laundry/nota mendengarkan port 8080 dan 8082 agar keduanya didukung. Konfigurasi dashboard Cloudflare tidak diubah.
- `.env` server: `APP_URL=https://laundry.malahproject.com`, `NOTA_URL=https://nota.malahproject.com`, cookie khusus `malah_laundry_session`, HTTPS, debug mati.
- Backup sebelum perubahan: `/root/laundry-backup-20260929-133958` (aplikasi, database SQL, konfigurasi Nginx dan tunnel).
- Migrasi 000007 dan 000008 sudah diterapkan. Akun dan transaksi lama dipertahankan.
- Android 1.2.3 mengambil alamat nota dari konfigurasi sinkronisasi dan menyimpannya untuk penggunaan offline. APK lama tetap didukung melalui redirect tautan nota lama.

Untuk pembaruan selanjutnya, backup lebih dahulu; pertahankan `.env`, APP_KEY, database dan storage. Jalankan Composer install dari lockfile, migrasi, lalu `php artisan optimize`. Jangan gunakan `migrate:fresh` atau seeder akun demo di produksi.
