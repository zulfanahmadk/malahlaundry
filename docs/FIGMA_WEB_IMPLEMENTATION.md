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

Konteks seluruh 17 node dan aset lokal sudah diinspeksi. Setelah pengguna mengizinkan testing dan build, seluruh suite Laravel dijalankan pada SQLite `:memory:`: **66 test, 464 assertion, semuanya lulus**. Konfigurasi PHPUnit memaksa database pengujian terisolasi, dan bootstrap pengujian menolak konfigurasi database bisnis sebelum migrasi pengujian berjalan. Pint seluruh repositori juga lulus.

Browser memverifikasi **45 kasus** pada halaman dan viewport desktop/ponsel, termasuk login tanpa scroll, buka/tutup sidebar dan dropdown, dialog layanan, serta tampilan form pengaturan. Pengujian tambahan memverifikasi validasi login, pembukaan kembali dialog layanan/pengguna yang ditolak beserta pesan error dan nilai input, persistensi pergantian cabang, serta pembaruan pratinjau template. Pengujian memakai database terpisah. Pemeriksaan sumber dan manifest 103 aset tetap tersedia sebagai pelengkap pengujian runtime.

Pipeline frontend juga diverifikasi: `npm ci` dan `npm run build` selesai dengan exit code 0, menghasilkan manifest serta bundle CSS/JavaScript produksi. `package-lock.json` ditambahkan karena sebelumnya tidak tersedia, sehingga pemasangan ulang memakai versi yang sama; versi mayor pada `package.json` tetap. Dashboard menggunakan aset CSS/JavaScript lokal yang terpisah dari bundle Vite. Overflow horizontal halaman detail pelanggan/cucian pada ponsel diperbaiki dengan membatasi kolom grid, sehingga tabel bergulir di dalam kontainernya. Foto dialog presensi disembunyikan sampai sumber foto dipilih.

Hasil lokal tidak membuktikan API produksi sudah diperbarui. SSH produksi menolak autentikasi yang tersedia sehingga deployment belum dapat dilakukan. API produksi masih perlu menerima kode/middleware terbaru sebelum sinkronisasi tablet dapat dinyatakan pulih. Seluruh perubahan web dan pekerjaan selanjutnya dipusatkan pada branch `qa-master`, sesuai instruksi pengguna.

## Perapihan lanjutan 4 Oktober 2026

- Login memakai tinggi viewport `100dvh`, tanpa minimum 840 px yang menyebabkan scroll. Tampilan pengantar disederhanakan pada ponsel atau layar pendek agar form tetap mendapat ruang.
- Tombol sidebar tersedia pada desktop dan ponsel, termasuk tombol tutup di dalam sidebar agar tetap dapat dijangkau saat overlay menutupi header. Pilihan desktop disimpan di browser. Kelompok Operasional, Manajemen, dan Pengaturan menggunakan dropdown `details/summary`; halaman aktif tetap terlihat ketika berpindah halaman. Drawer mobile mendukung Escape, penutupan dari overlay, pemulihan fokus, dan navigasi keyboard dalam menu. Isi dropdown tertutup disembunyikan secara eksplisit oleh CSS.
- CSS/JavaScript bersama dirapikan, dan CSS pagination dipindahkan dari template ke stylesheet. Indentasi 20 template login, layout, dan dashboard dirapikan. Form layanan/pengguna yang gagal validasi dibuka kembali; nilai form layanan tidak diterapkan ke seluruh dialog layanan lainnya.
- Monitor tidak menyebut perangkat tersinkron hanya karena antreannya kosong. Laporan awal tanpa konfirmasi selesai, error terakhir, antrean gagal, dan perangkat yang lama tidak melapor memerlukan perhatian. Waktu sinkron terakhir tetap hanya berasal dari konfirmasi selesai perangkat.
- Halaman Laravel `welcome`, skrip password lama yang tidak dirujuk, dua log pemeriksaan, serta empat skrip sekali pakai pengunduh/perapihan Figma di direktori workspace dihapus. Konteks desain, aset, database, backup, migrasi dan file build yang masih dirujuk tetap tersedia.

