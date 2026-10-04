# Workspace owner dari 17 desain Figma

Referensi: file Figma `V99fP3pAzfUenLTP4r4ctk` (Laundry), node halaman `127:*`. Implementasi menggunakan Laravel Blade, CSS dan JavaScript lokal; tidak memerlukan build frontend. Layout bersama ada di `resources/views/layouts/app.blade.php`, komponen di `resources/views/components/workspace-*.blade.php`, dan aset asli di `public/figma/`.

| Node | Halaman / keadaan | Route | View |
|---|---|---|---|
| 127-3 | Login owner | /login | auth/login |
| 127-45 | Ringkasan toko dan dropdown notifikasi | /dashboard | dashboard/index, layouts/app |
| 127-255 | Daftar cucian | /transactions | dashboard/transactions |
| 127-489 | Detail cucian | /transactions/{uuid} | dashboard/transaction-detail |
| 127-651 | Daftar pelanggan | /customers | dashboard/customers |
| 127-880 | Detail pelanggan | /customers/{uuid} | dashboard/customer-detail |
| 127-1087 | Laporan toko | /reports | dashboard/reports |
| 127-1289 | Layanan | /services | dashboard/services |
| 127-1524 | Cabang | /branches | dashboard/branches |
| 127-1725 | Pengguna | /users | dashboard/users |
| 127-1932 | Presensi | /attendances | dashboard/attendances |
| 127-2150 | Toko dan nota | /settings | dashboard/settings |
| 127-2347 | Jam buka | /opening-hours | dashboard/hours |
| 127-2541 | Template pesan | /message-templates | dashboard/templates |
| 127-2735 | Profil | /profile | dashboard/profile |
| 127-2929 | Sinkronisasi | /synchronization | dashboard/sync |
| 127-3130 | Notifikasi | /notifications | dashboard/notifications |

## Tampilan dan data

- Sidebar gelap 252 px, warna utama `#3158D4`, latar `#F4F7FB`, kartu putih, tabel, filter dan preferensi mengikuti konteks desain. Inter dan 103 SVG asli tersedia lokal. SVG mempertahankan dimensi intrinsik; manifest memetakan aset ke node. Sidebar menjadi drawer pada layar kecil, kartu menjadi dua kolom, dan tabel dapat digeser horizontal.
- Angka contoh Figma diganti data database. Cabang dipilih melalui sesi browser; layanan, pelanggan, transaksi, presensi, laporan dan pengaturan mengikuti pilihan itu. Daftar cabang menampilkan seluruh cabang agar owner dapat mengelolanya.
- Transaksi dan pelanggan di web hanya-baca. Tautan rincian dan nota bekerja melalui UUID sebenarnya. Mutasi transaksi tetap dari Android. Layanan memiliki harga, satuan `kg`/`pcs`/`m2`, kecepatan dan durasi. Satuan luas dapat dicatat di Android, dengan maksimal dua desimal.
- Laporan web dan Android menggunakan transaksi lunas untuk omzet; kontribusi layanan menggunakan nilai order dalam periode. Grafik dashboard memuat tujuh hari termasuk hari ini, dan laporan memuat enam bulan terakhir. Ekspor laporan XLSX yang sudah ada dipertahankan. Ekspor pelanggan/presensi CSV mengikuti filter dan mencegah formula spreadsheet dari teks pengguna.
- Rincian tanggal memakai `7 September 2026, 17.00`, zona WIB, termasuk presensi, nota, aktivitas akun, laporan perangkat dan ekspor. Input pemilih tanggal tetap menggunakan format native browser.
- Foto presensi dibuka melalui route owner yang mengikuti cabang. Penilaian masuk menggunakan jam buka dan pengecualian tanggal. "Belum tercatat" tidak mengklaim staf tidak hadir: data offline mungkin belum tersinkron.

## Pengaturan dan WhatsApp

- Pengaturan web menyimpan identitas, logo dan ketentuan pada cabang aktif, sehingga dapat dibaca Android dan nota publik. Perubahan sebagian tidak menghapus template/preferensi lain. Preferensi menyembunyikan nomor kontak/ketentuan pada nota digital.
- Jam buka disimpan per hari, dengan maksimal 20 pengecualian tanggal. Jadwal khusus menggantikan jadwal mingguan jika diaktifkan. Waktu menggunakan WIB; jam tutup harus setelah jam buka.
- Template diterima, siap diambil, selesai dan pengingat tersimpan per cabang. Preferensi mengatur ketersediaan pratinjau manual diterima/siap/pengingat pada Android setelah sinkronisasi; preferensi juga dapat diubah dari editor template Android. Penyimpanan halaman pengaturan Android mengirim field sesuai halaman agar perubahan jam tidak menimpa identitas atau template lain.
- **Semua pesan WhatsApp dikirim manual**: aplikasi membuka WhatsApp berisi nomor dan teks, kemudian kasir/user menekan Kirim. Tidak ada gateway, cron pengiriman, atau hitungan "pesan terkirim" yang tidak bisa dibuktikan. Preferensi di Figma yang menyiratkan kirim otomatis disesuaikan dengan instruksi pengguna.
- Perubahan profil membutuhkan password saat ini. Username/password baru mencabut token Android dan sesi web lain. Pilihan pencabutan sesi dijalankan saat formulir disimpan. Pemberitahuan login dapat dinonaktifkan.

