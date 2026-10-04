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

Admin menerima satu notifikasi untuk setiap tiket baru melalui lonceng topbar
dan **Dukungan > Notifikasi** (`/admin/notifications`). Tanda merah menunjukkan
ada tiket yang belum dibaca oleh akun admin tersebut. Status baca disimpan per
admin dalam `admin_ticket_reads`, sehingga membuka tiket tidak menghapus tanda
belum dibaca pada akun admin lainnya. Tiket yang sudah ada saat fitur dipasang
akan muncul sebagai belum dibaca sampai dibuka atau ditandai dibaca.

Lonceng memeriksa tiket baru setiap 30 detik selama halaman terlihat, saat tab
kembali aktif, dan saat lonceng dibuka. Empat notifikasi ditampilkan di popover;
jumlah belum dibaca tetap menghitung semua tiket. Daftar lengkap menyediakan
pencarian, filter dibaca/belum dibaca, dan pagination. Membuka detail tiket akan
menandai notifikasinya dibaca. Tombol **Tandai semua dibaca** memproses tiket
sampai batas saat form dimuat; tiket yang datang sesudahnya tetap belum dibaca.
Status baca tidak mengubah keputusan/progres tiket atau angka tiket menunggu
keputusan. Notifikasi tersedia di web, tanpa queue atau layanan push tambahan.

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

Data tersimpan dalam `support_tickets`, `ticket_updates`, dan `ticket_attachments`. Histori dipertahankan
bersama tiket; akun pengirim yang masih memiliki tiket tidak bisa dihapus melalui
database tanpa menangani referensinya. Akses hanya untuk sesi web owner/admin
aktif; kasir tidak mendapat akses. Fitur ini tidak mengirim WhatsApp/email dan
belum menyediakan form tiket native Android.

Lampiran tersedia pada laporan baru, tanggapan owner/admin, dan catatan progres
atau hasil admin. Format: foto JPG/JPEG/PNG/WebP/GIF, PDF, Word DOC/DOCX, serta
Excel XLS/XLSX. Maksimal 5 file per pengiriman, masing-masing 10 MiB. Pesan tetap
wajib diisi pada tanggapan/pembaruan. Jika validasi gagal, pilih ulang file;
browser tidak mengisi kembali input file. Daftar file terpilih ditampilkan
sebelum pengiriman, dan lampiran tersimpan pada aktivitas terkait di riwayat.

File disimpan privat di `storage/app/private/ticket-attachments`, memakai nama
acak. Tautan unduh memeriksa sesi aktif, pemilik tiket/admin, serta kecocokan
lampiran dengan tiket; tidak ada URL publik atau kebutuhan `storage:link`.
Unduhan memakai `Content-Disposition: attachment`, `nosniff`, dan `no-store`.
Ekstensi dan isi/MIME file diperiksa; dokumen Office diperiksa juga struktur
ZIP/OLE-nya. Lampiran tidak dijalankan server. Kegagalan transaksi membatalkan
record dan membersihkan file yang sudah ditulis. Backup database perlu disertai
folder lampiran ini. Penghapusan langsung melalui database tidak menghapus file
fisik secara otomatis.

Deployment memerlukan migrasi:

```sh
php artisan migrate --force
php artisan config:cache
```

Tidak membutuhkan function, trigger, atau event database. Transaksi, nomor unik,
dan pemeriksaan revisi ditangani Laravel. Log diagnostik menggunakan fitur
`tiket-bantuan`; audit menyimpan metadata tindakan, tanpa isi laporan/tanggapan.

PHP memerlukan ekstensi fileinfo, zip, dan mbstring. Agar lima file 10 MiB dapat
dikirim bersama, gunakan `upload_max_filesize` minimal 10M, `post_max_size`
minimal 64M, dan `max_file_uploads` minimal 5. Samakan batas body Nginx/proxy.
Konfigurasi upload APK yang sudah memakai 100M/110M tetap mencukupi.