Hasil pemeriksaan sumber: file PHP dan JavaScript utama tanpa error sintaks, 28 template Blade tanpa block tidak berpasangan, CSS tanpa error grammar, route template tanpa referensi hilang, dan seluruh 103 aset web tidak kosong serta tersedia. Pemeriksaan ini dilengkapi hasil PHPUnit dan browser di atas.

## Pemeriksaan API lanjutan

Validasi API sekarang memberikan pesan konfigurasi apabila konteks middleware cabang hilang. Tanggal arsip pelanggan diserialisasi ISO8601; ACK menyertakan UUID data dan identitas unggahan foto. Retry item identik mempertahankan item, sedangkan perubahan item/nominal/pengambilan menaikkan versi. Referensi UUID pelanggan dinormalisasi sebelum lookup; pelanggan yang belum tersinkron memberi error validasi 422 yang jelas dan seluruh batch dibatalkan.

Sepuluh regresi sinkronisasi baru memverifikasi presensi cabang 1 dan ACK retry, penolakan cabang berbeda, middleware cabang yang hilang, metadata arsip yang dikirim ulang kasir, proteksi path foto cache, timestamp ISO yang dapat dibaca Android, versi transaksi pada retry, referensi pelanggan yang hilang/huruf besar, laporan perangkat selesai, dan pembatasan cabang akun kasir. Pengujian foto lain tetap memverifikasi pemilik, jenis/ukuran berkas, penggantian foto, serta pembersihan berkas lama. Kepemilikan presensi dan batas cabang tidak dilonggarkan.

Pemeriksaan read-only MySQL lokal memastikan 1 cabang aktif, 2 akun aktif pada cabang 1, 4 layanan, serta 0 pelanggan/transaksi/item/presensi. Tidak ada reset atau seeding database bisnis. Seluruh endpoint autentikasi terlindungi, pengaturan, cabang, push/pull, records, upload foto dan laporan perangkat menggunakan middleware akun aktif dan pemilihan cabang.

Antrean tablet memakai cabang yang benar tetapi API produksi belum sejalan dengan seluruh kode lokal. Bukti diagnosis, hasil pengujian Android/web, dan langkah pembaruan lengkap tersedia dalam [laporan pemeriksaan bersama](SYNC_REVIEW_2026_10_04.md). API produksi belum diperbarui karena autentikasi SSH belum berhasil.

## Pemeriksaan terbaru setelah pembaruan produksi

Pengguna mengonfirmasi dashboard kembali dapat dibuka. Hash aset produksi yang diperiksa sudah cocok dengan sumber lokal, dan POST `/api/v1/sync/device` kini merespons 401 tanpa autentikasi. Pernyataan produksi belum diperbarui di bagian sebelumnya merupakan hasil pemeriksaan historis. Dashboard terautentikasi produksi belum diuji langsung melalui sesi owner dan SSH otomatis masih ditolak autentikasinya.

Pemeriksaan lanjutan lulus **67 tes Laravel / 494 assertion**, Pint, **45 kasus browser**, serta **4 kasus nota/not-found desktop/ponsel**, tanpa error JavaScript, aset font/favicon gagal atau overflow. Login tanpa scroll dan buka/tutup sidebar/dropdown tetap tercakup. Nota memakai Inter lokal dan empat template memakai favicon SVG yang sudah ada; favicon ICO kosong dan tes scaffold `assertTrue(true)` beserta suite Unit kosong dihapus. Direktori kosong `tests/Unit` dan `app/Console/Commands` berhasil dibersihkan; penghapusan parent kosong `app/Console` ditolak kebijakan otomatis.

Regresi API tambahan memastikan layanan yang hilang/cabang lain tetap ditolak, sedangkan referensi valid mempertahankan harga, nama, satuan dan kuantitas historis tanpa duplikasi pada retry. Pemulihan UUID lama di Android tidak mengubah aturan validasi web atau skema database. Seluruh perubahan berada di `qa-master`; perubahan web lanjutan ini belum dideploy otomatis. Bukti perangkat, cadangan dan batas verifikasi produksi tersedia dalam [laporan pemeriksaan bersama](SYNC_REVIEW_2026_10_04.md).

