# Log per fitur

Log aplikasi memakai nama fitur dan tanggal di `storage/logs/`, misalnya
`transaksi-2026-10-04.log`, `sinkronisasi-2026-10-04.log`,
`nota-2026-10-04.log`, atau `template-whatsapp-2026-10-04.log`.
File dibuat saat fitur menulis log, bukan semuanya sekaligus.

Fitur yang tersedia: autentikasi, sinkronisasi, transaksi, pelanggan, layanan,
pengguna, presensi, laporan, nota, pengaturan-toko, cabang, jam-buka,
template-whatsapp, profil, notifikasi, dashboard, admin-sistem, versi-apk, dan aplikasi.
Web dan API dengan fitur yang sama masuk ke file yang sama.
Sinkronisasi batch masuk ke sinkronisasi; rincian transaksi dalam batch tidak
dipecah menjadi log terpisah. CLI dan proses tanpa konteks fitur masuk ke aplikasi.

Konfigurasi `LOG_CHANNEL=stack` dengan `LOG_STACK=single` tetap berlaku.
Channel `single` dan `daily` sekarang mengarahkan pesan ke file fitur dengan
rotasi harian. `LOG_LEVEL` mengatur tingkat minimum. `LOG_DAILY_DAYS=14`
membatasi jumlah file harian yang disimpan per fitur. Pembersihan dilakukan
oleh Monolog saat rotasi pada fitur tersebut, bukan oleh event database.
Log lama `laravel.log` dipertahankan dan masih menjadi fallback emergency jika
konfigurasi logger gagal. Channel eksternal seperti stderr/syslog tetap mengikuti
konfigurasi semula.

Request perubahan, ekspor, dan respons HTTP 4xx/5xx mencatat status, durasi,
ID pengguna/cabang, pola route, dan ID permintaan. Respons menyertakan header
`X-Request-ID` untuk mencocokkan laporan masalah dengan log. Body request,
query string, header otorisasi, dan parameter route pelanggan tidak dicatat
oleh middleware. Exception tetap memuat pesan dan stack trace standar Laravel;
hindari memasukkan data rahasia pada pesan exception/log manual.

Penulisan manual dari controller, service, atau job:

```php
Log::channel('transaksi')->info('Status cucian diperbarui', [
    'transaction_id' => $transaction->id,
]);
Log::channel('sinkronisasi')->error('Sinkronisasi gagal', [
    'device_id' => $deviceId,
]);
```

`Log::info(...)` dan pelaporan exception otomatis memilih fitur dari route
aktif. Logger yang sama dapat dipakai untuk beberapa request tanpa menyimpan
nama fitur request sebelumnya. Channel eksplisit memilih fitur tetap sehingga
job antrean juga dapat menulis ke fitur yang sesuai.

Setelah deployment, jalankan `php artisan config:cache` dan restart worker
antrean yang berjalan. Tidak ada migrasi database untuk perubahan ini.
