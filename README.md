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

Buka http://127.0.0.1:8000/login. Document root Apache harus diarahkan ke `web/public`. Dashboard menggunakan Blade, `public/css/workspace.css` dan `public/js/workspace.js`; tidak membutuhkan Vite untuk dijalankan. Pemetaan 17 halaman dan rincian pengaturan tersedia di [dokumentasi Figma web](docs/FIGMA_WEB_IMPLEMENTATION.md). Jangan menimpa konfigurasi/database yang sudah digunakan.

Untuk database pengembangan/demo saja:

```powershell
php artisan db:seed
```

Seeder membuat akun `owner` dan `kasir1` dengan password demo `password123`, layanan contoh, template WhatsApp, serta pengaturan toko. Seeder tidak membuat pelanggan atau transaksi contoh. Login web hanya menerima owner; kasir menggunakan API. Pengisian ulang mempertahankan UUID, harga, password dan record yang sudah ada. Jangan gunakan kredensial demo pada deployment nyata.

Nota tersedia setelah transaksi dibuat melalui aplikasi dan disinkronkan, pada `/n/<uuid-transaksi>`. Transaksi memakai UUIDv4 acak.

## Alur yang tersedia

- Dashboard owner: statistik, transaksi, dan absensi.
- Tambah/edit/aktif-nonaktif layanan; tambah/edit pengguna, ubah username/password/peran, dan nonaktifkan akun.
- Riwayat transaksi dengan pencarian, status, pembayaran, tanggal, dan pagination.
- Ekspor hasil filter sebagai Excel OOXML (.xlsx). Ekspor memerlukan sesi browser owner, token API saja tidak cukup.
- API login/profil/push/pull/riwayat seluruh kasir/unggah foto dan nota publik. Header respons nota mencegah cache/pengindeksan; nomor HP disamarkan.
- Absensi memiliki filter nama, pengguna, status shift, dan tanggal; foto diakses melalui sesi owner. Status cucian: DITERIMA → SIAP_DIAMBIL → SELESAI, dengan tanggal pengambilan.

## Pemeriksaan

```powershell
php artisan test
php vendor/bin/pint --test app/Http/Controllers app/Http/Middleware app/Http/Requests app/Exports app/Models/Transaction.php app/Services/QrCodeService.php bootstrap/app.php routes database/factories/UserFactory.php database/seeders/DatabaseSeeder.php tests/Feature
```

Pint memakai preset PSR-12 dari `pint.json`. Tes menggunakan `RefreshDatabase` dengan SQLite `:memory:` pada `phpunit.xml`, bukan database pada .env. GD diperlukan untuk tes decode QR dan pembuatan gambar uji.

API lokal berada di `/api/v1`, bukan `/v1`. Untuk perangkat Android nyata gunakan alamat server yang dapat dijangkau perangkat; konfigurasi jaringan/Cloudflare Tunnel belum disertakan.

## Pengaturan toko

Menu **Pengaturan Toko & Nota** khusus owner mengatur nama, kontak, alamat, logo, tiga format WhatsApp, dan syarat nota digital. Jalankan migrasi 000008 sebelum memakai versi 1.2; tabel baru store_settings menyimpan identitas, sementara template memakai whatsapp_templates. PHP GD diperlukan untuk validasi dan normalisasi logo; ZIP diperlukan untuk laporan Excel. Logo dilayani melalui /store/logo tanpa symlink. Tombol Simpan sebagai PDF pada nota digital telah dihapus. Monitoring absensi kasir tetap tersedia untuk owner.
Data awal nota yang belum diisi dapat dilengkapi dengan `php artisan db:seed --class=StoreSettingsSeeder`. Seeder ini hanya mengisi nama/kontak/alamat/ketentuan kosong dan logo yang belum ada; nilai owner yang sudah diisi tidak ditimpa. Kontak contoh diberi penanda dan dapat diganti melalui Pengaturan Toko & Nota. Logo awal mengikuti ikon mesin cuci aplikasi; sumber SVG dan PNG ada di resources/images.
