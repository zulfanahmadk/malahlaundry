<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $defaults = \App\Services\StoreConfiguration::TEMPLATES;
        $legacy = [
            'WA_REMINDER' => [
                <<<'MESSAGE'
Halo {nama}, cucian {no_transaksi} sudah siap diambil sejak {tanggal_siap}. Silakan mampir ke {outlet}.
Sisa tagihan: Rp{sisa_bayar}
Nota: {url_nota}
MESSAGE,
                <<<'MESSAGE'
Halo {nama_pelanggan}, cucian nota {nomor_nota} sudah siap diambil sejak {tanggal_siap_diambil}. Mohon ambil sebelum {tanggal_batas_ambil}. Detail: {tautan_nota}
MESSAGE,
            ],
            'WA_DITERIMA' => [
                <<<'MESSAGE'
Halo {nama}, cucian Anda sudah diterima di {outlet}.
No. nota: {no_transaksi}
{items}
Total: Rp{total}
Pembayaran: {status_bayar}
Nota: {url_nota}
{alamat_outlet}
Hubungi: {telepon_outlet}
MESSAGE,
                <<<'MESSAGE'
Halo Kak {nama}, cucian Anda di *Malah Laundry* telah kami terima dengan nomor {no_transaksi}.
Total tagihan: Rp{total} ({status_bayar}).

Cek rincian nota digital Anda di sini:
{url_nota}

Terima kasih telah mempercayakan pakaian Anda kepada kami!
MESSAGE,
                <<<'MESSAGE'
Halo {nama_pelanggan}, cucian Anda sudah kami terima. Nota {nomor_nota}, total {total}. Cek nota: {tautan_nota}
MESSAGE,
            ],
            'WA_SIAP_DIAMBIL' => [
                <<<'MESSAGE'
Halo {nama}, cucian {no_transaksi} sudah siap diambil di {outlet}.
Sisa tagihan: Rp{sisa_bayar}
Nota: {url_nota}
{alamat_outlet}
Hubungi: {telepon_outlet}
MESSAGE,
                <<<'MESSAGE'
Halo Kak {nama}, cucian Anda ({no_transaksi}) di *Malah Laundry* sudah SELESAI dan SIAP DIAMBIL.

Sisa tagihan: Rp{sisa_bayar}.
Silakan tunjukkan nota digital saat pengambilan:
{url_nota}

Terima kasih!
MESSAGE,
                <<<'MESSAGE'
Halo {nama_pelanggan}, cucian nota {nomor_nota} sudah siap diambil. Total {total}. Detail: {tautan_nota}
MESSAGE,
            ],
            'WA_SELESAI' => [
                <<<'MESSAGE'
Terima kasih {nama}, cucian {no_transaksi} telah diambil.
Terima kasih telah menggunakan {outlet}.
Nota: {url_nota}
Hubungi: {telepon_outlet}
MESSAGE,
                <<<'MESSAGE'
Halo Kak {nama}, cucian ({no_transaksi}) telah selesai diambil. Terima kasih banyak telah menggunakan jasa *Malah Laundry*! Semoga pakaian Anda selalu bersih dan wangi.
MESSAGE,
                <<<'MESSAGE'
Terima kasih {nama_pelanggan}. Pesanan {nomor_nota} telah selesai, total {total}. Nota digital: {tautan_nota}
MESSAGE,
            ],
        ];
        foreach ($defaults as $type => $content) {
            $row = DB::table('whatsapp_templates')->where('type', $type)->first();
            if (! $row) {
                DB::table('whatsapp_templates')->insert(['type' => $type, 'content' => $content, 'created_at' => now(), 'updated_at' => now()]);
            } elseif (blank($row->content) || in_array($row->content, $legacy[$type] ?? [], true)) {
                DB::table('whatsapp_templates')->where('id', $row->id)->update(['content' => $content, 'updated_at' => now()]);
            }
        }
        foreach (DB::table('branches')->get(['id', 'templates']) as $branch) {
            $templates = json_decode($branch->templates ?? '{}', true) ?: [];
            foreach ($defaults as $type => $content) {
                if (blank($templates[$type] ?? null) || in_array($templates[$type], $legacy[$type] ?? [], true)) {
                    $templates[$type] = $content;
                }
            }
            DB::table('branches')->where('id', $branch->id)->update(['templates' => json_encode($templates, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Preserve edited messages on rollback.
    }
};
