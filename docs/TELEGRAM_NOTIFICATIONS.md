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
5. Pada production, gunakan job Cronicle **Telegram Notifications** sesuai bagian berikut. Untuk pengembangan lokal, jalankan worker khusus koneksi database `telegram`:

   ```sh
   php artisan queue:work telegram --queue=telegram --sleep=1 --tries=1 --timeout=30
   ```

6. Untuk pengembangan lokal, jalankan scheduler di terminal kedua:

   ```sh
   php artisan schedule:work
   ```

## Production dengan Cronicle

Gunakan Shell Script plugin dengan isi [telegram-notifications.sh](../deploy/cronicle/telegram-notifications.sh). Cronicle memanggil `telegram:dispatch` langsung lalu memproses antrean `telegram`; ini menangani pengiriman baru dan retry tertunda.

- Judul: **Telegram Notifications**.
- Kategori: **MalahLaundry Telegram**, dengan maksimal satu job bersamaan. Kategori tersendiri menjaga job pembersihan log/foto tetap berjalan pada jadwalnya.
- Target: **malahsite** (Primary Group / `maingrp`).
- Jadwal: setiap menit, zona waktu **Asia/Jakarta** (`timing: {}`).
- Plugin: **Shell Script** (`shellplug`).
- Maksimal concurrent job: **1**; timeout **75 detik**.
- Catch up, detached, antrean event Cronicle, dan retry Cronicle: **nonaktif**. Retry pesan diatur oleh outbox Laravel.
- Script dijalankan Cronicle sebagai root, sedangkan seluruh Artisan dijalankan sebagai **www-data** melalui `sudo -n`.

`flock` memakai `storage/framework/telegram-cronicle.lock` agar Run Now dan eksekusi terjadwal tidak membuat worker kedua. Worker keluar saat antrean kosong, setelah batas 35 detik di antara job, atau setelah 100 job. Satu job yang sedang diproses boleh selesai terlebih dahulu. Pemrosesan berlanjut pada menit berikutnya, sehingga latensi antrean dapat mencapai sekitar satu menit. Riwayat Cronicle memperlihatkan hasil command; riwayat Telegram owner memperlihatkan konfirmasi tiap pesan.

Worker yang berakhir pada tiap siklus akan membaca kode dan konfigurasi terbaru pada siklus berikutnya. Setelah pull perubahan kode/token, jalankan migrasi yang diperlukan dan `sudo -u www-data php artisan optimize:clear`. Job Cronicle tidak melakukan `git pull`, migrasi, atau perubahan token secara otomatis. Job ini menangani Telegram; tugas Laravel lain tetap mengikuti jadwal masing-masing.

## Penerima

Pengaturan penerima berlaku per **akun owner dan cabang**. Owner dengan akses role cabang hanya dapat menerima pesan cabangnya. Akun nonaktif, bukan owner, atau tanpa akses membaca transaksi tidak menerima pesan. Beberapa owner yang memakai Chat ID sama hanya menghasilkan satu pesan per transaksi dan jenis event.

## Pergantian penerima / ownership

Transfer ownership bot tidak memilih penerima notifikasi aplikasi. Owner baru menekan Start, lalu Chat ID testing diganti dengan Chat ID owner melalui halaman pengaturan. Pesan tertunda ke penerima lama dibatalkan. Jika token diregenerasi, perbarui `.env` dan bersihkan cache konfigurasi. Worker Cronicle memakai token baru pada siklus berikutnya. Pengaturan baru tidak mengirim ulang transaksi lama.

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

Saat memverifikasi job Cronicle, jalankan **Run Now** dan periksa status sukses serta log `telegram:dispatch`. Konfirmasi pengiriman nyata melalui riwayat owner; job yang berhasil dengan antrean kosong hanya membuktikan worker dapat berjalan. Tombol **Kirim pesan percobaan** tetap dapat dipakai sebelum notifikasi otomatis diaktifkan.

Referensi resmi: [Telegram sendMessage](https://core.telegram.org/bots/api#sendmessage), [Laravel queue](https://laravel.com/docs/12.x/queues), [Cronicle API dan pengaturan event](https://github.com/jhuckaby/Cronicle/blob/master/docs/APIReference.md).
