# Database untuk 48 desain Laundry

Migrasi tambahan: `database/migrations/2026_10_03_000001_add_design_operations.php`.

- Tabel `branches`: identitas dan logo cabang, status aktif, jadwal mingguan, template, syarat nota, batas komplain, koordinat/radius presensi.
- Relasi `branch_id` pada users, customers, services, transactions, attendances. Data lama ditempatkan di cabang 1 (Utama).
- Pelanggan: catatan dan waktu arsip. Layanan: reguler/express dan durasi jam.
- Transaksi: estimasi selesai, siap diambil, waktu pelunasan, metode pembayaran, dan versi server.
- Presensi: koordinat/akurasi masuk dan pulang.

Pada 4 Oktober 2026 migrasi sudah diterapkan ke MySQL lokal `malahlaundry`. Backup sebelumnya disimpan di `storage/app/private/backups/before-figma-20261004-001901.sql`. Jumlah data utama sebelum dan sesudah tetap: 2 pengguna, 4 layanan, 0 pelanggan/transaksi/item/presensi. Cabang Utama dibuat dari pengaturan toko lama. Backup merupakan berkas privat yang diabaikan Git.

Untuk lingkungan lain, backup database lalu jalankan:

```shell
php artisan migrate --force
```

Jangan menggunakan `migrate:fresh` atau reset tabel. Tidak ada perubahan database produksi dalam pekerjaan ini.

API baru: `GET /api/v1/branches`, `POST /api/v1/branches`, `POST /api/v1/branches/{id}`. Header `X-Branch-Id` memilih cabang bagi owner; kasir hanya dapat mengakses cabang penempatannya. Data sinkronisasi diikat ke cabang pada server. Nota publik mengambil identitas dari cabang transaksi. `logo_base64` menerima JPG/PNG/WebP maksimal 1 MB, dinormalisasi menjadi PNG berukuran maksimal 384 piksel.

WhatsApp nota dan reminder dibuka melalui aplikasi WhatsApp, lalu **dikirim manual oleh kasir/user**. Tidak memerlukan gateway, token WhatsApp, cron, atau scheduler pengiriman. Tidak ada pesan WhatsApp dikirim selama implementasi. Tabel percobaan gateway yang sempat dibuat saat pengerjaan telah dihapus setelah dipastikan kosong; migrasi final tidak memuat integrasi tersebut.

Testing dan build tidak dijalankan sesuai instruksi pengguna.

## Tambahan workspace web (4 Oktober 2026)

Migrasi `2026_10_04_000001_add_owner_workspace.php` sudah diterapkan secara lokal setelah backup `storage/app/private/backups/before-owner-workspace-20261004-090836.sql`. Menambahkan preferensi nota/pesan, jam khusus, preferensi operasional cabang, telepon/login/preferensi notifikasi pengguna, laporan perangkat dan status baca notifikasi. Satuan layanan mendukung `m2`. Data utama tetap utuh; tidak ada reset. Rincian 17 desain dan integrasi ada di [FIGMA_WEB_IMPLEMENTATION.md](FIGMA_WEB_IMPLEMENTATION.md).