API produksi telah mengakui transaksi nyata tablet setelah pemulihan referensi layanan: antrean 0, revision dan syncedRevision sama-sama 2, total tetap Rp155.000. Bukti ini memverifikasi jalur sinkronisasi akun kasir tersebut; hasil dashboard produksi masih berdasarkan konfirmasi pengguna, sementara dashboard berisi data diuji terautentikasi pada lingkungan lokal.

## Perapihan tampilan web berikutnya

Perapihan menggunakan konteks dan screenshot Figma Beranda, Cucian, Layanan serta Toko & Nota, dengan komponen Blade, warna, Inter dan aset SVG yang sudah tersedia. Dashboard menempatkan grafik dan tabel terbaru dalam satu kolom, sejajar dengan ringkasan operasional di sampingnya. Margin tambahan pada statistik, toolbar dan status pengaturan dihapus agar tidak bertumpuk dengan gap layout.

Aturan input umum menggunakan `:where` sehingga padding ikon pencarian dan chevron select tidak tertimpa. Filter tanggal, pencarian, select dan tombol sejajar di bagian bawah. Label yang membungkus input memiliki jarak eksplisit dan form tidak meregang mengikuti textarea sebelahnya. Input logo mempunyai ruang yang cukup, tombol file konsisten dan jadwal mingguan menggunakan dua kolom pada ponsel.

Kode nota, tanggal penting dan nominal tabel mempertahankan satu baris. Teks panjang tetap dapat membungkus, aksi/status memakai jarak yang konsisten, dan tabel dapat digulir di dalam kartunya. Petunjuk geser serta fokus keyboard hanya tersedia ketika tabel benar-benar meluber di dalam kontainernya. Halaman tidak meluber horizontal. Header dapat membungkus, pemilih cabang mendapat ruang lebih luas dan tombol aksi di ponsel menempati baris sendiri. Notifikasi mengikuti tinggi header serta dibatasi ruang layar; nilai grafik panjang memakai ellipsis dengan nominal lengkap pada title dan ringkasan aksesibilitas.

Verifikasi akhir: **95 pemeriksaan halaman** (19 halaman pada lebar 320, 390, 768, 1024 dan 1440 px) lulus, begitu juga login tanpa scroll pada lima ukuran, dialog layanan, sidebar/dropdown, keselarasan filter, ikon yang terlihat dalam ukuran asli dan petunjuk tabel. Empat ukuran tambahan memverifikasi panel notifikasi tetap di dalam viewport, Escape dan gulir tabel melalui keyboard. Tidak ada error JavaScript, HTTP/aset gagal atau overflow halaman. **67 tes Laravel / 494 assertion**, Pint, pemeriksaan sintaks JavaScript dan `git diff --check` lulus. Database/browser fixture tetap terisolasi dari database bisnis.

Screenshot dan laporan geometri lokal tersedia pada direktori workspace `docs/ui-review/web-polish`. Perubahan dikerjakan dan dipush ke `qa-master`; tidak ada perubahan Android, database bisnis, `master` maupun deployment produksi dalam perapihan ini.

## Koreksi tabel kosong dari screenshot pengguna

Screenshot dashboard tanpa transaksi memperlihatkan pesan kosong rata kanan. Aturan `td:last-child` lebih spesifik daripada `.empty`, sehingga sel `colspan` menerima perataan nominal. Aturan kolom terakhir kini mengecualikan sel `colspan`; pesan kosong rata tengah dengan font-weight 400. Pada ponsel, header tabel yang seluruh datanya kosong disembunyikan agar pesan mendapat lebar kartu penuh. Header desktop dan tabel yang berisi data tetap ditampilkan.

Verifikasi Chrome pada fixture cabang tanpa transaksi lulus **44 kasus**: 11 halaman pada lebar 1536, 1440, 390 dan 320 px. Pengukuran memastikan pesan berada di tengah, font normal, tidak terpotong dan halaman tidak meluber; header desktop tetap terlihat. Nominal pada cabang berisi transaksi tetap rata kanan. Screenshot `empty-dashboard-1536.png`, `empty-dashboard-390.png` dan `empty-table-report.json` tersedia di direktori bukti lokal yang sama. Database bisnis tidak diubah.
