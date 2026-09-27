# Malah Laundry — Web & API

Laravel 12, Sanctum, dan nota publik dengan QR SVG. Kontrak API: [../docs/API.md](../docs/API.md). Cakupan fitur dan backlog: [../docs/PROGRESS.md](../docs/PROGRESS.md).

## Menjalankan lokal

Prasyarat: PHP 8.2+, Composer, ekstensi PDO MySQL/SQLite, mbstring, DOM, fileinfo dan GD (uji decode QR/foto). Pilih database khusus proyek ini; pengujian otomatis menggunakan SQLite dalam memori.

Dari folder `web`:

```powershell
composer install
# Hanya jika .env belum ada:
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

Atur `APP_URL` ke alamat yang dipakai membuka web. Untuk MySQL XAMPP, atur `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`. Default contoh memakai SQLite; buat `database/database.sqlite` jika memakai konfigurasi tersebut. Gunakan `APP_TIMEZONE=Asia/Jakarta` dan `APP_LOCALE=id`.

Jalankan `php artisan key:generate` **hanya jika APP_KEY masih kosong**, lalu:

```powershell
php artisan migrate
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

Buka http://127.0.0.1:8000/login. Document root Apache harus diarahkan ke `web/public`. Tampilan dashboard saat ini menggunakan Blade dan CSS inline; tidak membutuhkan Vite untuk dijalankan. Jangan menimpa konfigurasi/database yang sudah digunakan.

Untuk database pengembangan/demo saja:

```powershell
php artisan db:seed
```

Seeder membuat akun `owner` dan `kasir1` dengan password demo `password123`, layanan contoh, template WhatsApp, serta satu nota. Login web hanya menerima owner; kasir menggunakan API. Pengisian ulang demo mempertahankan UUID, harga, password dan record yang sudah ada. Jangan gunakan kredensial demo pada deployment nyata.

Nota contoh: `/n/e1a2b3c4-d5e6-4f7a-8b9c-0d1e2f3a4b5c`. UUID tersebut hanya untuk data demo; transaksi operasional memakai UUIDv4 acak.

## Alur yang tersedia

- Dashboard owner: statistik, transaksi, dan absensi.
- Tambah/edit/aktif-nonaktif layanan; tambah pengguna dan nonaktifkan akun.
- Riwayat transaksi dengan pencarian, status, pembayaran, tanggal, dan pagination.
- Ekspor hasil filter sebagai CSV UTF-8 yang dapat dibuka di Excel. Ekspor memerlukan sesi browser owner, token API saja tidak cukup.
- API login/push/pull/unggah foto dan nota publik. Header respons nota mencegah cache/pengindeksan; nomor HP disamarkan.
- Belum ada UI POS web, ekspor XLSX/PDF laporan, ataupun pengiriman WhatsApp otomatis dari server.

## Pemeriksaan

```powershell
php artisan test
php vendor/bin/pint --test app/Http/Controllers app/Http/Middleware app/Http/Requests app/Exports app/Models/Transaction.php app/Services/QrCodeService.php bootstrap/app.php routes database/factories/UserFactory.php database/seeders/DatabaseSeeder.php tests/Feature
```

Pint memakai preset PSR-12 dari `pint.json`. Tes menggunakan `RefreshDatabase` dengan SQLite `:memory:` pada `phpunit.xml`, bukan database pada .env. GD diperlukan untuk tes decode QR dan pembuatan gambar uji.

API lokal berada di `/api/v1`, bukan `/v1`. Untuk perangkat Android nyata gunakan alamat server yang dapat dijangkau perangkat; konfigurasi jaringan/Cloudflare Tunnel belum disertakan.