## Monitor sinkronisasi dan migrasi

Migrasi tambahan `2026_10_04_000001_add_owner_workspace.php` menambahkan JSON preferensi cabang, jam khusus, profil/login pengguna, `device_sync_states`, dan `owner_notification_reads`. Kolom satuan layanan menjadi VARCHAR untuk mendukung `m2`; rollback tidak mempersempitnya kembali ke enum karena dapat merusak data layanan luas.

Endpoint `POST /api/v1/sync/device` menerima laporan perangkat Android yang terautentikasi dan mengikuti `X-Branch-Id`. Android melaporkan UUID instalasi, versi, jumlah antrean, jumlah gagal, kesalahan terakhir dan sinkronisasi selesai. Pelaporan bersifat tambahan: kegagalannya tidak menggagalkan pencatatan atau mengubah pengakuan transaksi. Jumlah antrean di web adalah **laporan terakhir**, bukan pembacaan langsung perangkat offline. "Periksa sekarang" hanya memuat ulang data server.

Notifikasi berasal dari cucian siap >3 hari, presensi terlambat dalam tujuh hari termasuk hari ini, laporan perangkat tertunda/gagal/belum melapor, dan login terakhir akun. Status dibaca disimpan per owner dan cabang. Tidak ada pengiriman pesan keluar otomatis.

Validasi push menerima metadata tambahan pada record cache tanpa meneruskannya ke penyimpanan. Field yang disimpan tetap dibatasi aturan eksplisit dan controller. Struktur daftar, UUID, nilai, pemilik, cabang, urutan status dan konflik pembayaran tetap divalidasi. Pesan validasi tersedia dalam bahasa Indonesia; Android menampilkan field kesalahan. Antrean gagal lama perlu dicoba ulang melalui tombol sinkronisasi Android setelah API diperbarui.

Migrasi sudah diterapkan pada MySQL **lokal** 4 Oktober 2026. Backup privat: `storage/app/private/backups/before-owner-workspace-20261004-090836.sql` beserta jumlah record. Sesudah migrasi tetap 1 cabang, 2 pengguna, 4 layanan, 0 pelanggan/transaksi/item/presensi; tabel monitor dan status baca mulai kosong. Tidak dilakukan reset atau perubahan database produksi.

Untuk lingkungan lain, cadangkan database kemudian jalankan `php artisan migrate --force` bersama kode API/web terbaru. Tidak perlu gateway WhatsApp.

## Batas verifikasi

Konteks seluruh 17 node dan aset lokal sudah diinspeksi. Route, model, penyimpanan preferensi, metadata SVG dan pemanggilan aset ditinjau dari sumber. Pemeriksaan lanjutan mencakup sintaks PHP/JavaScript, struktur CSS, keseimbangan directive Blade, route dan manifest aset. **Testing, build, kompilasi aplikasi dan verifikasi visual runtime tidak dijalankan**, sesuai instruksi pengguna. Tidak ada deployment, commit atau push pada pekerjaan ini.

## Perapihan lanjutan 4 Oktober 2026

- Login memakai tinggi viewport `100dvh`, tanpa minimum 840 px yang menyebabkan scroll. Tampilan pengantar disederhanakan pada ponsel atau layar pendek agar form tetap mendapat ruang.
- Tombol sidebar tersedia pada desktop dan ponsel, termasuk tombol tutup di dalam sidebar agar tetap dapat dijangkau saat overlay menutupi header. Pilihan desktop disimpan di browser. Kelompok Operasional, Manajemen, dan Pengaturan menggunakan dropdown `details/summary`; halaman aktif tetap terlihat ketika berpindah halaman. Drawer mobile mendukung Escape, penutupan dari overlay, pemulihan fokus, dan navigasi keyboard dalam menu. Isi dropdown tertutup disembunyikan secara eksplisit oleh CSS.
- CSS/JavaScript bersama dirapikan, dan CSS pagination dipindahkan dari template ke stylesheet. Indentasi 20 template login, layout, dan dashboard dirapikan. Form layanan/pengguna yang gagal validasi dibuka kembali; nilai form layanan tidak diterapkan ke seluruh dialog layanan lainnya.
- Monitor tidak menyebut perangkat tersinkron hanya karena antreannya kosong. Laporan awal tanpa konfirmasi selesai, error terakhir, antrean gagal, dan perangkat yang lama tidak melapor memerlukan perhatian. Waktu sinkron terakhir tetap hanya berasal dari konfirmasi selesai perangkat.
- Halaman Laravel `welcome`, skrip password lama yang tidak dirujuk, dua log pemeriksaan, serta empat skrip sekali pakai pengunduh/perapihan Figma di direktori workspace dihapus. Konteks desain, aset, database, backup, migrasi dan file build yang masih dirujuk tetap tersedia.

Hasil pemeriksaan sumber: 71 file PHP tanpa error sintaks, JavaScript utama tanpa error sintaks, 28 template Blade tanpa block tidak berpasangan, CSS tanpa error grammar, route template tanpa referensi hilang, dan seluruh 103 aset web tidak kosong serta tersedia. Ini tidak menggantikan pengujian browser atau aplikasi.
