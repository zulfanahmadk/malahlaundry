# Notifikasi Telegram owner

Fitur mengirim dua jenis pesan: transaksi baru yang berhasil tersimpan di server, dan cucian pertama kali berstatus `SELESAI` dengan `picked_up_at`. `SIAP_DIAMBIL` tidak memicu pesan pengambilan. Transaksi yang dibuat dan diambil ketika Android offline dapat menghasilkan kedua pesan saat pertama kali tersinkron.

## Konfigurasi

1. Buat bot di BotFather. Penerima membuka bot, menekan **Start**, dan mengirim pesan. Ambil angka `message.chat.id` melalui `getUpdates`. Untuk grup, tambahkan bot dan pastikan bot memiliki izin mengirim pesan.
2. Isi `TELEGRAM_BOT_TOKEN` di `.env` server. Token tidak dimasukkan ke Git, browser, API konfigurasi Android, payload antrean, atau APK. Jangan membagikan URL `getUpdates` yang berisi token.
3. Jalankan migrasi penambahan tabel (tanpa reset data):

   ```sh
   php artisan migrate --force
   php artisan optimize:clear
   ```

4. Login sebagai owner, buka **Toko & Nota → Atur Telegram**. Isi Chat ID dan simpan. Tekan **Kirim pesan percobaan**. Aktifkan notifikasi dan jenis pesan yang diinginkan.
5. Jalankan worker khusus koneksi database `telegram`:

   ```sh
   php artisan queue:work telegram --queue=telegram --sleep=1 --tries=1 --timeout=30
   ```

6. Jalankan scheduler. Untuk pengembangan lokal gunakan terminal kedua:

   ```sh
   php artisan schedule:work
   ```

   Di production gunakan cron setiap menit, sesuaikan lokasi project:

   ```cron
   * * * * * cd /var/www/malahlaundry && php artisan schedule:run >> /dev/null 2>&1
   ```

   Worker perlu dikelola oleh Supervisor atau systemd agar otomatis hidup kembali. Jalankan worker, scheduler, dan perintah Laravel production sebagai `www-data`. Setelah deployment kode/token baru, jalankan `php artisan queue:restart`.

Pengaturan penerima berlaku per **akun owner dan cabang**. Owner dengan akses role cabang hanya dapat menerima pesan cabangnya. Akun nonaktif, bukan owner, atau tanpa akses membaca transaksi tidak menerima pesan. Beberapa owner yang memakai Chat ID sama hanya menghasilkan satu pesan per transaksi dan jenis event.

## Pergantian penerima / ownership

Transfer ownership bot tidak memilih penerima notifikasi aplikasi. Owner baru menekan Start, lalu Chat ID testing diganti dengan Chat ID owner melalui halaman pengaturan. Pesan tertunda ke penerima lama dibatalkan. Jika token diregenerasi, perbarui `.env`, bersihkan cache konfigurasi, dan restart worker. Pengaturan baru tidak mengirim ulang transaksi lama.

## Pengiriman dan kegagalan

- `SyncController` mencatat outbox setelah item transaksi disimpan, dalam transaksi database yang sama. Rollback seluruh batch juga menghapus outbox. Tidak ada HTTP Telegram di jalur pencatatan transaksi.
- Setelah commit, outbox dimasukkan ke antrean. Jika enqueue gagal, respons sinkronisasi tetap mengakui transaksi dan scheduler mencoba kembali. `telegram:dispatch` juga tersedia untuk pemeriksaan manual.
- Koneksi queue `telegram` harus tetap memakai database aplikasi dan tabel `jobs`. Job dan klaim outbox disimpan secara atomik. Scheduler memulihkan klaim yang job-nya hilang setelah dua menit, tanpa menambahkan job baru selama job lama masih ada.
- Worker memeriksa ulang penerima, status akun, cabang role, dan preferensi. Pesan memakai snapshot data event agar transaksi yang berubah sesudahnya tidak mengubah isi event sebelumnya. Semua waktu ditampilkan dalam WIB.
- Status `sent` hanya dicatat setelah Telegram memberi `ok: true` dan `message_id`. Status ini tidak membuktikan pesan sudah dibaca owner.
- Gangguan jaringan dan kegagalan sementara dicoba hingga lima kali, dengan jeda minimal 60, 120, 300, dan 600 detik. HTTP 429 mengikuti `retry_after` jika lebih panjang. Scheduler berjalan setiap menit sehingga jeda aktual dapat sedikit lebih lama.
- Token invalid, Chat ID invalid, atau bot diblokir langsung berstatus gagal. Riwayat menyediakan **Coba kirim ulang** setelah masalah diperbaiki, selama penerima dan preferensi masih sesuai.
- Pesan percobaan dikirim langsung dengan batas waktu HTTP 12 detik supaya hasil segera terlihat. Kegagalan sementara pesan percobaan masuk mekanisme retry yang sama.
- Replay sync dan job yang berulang dicegah dengan kunci transaksi/event/Chat ID serta pemeriksaan status di worker. Namun Telegram `sendMessage` tidak menyediakan kunci idempotensi: jika pesan diterima Telegram tetapi respons timeout atau worker mati sebelum menyimpan konfirmasi, retry bisa menghasilkan pesan ganda.
- Tombol transaksi menuju halaman owner yang memerlukan login; pilih cabang sesuai pesan bila cabang aktif berbeda. Gunakan `APP_URL` yang dapat diakses owner di HP.

## Pengujian

Pengujian fitur menggunakan SQLite in-memory dan HTTP palsu; tidak mengirim pesan Telegram asli:

```sh
php vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Feature/TelegramNotificationsTest.php tests/Feature/AccessRolesTest.php
```

Referensi resmi: [Telegram sendMessage](https://core.telegram.org/bots/api#sendmessage), [Laravel queue](https://laravel.com/docs/12.x/queues), [Laravel scheduler](https://laravel.com/docs/12.x/scheduling).
