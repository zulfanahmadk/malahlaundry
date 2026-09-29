<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Models\User;
use App\Services\StoreConfiguration;
use Database\Seeders\StoreSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function settings(): array
    {
        return ['name' => 'Laundry Bintang', 'phone' => '081234567890', 'address' => "Jalan Melati 7\nBandung",
            'receipt_terms' => "Simpan nota untuk pengambilan.\n<script>alert(1)</script>",
            'templates' => StoreConfiguration::TEMPLATES];
    }

    public function test_owner_can_save_store_logo_and_templates_from_web_and_render_public_receipt(): void
    {
        Storage::fake('public');
        $this->seed();
        $owner = User::where('username', 'owner')->firstOrFail();
        $this->actingAs($owner)->get('/settings')->assertOk();
        $this->post('/settings', [...$this->settings(), 'logo' => UploadedFile::fake()->image('brand.png', 600, 300)])
            ->assertRedirect('/settings')->assertSessionHasNoErrors();
        $record = StoreSetting::findOrFail(1);
        Storage::disk('public')->assertExists($record->logo_path);
        $image = getimagesizefromstring(Storage::disk('public')->get($record->logo_path));
        $this->assertSame(384, $image[0]);
        $this->assertSame(192, $image[1]);
        $this->get('/store/logo')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/dashboard')->assertSee('Laundry Bintang')->assertSee('Pengaturan Toko');
        $this->get('/n/e1a2b3c4-d5e6-4f7a-8b9c-0d1e2f3a4b5c')->assertOk()
            ->assertSee('Laundry Bintang')->assertSee('081234567890')->assertSee('Syarat dan Ketentuan')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('Simpan sebagai PDF')
            ->assertDontSee('window.print()', false);
    }

    public function test_owner_can_update_from_android_and_cashier_can_only_read_synced_settings(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        Sanctum::actingAs($owner);
        $logo = UploadedFile::fake()->image('brand.png')->get();
        $this->postJson('/api/v1/settings', [...$this->settings(), 'logo_base64' => base64_encode($logo)])
            ->assertOk()->assertJsonPath('store.name', 'Laundry Bintang');
        $previous = StoreSetting::findOrFail(1)->logo_path;
        $this->postJson('/api/v1/settings', [...$this->settings(), 'logo_base64' => base64_encode('not an image')])
            ->assertUnprocessable();
        $this->assertSame($previous, StoreSetting::findOrFail(1)->logo_path);
        $this->postJson('/api/v1/settings', [...$this->settings(), 'templates' => ['WA_DITERIMA' => 'Hi']])
            ->assertUnprocessable();
        $this->postJson('/api/v1/settings', [...$this->settings(), 'remove_logo' => true])
            ->assertOk()->assertJsonPath('store.logo_data', '');
        Storage::disk('public')->assertMissing($previous);
        $cashier = User::factory()->create(['role' => 'cashier']);
        Sanctum::actingAs($cashier);
        $this->postJson('/api/v1/settings', $this->settings())->assertForbidden();
        $this->getJson('/api/v1/sync/pull')->assertOk()
            ->assertJsonPath('store.name', 'Laundry Bintang')
            ->assertJsonPath('store.templates.WA_SELESAI', StoreConfiguration::TEMPLATES['WA_SELESAI']);
        $this->actingAs($cashier, 'web')->get('/settings')->assertForbidden();
    }

    public function test_receipt_seed_fills_only_missing_fields_and_preserves_owner_settings(): void
    {
        Storage::fake('public');
        $this->seed(StoreSettingsSeeder::class);
        $store = StoreSetting::findOrFail(1);
        $this->assertStringContainsString('contoh', $store->address);
        $this->assertNotEmpty($store->receipt_terms);
        Storage::disk('public')->assertExists($store->logo_path);
        $customLogo = UploadedFile::fake()->image('owner.png')->store('store/logos', 'public');
        $store->update(['name' => 'Toko Owner', 'address' => 'Alamat Owner', 'phone' => '0812345678',
            'receipt_terms' => 'Ketentuan Owner', 'logo_path' => $customLogo]);
        $this->seed(StoreSettingsSeeder::class);
        $this->assertSame('Toko Owner', $store->fresh()->name);
        $this->assertSame('Alamat Owner', $store->fresh()->address);
        $this->assertSame('0812345678', $store->fresh()->phone);
        $this->assertSame('Ketentuan Owner', $store->fresh()->receipt_terms);
        $this->assertSame($customLogo, $store->fresh()->logo_path);
        $this->assertDatabaseCount('store_settings', 1);
    }
}
