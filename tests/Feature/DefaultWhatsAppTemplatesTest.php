<?php

namespace Tests\Feature;

use App\Services\StoreConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DefaultWhatsAppTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_upgrade_preserves_owner_messages_and_refreshes_legacy_reminders(): void
    {
        $old = "Halo {nama}, cucian {no_transaksi} sudah siap diambil sejak {tanggal_siap}. Silakan mampir ke {outlet}.\nSisa tagihan: Rp{sisa_bayar}\nNota: {url_nota}";
        DB::table('branches')->where('id', 1)->update(['templates' => json_encode([
            'WA_DITERIMA' => 'Pesan khusus owner: {nama}', 'WA_REMINDER' => $old,
        ])]);
        DB::table('whatsapp_templates')->updateOrInsert(['type' => 'WA_REMINDER'], ['content' => $old]);
        $migration = require database_path('migrations/2026_10_04_000001_refresh_default_whatsapp_templates.php');
        $migration->up();
        $migration->up();
        $store = app(StoreConfiguration::class)->read(false, 1);
        $this->assertSame('Pesan khusus owner: {nama}', $store['templates']['WA_DITERIMA']);
        $this->assertSame(StoreConfiguration::TEMPLATES['WA_REMINDER'], $store['templates']['WA_REMINDER']);
        $this->assertSame(StoreConfiguration::TEMPLATES['WA_REMINDER'], DB::table('whatsapp_templates')->where('type', 'WA_REMINDER')->value('content'));
        foreach (['WA_SIAP_DIAMBIL', 'WA_SELESAI', 'WA_REMINDER'] as $type) {
            foreach (['{no_transaksi}', '{items}', '{total}', '{total_dibayar}', '{sisa_bayar}', '{status_bayar_label}', '{url_nota}'] as $variable) {
                $this->assertStringContainsString($variable, $store['templates'][$type]);
            }
        }
    }
}
