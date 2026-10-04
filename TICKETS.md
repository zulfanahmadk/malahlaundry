# Tiket bantuan owner dan admin

Owner membuka **Dukungan > Tiket bantuan** (`/tickets`) untuk mengirim bugs,
keluhan, atau permintaan peningkatan aplikasi. Form mencatat jenis, aplikasi
terkait (website/Android/lainnya), judul, uraian, dan cabang yang sedang dipilih.
Nomor dibuat otomatis, misalnya `12345/BUGS/67890` atau `12345/ENCH/67890`.
Kedua kelompok angka terdiri dari lima digit; nomor unik di database.

Owner hanya melihat tiket yang dia kirim, termasuk ketika berpindah cabang.
Admin membuka **Dukungan > Tiket masuk** (`/admin/tickets`) untuk melihat tiket
seluruh owner beserta pengirim dan cabang laporan. Angka pada menu menunjukkan
jumlah tiket yang masih menunggu keputusan, juga tersedia di ringkasan admin.
Daftar dapat dicari berdasarkan nomor/judul dan difilter menurut jenis/status.

Alur penanganan:

- Diajukan: menunggu keputusan admin, progres 0%.
- Diterima: laporan disetujui, progres 0%.
- Dikerjakan: admin mengisi progres 1–99%; progres tidak boleh berkurang.
- Selesai: progres menjadi 100% dan catatan terakhir menjadi hasil penanganan.
- Ditolak: progres 0% dan catatan keputusan menjadi alasan penolakan.

Admin wajib memberi catatan pada setiap pembaruan. Tiket Diajukan dapat diterima
atau ditolak. Tiket Diterima dapat dikerjakan, diselesaikan, atau ditolak. Tiket
Dikerjakan dapat diperbarui atau diselesaikan. Tiket Selesai/Ditolak ditutup;
owner dan admin tetap dapat menambahkan tanggapan tanpa mengubah keputusan atau
hasil akhir. Semua pembaruan tampil di riwayat dengan pelaku, waktu WIB, status,
progres, dan pesan.

Form yang terkirim ulang memakai UUID pengiriman yang sama sehingga tidak
membuat tiket ganda. Pembaruan admin memakai revisi tiket; bila ada aktivitas
baru sejak form dibuka, admin harus memuat ulang sebelum menyimpan. Isi laporan
dan tanggapan ditampilkan sebagai teks, bukan HTML.

Data tersimpan dalam `support_tickets` dan `ticket_updates`. Histori dipertahankan
bersama tiket; akun pengirim yang masih memiliki tiket tidak bisa dihapus melalui
database tanpa menangani referensinya. Akses hanya untuk sesi web owner/admin
aktif; kasir tidak mendapat akses. Fitur ini tidak mengirim WhatsApp/email dan
belum menyediakan lampiran atau form tiket native Android.

Deployment memerlukan migrasi:

```sh
php artisan migrate --force
```

Tidak membutuhkan function, trigger, atau event database. Transaksi, nomor unik,
dan pemeriksaan revisi ditangani Laravel. Log diagnostik menggunakan fitur
`tiket-bantuan`; audit menyimpan metadata tindakan, tanpa isi laporan/tanggapan.
