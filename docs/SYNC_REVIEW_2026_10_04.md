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

## Pemeriksaan ulang dashboard produksi

Pengguna melaporkan HTTP 500 pada `https://laundry.malahproject.com/dashboard`. Dashboard terautentikasi pada MySQL lokal merespons HTTP 200 dalam transaksi read-only, tanpa perubahan database bisnis. Regresi tambahan memverifikasi dashboard berisi transaksi lunas, cucian terlambat diambil, presensi terlambat, perangkat gagal sinkron, notifikasi login dan pembatasan data lintas cabang. Suite Laravel terbaru lulus **67 test / 477 assertion**. Semua 13 migrasi lokal sudah diterapkan.

Pemeriksaan HTTP produksi menunjukkan login, health check, CSS, JavaScript dan `figma/manifest.json` tersedia. Permintaan dashboard tanpa sesi hanya dialihkan ke login; ini tidak membuktikan dashboard setelah login berhasil. Endpoint push, pull dan upload yang dipanggil tanpa autentikasi mengembalikan 401, tetapi **POST `/api/v1/sync/device` masih 404**. Produksi belum dapat dinyatakan sesuai dengan seluruh route sumber `qa-master`.

Audit Android memastikan APK 1.6.1 masih berjalan dan buffer crash kosong. Bukti 34 unit test, 20 test tablet serta lint 0 error / 30 warning sesuai sumber saat ini. Pemeriksaan read-only antrean nyata masih menemukan 3 data: presensi dan pelanggan gagal pada field `branch_id`, serta transaksi tertunda. Tidak dilakukan penghapusan data, retry antrean bisnis, atau pemasangan ulang saat audit ini.

Penyebab exception dashboard produksi belum diketahui. Akses SSH ditolak autentikasinya; diperlukan exception terbaru dari `storage/logs/laravel.log` produksi untuk menentukan perbaikan. Migrasi, izin berkas dan cache produksi belum dapat diperiksa langsung. Hasil lokal tidak digunakan untuk menyatakan HTTP 500 maupun sinkronisasi produksi telah selesai diperbaiki.

## Pemeriksaan lanjutan setelah produksi diperbarui

Bagian ini menggantikan status produksi pada pemeriksaan sebelumnya. Pengguna mengonfirmasi dashboard sudah dapat dibuka. Login, health check dan aset web produksi merespons 200; hash CSS, JavaScript, manifest, font dan aset dashboard yang diperiksa cocok dengan sumber lokal. POST `/api/v1/sync/device` kini merespons 401 tanpa autentikasi, bukan 404. Dashboard terautentikasi produksi tetap belum diperiksa langsung melalui sesi owner; akses SSH otomatis masih ditolak autentikasinya.

Pemeriksaan privat tablet menemukan pelanggan dan presensi sudah menerima ACK. Satu transaksi bernilai Rp155.000 masih FAILED karena kedua UUID layanan lama tidak ditemukan di katalog server. Katalog cabang 1 mempunyai satu pengganti aktif untuk masing-masing layanan dengan nama, satuan, harga, kecepatan dan durasi yang sama.

Android 1.6.2 (versionCode 9) menarik katalog terbaru sebelum push. Pemulihan hanya berlaku bagi transaksi yang belum pernah diakui server, cabangnya sama, snapshot antrean cocok, serta setiap referensi yang hilang mempunyai tepat satu pengganti yang cocok. Layanan yang masih tercatat di katalog, termasuk yang nonaktif, tetap memakai UUID aslinya. Layanan lokal yang masih dalam antrean tidak ikut dipetakan. Pemeriksaan transaksi server dilakukan sebelum perubahan untuk melindungi nota yang mungkin sudah tersimpan tetapi ACK-nya hilang. Respons gagal, tidak lengkap, kandidat ganda atau snapshot berubah mempertahankan data lama.

Penggantian referensi dan antrean dilakukan dalam satu transaksi Room, menaikkan revisi, tanpa mengganti nama item, satuan, kuantitas, harga, total, nomor nota maupun status pembayaran/cucian. Antrean baru tetap hanya dihapus setelah ACK revisi yang sesuai. Regresi API memverifikasi penolakan UUID hilang/cabang lain, preservasi harga historis dan retry tanpa duplikasi; validasi server tidak dilonggarkan.

Verifikasi akhir sumber: **67 tes Laravel / 494 assertion**, **42 tes unit Android**, **22 tes perangkat**, **45 kasus browser** dan **4 kasus nota/not-found desktop/ponsel** lulus. Pint, ktlintCheck, build debug/QA/instrumentation dan lintDebug lulus. Lint melaporkan 0 error / 30 warning pembaruan dependensi dan pedoman API. Percobaan pertama tes pemulihan sempat timeout pada login ketika server fixture baru mulai; pengulangan dua tes terarah dan suite lengkap setelah server siap lulus. Database/storage fixture berada di TEMP dan package QA terpisah dari aplikasi utama.

Perapihan tambahan menghapus tes scaffold `assertTrue(true)`, konfigurasi suite Unit web yang sudah kosong, favicon ICO nol byte, serta file keepRules Android yang hanya berisi komentar. Empat template web memakai favicon SVG yang sudah tersedia; nota memakai Inter lokal sehingga tidak tergantung permintaan Google Fonts. Direktori kosong `web/tests/Unit` dan `web/app/Console/Commands` berhasil dihapus. Referensi desain, aset manifest, sumber aktif, migrasi, database dan cadangan dipertahankan.

Penghapusan lima direktori ditolak oleh pemeriksaan persetujuan otomatis dengan alasan `blocked by policy`: tiga package fixture Android kosong (`ui/components`, `ui/customers`, `ui/pos`), direktori APK instrumentation debug lama, dan direktori kosong `web/app/Console`. Direktori tersebut dibiarkan; penolakan tidak dicoba dilewati melalui alat lain.

APK utama sudah diperbarui ke 1.6.2 dengan signature yang sama melalui `adb install -r`. Cadangan privat terbaru sebelum instalasi berada di `LOCALAPPDATA/MalahLaundry/device-backups/20261004-172259-sync-recovery`, berisi APK sebelumnya, database, preferensi dan file. Integrity check lulus dengan 1 pelanggan, 1 transaksi, 2 item, 1 presensi dan 1 antrean. Aplikasi terbuka dengan status ok. Permintaan manual sempat menunggu karena tidak ada default network aktif. Setelah tablet tersambung kembali, sinkronisasi ke server produksi berhasil: antrean kosong dan transaksi memiliki **revision 2 / syncedRevision 2**. Kedua item kini memakai UUID katalog yang sesuai; total tetap **Rp155.000**, pembayaran BELUM, cucian DITERIMA dan cabang 1.

Pemeriksaan cadangan sesudah pembaruan dan sesudah sinkronisasi (`after-sync`) lulus integrity check. Primary key pelanggan/transaksi/presensi, nominal transaksi, kuantitas/harga/subtotal item tetap sama dengan cadangan sebelum instalasi. Data akhir tetap 1 pelanggan, 1 transaksi, 2 item dan 1 presensi, dengan **0 antrean**; versi aplikasi 1.6.2 / 9. Package QA dan instrumentation sementara dihapus, adb reverse dilepas dan server fixture dihentikan. Aplikasi utama dibuka kembali; data dan sesi tidak dihapus. Buffer crash Android tidak menunjukkan fatal exception pada pemeriksaan akhir.

Perubahan disimpan di `qa-master` kedua repository. Tidak ada pembaruan `master`, reset/seeding database bisnis, penghapusan data aplikasi utama atau deployment web otomatis dalam pemeriksaan lanjutan ini.
