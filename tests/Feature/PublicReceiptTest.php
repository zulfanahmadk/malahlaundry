<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Service;
use App\Models\Transaction;
use App\Services\QrCodeService;
use chillerlan\QRCode\Common\GDLuminanceSource;
use chillerlan\QRCode\QRCode;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_is_public_and_shows_historical_prices_without_exposing_contact_details(): void
    {
        $transaction = $this->createTransaction();

        $response = $this->get('/n/'.$transaction->uuid);

        $response->assertOk()
            ->assertSee('Pelanggan Nota')
            ->assertSee('Cuci Setrika')
            ->assertSee('Rp17.500')
            ->assertDontSee('Rp99.000')
            ->assertSee('********7890')
            ->assertDontSee('081234567890')
            ->assertDontSee('Alamat pribadi pelanggan')
            ->assertSee('Cucian Anda siap diambil.')
            ->assertSee('BELUM LUNAS')
            ->assertSee('10 Agt 2026, 09:30')
            ->assertSee('aria-current="step"', false)
            ->assertSee(QrCodeService::generateDataUri($transaction->uuid), false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Referrer-Policy', 'no-referrer');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
    }

    public function test_completed_receipt_confirms_collection_and_payment(): void
    {
        $transaction = $this->createTransaction();
        $transaction->update(['laundry_status' => 'SELESAI', 'payment_status' => 'LUNAS']);

        $this->get('/n/'.$transaction->uuid)
            ->assertOk()
            ->assertSee('Bukti Pengambilan Laundry')
            ->assertSee('Cucian Anda sudah diambil.')
            ->assertSee('LUNAS')
            ->assertDontSee('BELUM LUNAS')
            ->assertDontSee('Cucian Anda siap diambil.');
    }

    public function test_uppercase_uuid_opens_the_same_receipt(): void
    {
        $transaction = $this->createTransaction();

        $this->get('/n/'.strtoupper($transaction->uuid))
            ->assertOk()
            ->assertSee($transaction->transaction_number);
    }

    public function test_missing_receipt_returns_a_helpful_404_without_caching_it(): void
    {
        $response = $this->get('/n/'.Str::uuid());

        $response->assertNotFound()
            ->assertSee('Nota Tidak Ditemukan')
            ->assertSee('nota mungkin belum tersinkronisasi')
            ->assertSee('Coba Lagi')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_receipt_routes_reject_numeric_ids_and_non_v4_uuids(): void
    {
        foreach (['123', 'bukan-uuid', 'f47ac10b-58cc-11cf-a447-001122334455'] as $identifier) {
            $this->get('/n/'.$identifier)->assertNotFound();
        }
    }

    public function test_generated_svg_qr_decodes_to_the_transaction_uuid(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD is needed to decode the generated QR image.');
        }

        $uuid = '9b73b50e-1f43-47fd-a874-85756dd2a872';
        $dataUri = QrCodeService::generateDataUri($uuid);
        $prefix = 'data:image/svg+xml;base64,';
        $this->assertStringStartsWith($prefix, $dataUri);

        $svg = new DOMDocument();
        $this->assertTrue($svg->loadXML(base64_decode(substr($dataUri, strlen($prefix)), true)));
        $viewBox = explode(' ', $svg->documentElement->getAttribute('viewBox'));
        $scale = 6;
        $size = (int) $viewBox[2] * $scale;
        $bitmap = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($bitmap, 255, 255, 255);
        $black = imagecolorallocate($bitmap, 0, 0, 0);
        imagefill($bitmap, 0, 0, $white);

        // Rasterize the actual SVG modules so the decoder verifies the receipt image.
        foreach ($svg->getElementsByTagName('path') as $path) {
            if (! str_contains($path->getAttribute('class'), ' dark ')) {
                continue;
            }

            preg_match_all('/M(\d+) (\d+) h1 v1 h-1Z/', $path->getAttribute('d'), $modules, PREG_SET_ORDER);

            foreach ($modules as $module) {
                $x = (int) $module[1] * $scale;
                $y = (int) $module[2] * $scale;
                imagefilledrectangle($bitmap, $x, $y, $x + $scale - 1, $y + $scale - 1, $black);
            }
        }

        $decoded = (new QRCode())->readFromSource(new GDLuminanceSource($bitmap));
        $this->assertSame($uuid, $decoded->data);
        imagedestroy($bitmap);
    }

    private function createTransaction(): Transaction
    {
        $customer = Customer::create([
            'name' => 'Pelanggan Nota',
            'phone' => '081234567890',
            'address' => 'Alamat pribadi pelanggan',
        ]);
        $service = Service::create([
            'name' => 'Cuci Setrika',
            'unit' => 'kg',
            'price' => 99000,
            'is_active' => true,
        ]);
        $transaction = Transaction::create([
            'customer_uuid' => $customer->uuid,
            'transaction_number' => 'TRX-NOTA-001',
            'subtotal' => 17500,
            'total' => 17500,
            'payment_status' => 'BELUM',
            'laundry_status' => 'SIAP_DIAMBIL',
        ]);
        $transaction->forceFill(['created_at' => '2026-08-10 09:30:00'])->save();
        $transaction->items()->create([
            'service_uuid' => $service->uuid,
            'qty' => 2.5,
            'price' => 7000,
        ]);

        return $transaction;
    }
}
