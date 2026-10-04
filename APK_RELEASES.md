# Admin sistem dan pembaruan Android

Android versi 1.7.0 (versionCode 10) menggunakan server produksi
`https://laundry.malahproject.com` pada formulir login tanpa kolom URL.
Server tersimpan dari instalasi lama dipertahankan untuk melindungi antrean offline;
sinkronkan data pada server lama sebelum pindah akun/server. Alamat server khusus
hanya tersedia dalam pemanggilan repository untuk fixture pengujian, bukan UI.

Role `admin` mengelola APK melalui `/admin/apk`. Role ini terpisah dari owner dan
kasir, tidak dapat login Android, serta tidak dapat diedit oleh owner. Login web
admin membuka ringkasan sistem `/admin`: jumlah pengguna toko (owner/kasir),
cabang, layanan, dan rilis APK, termasuk jumlah aktif/nonaktif. Ringkasan hanya
berisi angka agregat seluruh sistem tanpa identitas pengguna, rincian cabang,
pelanggan, transaksi, atau laporan keuangan. Admin tidak dapat memilih atau
masuk ke cabang owner melalui web maupun API, termasuk dengan token lama.
Admin dan owner menggunakan layout dashboard yang sama: sidebar, topbar, menu
responsif, kartu, tabel, dan formulir. Menu mengikuti peran. Admin berisi
Ringkasan, Pengguna, Cabang, Versi APK, dan Log Audit.

Admin mengelola semua akun di `/admin/users`: tambah, edit nama/username/peran,
password baru, penempatan cabang, dan status aktif/nonaktif. Semua peran
(admin/owner/kasir) dapat dikelola, tetapi admin tidak dapat menonaktifkan atau
menurunkan peran akun sendiri. Penempatan dan akses saat ini ditampilkan:
owner dapat memilih seluruh cabang; kasir hanya cabang penempatan; admin tidak
memiliki akses operasional cabang. Filter cabang memeriksa penempatan, bukan
mengubah izin akses owner. `/admin/branches` menampilkan daftar cabang dan jumlah
owner/kasir yang ditempatkan, dengan tautan ke pengguna pada penempatan itu.
Tidak ada perubahan aturan akses owner atau operasional toko.

Perubahan username/password/peran/cabang atau penonaktifan mencabut token
Android, cookie remember lama, dan sesi browser database pengguna yang terdampak.
Sesi admin sendiri tetap aktif setelah pengubahan akunnya. Aktivitas perubahan
tercatat pada audit dan log fitur pengguna. Owner tetap tidak dapat membuat
atau mengubah akun admin melalui menu/API owner.

Log Audit `/admin/audit` hanya dapat dibaca admin aktif. Catatan dimulai setelah
migrasi `2026_10_04_000003_create_audit_logs` dipasang, tanpa mengimpor log teks
lama. Filter tersedia untuk tanggal WIB, fitur, hasil, peran, dan ID pengguna;
halaman memuat 30 catatan. Tidak ada fitur ubah/hapus audit. Lihat `LOGGING.md`
untuk cakupan pencatatan dan pemisahan dari log diagnostik.

Owner dapat membuka menu Pengaturan → APK Android (`/apk`) untuk mengecek versi
terbaru aplikasi utama, catatan rilis, tanggal penerbitan, dan ukuran APK, lalu
mengunduh melalui `/apk/{id}/download`. Menu owner selalu menampilkan APK utama;
rilis QA tidak ditawarkan untuk perangkat kasir. Owner tidak dapat mengunggah
atau mengubah rilis. Tombol Cek versi terbaru memuat kembali metadata dari server.

Untuk menyiapkan server setelah kode di-deploy:

```sh
php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force
php artisan config:cache
php artisan queue:restart
```

AdminSeeder juga dijalankan oleh DatabaseSeeder untuk instalasi baru. Pada server
yang sudah berisi data, jalankan AdminSeeder saja; jangan menjalankan seeder akun
demo/pengguna lainnya. Username awal `admin`, dapat diubah lewat `ADMIN_USERNAME`.
Password awal `password123`, dapat diubah lewat `ADMIN_INITIAL_PASSWORD`; seeder
tidak mengganti password akun admin yang sudah ada. Kredensial awal tersimpan di
`storage/app/private/admin-initial-password.txt`. Ganti password melalui halaman
admin; file kredensial awal dihapus setelah perubahan berhasil. Jangan commit
file ini maupun file APK ke Git.

Upload menerima APK sampai 100 MiB. Server memerlukan ekstensi PHP zip dan
mbstring. Sesuaikan `upload_max_filesize`, `post_max_size`, serta batas body
Nginx/proxy agar sesuai ukuran file, termasuk overhead multipart. Batas proxy
seperti Cloudflare dapat lebih rendah daripada batas aplikasi.

Web membaca package, versionName, dan versionCode langsung dari binary manifest
APK. APK utama `com.malahlaundry.app` dan QA `com.malahlaundry.app.qa` diterbitkan
terpisah. Version code baru harus lebih besar dari rilis sebelumnya untuk package
yang sama. APK tersimpan privat; endpoint unduh hanya menyajikan file, bukan
menjalankannya. Riwayat rilis lama dipertahankan. Tidak ada pengiriman ke Play Store.

Endpoint publik Android:

- `GET /api/v1/app-release?package=com.malahlaundry.app`: metadata versi terbaru;
  `release: null` bila belum ada APK.
- `GET /api/v1/app-releases/{id}/download`: file APK.

Android memeriksa versi saat aplikasi dibuka/kembali aktif (maksimal sekali tiap
5 menit), dan menyediakan tombol Cek pembaruan. Jika versionCode lebih besar,
dialog menampilkan catatan rilis dan ukuran unduhan. Pengguna dapat memilih Nanti.
Unduhan memakai HTTPS dari server tetap, lalu memeriksa ukuran, SHA-256, package,
versionCode, dan kompatibilitas sertifikat dengan aplikasi yang terpasang.
Pemasang Android memverifikasi tanda tangan dan meminta konfirmasi pengguna.
Izin pemasangan dari aplikasi ini dapat diminta sekali lewat pengaturan Android.
Tidak ada uninstall, reset database, atau penghapusan antrean sinkronisasi.

Pakai sertifikat/keystore yang sama untuk setiap pembaruan. QA tidak menggantikan
aplikasi utama. Jangan mengunggah APK release tanpa tanda tangan. APK yang dibuat
di mesin berbeda dengan debug keystore berbeda tidak dapat memperbarui instalasi
yang sudah ada; gunakan kunci penandatanganan distribusi yang konsisten.

Instalasi lama yang belum mempunyai pemeriksa pembaruan harus memasang versi
1.7.0 ini secara manual sekali. Setelah itu aplikasi dapat menemukan rilis baru
dari web. Koneksi gagal atau pembaruan ditunda tidak menghalangi penggunaan POS
offline. Verifikasi pemasangan pada HP dilakukan setelah endpoint produksi tersedia.
