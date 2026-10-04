# Pemeriksaan sinkronisasi 4 Oktober 2026

## Bukti dari perangkat dan server

Tablet terhubung memakai aplikasi 1.6.0, Room versi 5, akun kasir, serta server `https://laundry.malahproject.com`. Sesi dan seluruh tiga payload antrean memakai cabang ID 1. Pelanggan dan presensi berstatus FAILED dengan validasi `branch_id`; transaksi masih PENDING karena menunggu pelanggan. Database perangkat hanya dibaca, tidak dihapus atau diubah.

API produksi membalas GET `/api/v1/auth/user` dengan 401 sesuai endpoint terproteksi, tetapi `/api/v1/sync/device` membalas 404 route tidak ditemukan. HTML login produksi juga belum memakai `css/workspace.css`. Kode lokal mempunyai kelima route sinkronisasi dengan middleware `auth:sanctum`, `active`, dan `branch`. Semua migrasi lokal sudah diterapkan.

Perbedaan tersebut menunjukkan produksi belum memakai keseluruhan implementasi lokal. Konteks cabang yang hilang akibat kode atau cache route yang tidak konsisten merupakan dugaan penyebab penolakan cabang, sebab payload dan sesi sudah sama. Konfigurasi internal produksi belum dapat dibaca: koneksi SSH tersimpan ditolak autentikasinya.

## Perbaikan sumber

- Android memakai cabang asli record pada push dan unggah foto. ID cabang tidak diubah untuk melewati validasi.
- Sinkronisasi manual dapat menggantikan worker yang menunggu retry. Tombol menunggu sinkronisasi aktif selesai agar tidak membatalkan unggahan berulang kali.
- Pelanggan dikirim sebelum transaksi; hanya layanan tertunda yang benar-benar dipakai suatu transaksi yang menghambatnya. Layanan gagal yang tidak terkait tidak menahan seluruh transaksi.
- Konfirmasi UUID dan jenis foto diperiksa jika tersedia pada API. Penghapusan antrean tetap mensyaratkan revisi lokal yang sesuai. Konflik nominal/item mempertahankan transaksi lokal untuk ditinjau.
- Tanggal arsip pelanggan API menjadi ISO8601. Android juga membaca tanggal SQL lama sebagai WIB dan menampilkan kesalahan format respons dengan jelas.
- API memberikan pesan konfigurasi bila middleware tidak menyediakan konteks cabang. Validasi kepemilikan dan cabang tetap berlaku.
- Retry transaksi identik mempertahankan item; perubahan item, nominal, atau waktu pengambilan menaikkan versi. Metadata arsip yang tidak berubah tidak menolak kasir. Perubahan arsip tetap membutuhkan owner.
- Halaman sinkron menampilkan server dan ID cabang, kesalahan, status proses, serta akses perbarui login. Form login ulang memakai akun/server sesi aktif.
- Pembayaran tunai lunas pada transaksi baru dan detail transaksi membuka popup uang diterima dan kembalian. Nominal kurang atau terlalu besar tidak dapat dikonfirmasi. Kesalahan penyimpanan tampil di popup. Nominal uang diterima dipakai sebagai bantuan perhitungan saat konfirmasi; tidak menambah kolom database atau mengubah nilai tagihan.
- Halaman pelanggan/cabang yang hilang memiliki pesan pemulihan; form edit tidak membuat record baru tanpa sengaja. Presensi melepas lokasi lama ketika mengambil lokasi baru.
- Web memperbaiki empat selector CSS yang sebelumnya diabaikan browser, jarak form edit pengguna, urutan ekspor presensi, serta pencarian tabrakan nomor nota lintas cabang.

Tiga folder kosong yang tidak dipakai dihapus: `android/ui-review`, serta package produksi `ui/components` dan `ui/customers` yang sumber lamanya sudah dipindahkan ke fixture AndroidTest. Referensi desain, aset, fixture yang masih dirujuk, database, backup dan toolchain dipertahankan.

## Penyelesaian pada lingkungan produksi

API produksi belum diperbarui. APK tablet sudah diperbarui ke 1.6.1 (versionCode 8) melalui instalasi pengganti dengan signature yang sama. Cadangan privat mencakup APK lama, database, preferensi, dan foto presensi; pemeriksaan database cadangan lolos integrity_check dengan 1 pelanggan, 1 transaksi, 1 presensi dan 3 antrean. Setelah akses server tersedia, cadangkan aplikasi/database, terapkan kode web lengkap beserta aset dan dependensi lockfile, pertahankan `.env`/APP_KEY/storage, lalu periksa status migrasi. Jalankan migrasi tambahan yang belum diterapkan dengan `php artisan migrate --force`, dan bersihkan konfigurasi/route/view lama dengan `php artisan optimize:clear`. Jangan reset database atau menjalankan seeder akun demo.

Periksa `php artisan route:list --path=api/v1/sync` agar push, pull, records, upload, dan device memakai middleware cabang. Proses PHP yang masih memegang kode lama perlu dimuat ulang melalui pengelolaan PHP-FPM server bila diperlukan. Instal APK yang dibangun dari sumber terbaru sebagai pembaruan aplikasi, kemudian pilih Coba ulang semua. Tidak perlu menghapus data Android atau mengganti cabang antrean.

