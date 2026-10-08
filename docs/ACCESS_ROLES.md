# Role dan riwayat login

Admin dan owner memiliki menu **Role & akses**. Pilih peran dasar, lalu centang menu dan tindakan yang tersedia. Setiap menu hanya menampilkan tindakan yang benar-benar ada: Beranda dan Log audit hanya untuk dilihat; unduh data tersedia pada Pelanggan, Laporan, dan Presensi; unggah APK hanya untuk admin. Tindakan khusus Android diberi keterangan. Beranda dan Profil wajib dapat dilihat.

Pada formulir tambah/edit pengguna, pilih **Ikuti akses role / peran** atau **Atur khusus untuk pengguna ini**. Pilihan khusus menampilkan daftar menu dan tindakan langsung di formulir pengguna. Akses khusus hanya berlaku untuk akun tersebut, dibatasi oleh role/peran dasar, dan tidak mengubah akses akun lain. Kembali ke pilihan ikuti role untuk menghapus batas khusus. Perubahan akses mencabut sesi/token perangkat lain. Akun tidak dapat mengubah aksesnya sendiri.

- Akun tanpa role buatan mempertahankan akses bawaan.
- Admin dapat membuat role bersama. Owner membuat role untuk cabang aktif, tanpa hak admin.
- Role buatan owner membatasi akun pada cabang penempatannya.
- Akun terbatas tidak dapat memberi akses melebihi haknya sendiri.
- Role yang dipakai akun sendiri tidak dapat diedit sendiri. Role yang masih dipakai pengguna tidak dapat dihapus.
- Pembatasan diperiksa di web dan API. Android 1.7.4 menyimpan izin untuk tampilan menu dan tindakan offline, serta menyegarkannya saat sinkronisasi. Perubahan role pengguna mencabut sesi/token perangkat lain; antrean lokal tetap dipertahankan.

Admin melihat IP, perangkat, dan riwayat login dari menu Pengguna. Pencatatan dimulai pada login setelah pembaruan server. Lokasi bersifat opsional, memerlukan izin perangkat/browser, dan hanya dapat dilaporkan untuk login milik akun tersebut dalam 15 menit pertama. Tidak ada pelacakan lokasi latar belakang. Informasi perangkat/lokasi yang dilaporkan bukan bukti keamanan untuk mengautentikasi pengguna.

Password lama tetap berupa hash dan tidak dapat ditampilkan kembali. Admin dapat menetapkan password baru dari formulir edit pengguna. Pilihan “Tampilkan password baru” hanya memperlihatkan teks yang sedang diketik.

## Penerapan

Cadangkan database sebelum menerapkan. Pada `/var/www/malahlaundry`, gunakan branch `qa-master`:

```sh
php artisan down --retry=60
git pull --ff-only origin qa-master
php artisan migrate --force
php artisan optimize:clear
php artisan up
```

Jalankan Artisan menggunakan akun layanan yang memiliki akses storage (server ini memakai `www-data`). Jika migrasi gagal, perbaiki penyebabnya sebelum mengaktifkan aplikasi kembali. Migrasi hanya menambahkan tabel `access_roles`, `user_logins`, dan kolom `users.access_role_id` serta `users.menu_permissions`; tidak mengubah password atau data transaksi lama. Gunakan `migrate --force`, bukan `migrate:fresh`, untuk pembaruan server.

Untuk lokasi login browser, kebijakan Nginx/Cloudflare pada domain laundry harus mengizinkan `geolocation=(self)`. Kebijakan `geolocation=()` akan menolak lokasi browser meskipun pengguna ingin mengizinkannya. Pengaturan ini tidak memengaruhi pelaporan GPS dari aplikasi Android.

Android disiapkan sebagai **1.7.4 / version code 14**. Tanda tangani release menggunakan keystore yang sama dengan APK terpasang. Jangan menerbitkan APK unsigned dan jangan mengganti keystore ketika membuat pembaruan.

## Pemeriksaan

Pengujian web memakai database SQLite terisolasi:

```sh
php vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Feature/AccessRolesTest.php
```

Pengujian Android: `:app:testQaUnitTest`, `:app:lintQa`, `:app:ktlintCheck`; build release: `:app:assembleRelease`.