Pengguna kemudian mengizinkan testing dan build. Verifikasi aktual Laravel pada SQLite terisolasi lulus 66 test dan 464 assertion. Pint lulus. Browser desktop/ponsel lulus 45 kasus serta pengujian validasi form, persistensi cabang dan preview template. npm ci dan npm run build lulus setelah package-lock.json ditambahkan. Database bisnis tidak dipakai untuk fixture pengujian.

Pemeriksaan sumber sebelum pembersihan fixture: 74 sumber PHP dan 75 sumber Kotlin tanpa error sintaks; 12 XML Android dapat dibaca; 28 template Blade memiliki tag/directive berpasangan; JavaScript utama lolos pemeriksaan sintaks; 308 aturan CSS tanpa error deklarasi atau spasi pseudo-selector yang salah. Semua referensi manifest aset tersedia dan tidak kosong. Dua puluh query Room dapat dipersiapkan dengan EXPLAIN pada salinan database perangkat read-only; tujuh tabel cocok dengan deklarasi skema Room 5. Kedua repository lolos `git diff --check`.

## Verifikasi Android dan tablet

Build debug, QA, dan APK instrumentation lulus, begitu juga 34 unit test, ktlintCheck, dan lintDebug (0 error; warning dependensi/pedoman API masih dilaporkan). Sepuluh temuan lint tentang StateFlow di composition diperbaiki dengan collectAsStateWithLifecycle, sehingga informasi cabang ikut diperbarui setelah perubahan sesi/store. Format tanggal di tes template WhatsApp mengikuti format lengkap Indonesia/WIB.

Delapan pengujian terarah pada tablet lulus. Jalur yang terbukti: sinkron pelanggan dan transaksi tunai; presensi masuk/pulang beserta unggahan JPEG, ACK dan pembersihan foto yang diakui; owner cabang 2 membuat layanan baru, pelanggan, dan transaksi bergantung layanan; pembatasan visibilitas data cabang; ACK UUID; popup nominal kurang/overflow/kembalian; transaksi offline tersimpan dan tombol kembali ke beranda; sinkron manual dinonaktifkan selama worker aktif. HTTP pengujian memakai adb reverse ke server lokal 127.0.0.1:8789 dengan SQLite/storage sementara. Aplikasi QA terpisah dari package utama.

Suite perangkat tambahan memindahkan pengujian ke LaundryDesignApp, membedakan store_name mentah dari name tampilan cabang, serta mencakup migrasi 1?5. Sebelas fixture layar lama dan dua puluh resource teks tidak lagi dirujuk lalu dihapus. Penghapusan direktori kosong bekas fixture dan direktori APK instrumentation debug lama ditolak oleh kebijakan otomatis; direktori tersebut dibiarkan.

Pengujian UI produksi menemukan pelanggan yang disimpan dari checkout belum otomatis terpilih karena editDraft tertahan selama busy. Pilihan pelanggan kini dilakukan di dalam operasi simpan sebelum navigasi; edit pelanggan biasa tetap tidak mengubah draft. Owner tablet juga mendapat Lainnya pada sidebar yang dapat bergulir agar profil, printer dan logout bisa dijangkau. Suite lengkap diuji ulang setelah koreksi alur ini: **20 test perangkat lulus, 0 failure** dalam orientasi landscape, termasuk navigasi sidebar owner. Rotasi perangkat dikembalikan ke pengaturan awal. Suite sebelumnya dalam portrait juga memverifikasi alur kasir dan pelanggan baru; asumsi scroll pada tab navigasi disesuaikan agar tes mendukung kedua layout. Aplikasi QA/instrumentation sementara dibersihkan setelah bukti screenshot ditarik.

Sinkronisasi ke produksi belum dapat dinyatakan pulih: autentikasi SSH menolak akses, sehingga kode/middleware/migrasi API produksi belum dapat diperbarui. Hasil API lokal dan pengujian tablet tidak menggantikan verifikasi server produksi.

Hasil akhir lokal: 66 test Laravel / 464 assertion, 34 unit test Android, 20 test tablet, 45 kasus browser, Pint, ktlintCheck, lintDebug (0 error), build web dan build debug/QA/instrumentation Android semuanya lulus. Dokumentasi ini membatasi klaim pada jalur yang benar-benar diperiksa. Kamera fisik/lokasi dan printer Bluetooth belum diuji dengan perangkat/periferal nyata; pengujian foto presensi menggunakan JPEG fixture.

Pemeriksaan setelah instalasi APK final 1.6.1 memverifikasi primary key pelanggan, transaksi, presensi, dan antrean identik dengan cadangan tepat sebelum pembaruan: 1 pelanggan, 1 transaksi, 1 presensi, 3 antrean. Database sesudah pembaruan lolos integrity_check dan MainActivity terbuka dengan status ok. Cadangan akhir berada pada folder privat LOCALAPPDATA/MalahLaundry/device-backups/20261004-143123-final; APK lama tersedia pada cadangan sebelumnya 20261004-140028. Server, akun dan cabang aktif Android dipertahankan.
