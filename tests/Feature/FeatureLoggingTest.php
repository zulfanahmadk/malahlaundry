<?php

namespace Tests\Feature;

use App\Logging\FeatureLog;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class FeatureLoggingTest extends TestCase
{
    private string $logDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logDirectory = sys_get_temp_dir().'/malah-feature-logs-'.Str::uuid();
        config(['logging.default' => 'single', 'logging.channels.single.directory' => $this->logDirectory]);
        foreach (FeatureLog::features() as $feature) {
            config(['logging.channels.'.$feature.'.directory' => $this->logDirectory]);
        }
        Log::forgetChannel('single');
    }

    protected function tearDown(): void
    {
        Log::getLogger()->close();
        (new Filesystem())->deleteDirectory($this->logDirectory);
        parent::tearDown();
    }

    public function test_cached_logger_routes_each_request_and_keeps_request_input_out_of_logs(): void
    {
        Route::post('/transactions/log-test', function () {
            Log::info('Transaksi diperiksa');
            return response()->json(['ok' => true]);
        })->name('transactions.log-test');
        Route::post('/message-templates/log-test', fn () => response()->json(['ok' => true]))->name('templates.log-test');

        $response = $this->postJson('/transactions/log-test', ['password' => 'RAHASIA-PASSWORD', 'token' => 'RAHASIA-TOKEN', 'phone' => 'RAHASIA-TELEPON']);
        $response->assertOk()->assertHeader('X-Request-ID');
        $this->postJson('/message-templates/log-test', [])->assertOk();
        $transaction = $this->contents('transaksi');
        $templates = $this->contents('template-whatsapp');
        $this->assertStringContainsString('Transaksi diperiksa', $transaction);
        $this->assertStringContainsString($response->headers->get('X-Request-ID'), $transaction);
        $this->assertStringContainsString('Permintaan fitur selesai', $templates);
        $this->assertStringContainsString('"status":200', $transaction);
        $this->assertStringNotContainsString('Transaksi diperiksa', $templates);
        foreach (['RAHASIA-PASSWORD', 'RAHASIA-TOKEN', 'RAHASIA-TELEPON'] as $secret) {
            $this->assertStringNotContainsString($secret, $transaction);
        }
    }

    public function test_unhandled_errors_and_auth_failures_go_to_their_feature_files(): void
    {
        Route::get('/api/v1/sync/log-test', fn () => throw new RuntimeException('Sinkronisasi uji gagal'));
        $this->getJson('/api/v1/sync/log-test')->assertStatus(500);
        $this->getJson('/api/v1/auth/user')->assertUnauthorized();
        $this->assertStringContainsString('Sinkronisasi uji gagal', $this->contents('sinkronisasi'));
        $this->assertStringContainsString('"status":401', $this->contents('autentikasi'));
    }

    public function test_console_explicit_channels_and_unknown_feature_use_safe_filenames(): void
    {
        app()->instance('request', Request::create('/'));
        Log::info('Pekerjaan CLI');
        Log::channel('transaksi')->warning('Pemeriksaan transaksi');
        Log::info('Fitur tidak dikenal', ['feature' => '../../secret']);
        $this->assertStringContainsString('Pekerjaan CLI', $this->contents('aplikasi'));
        $this->assertStringContainsString('Fitur tidak dikenal', $this->contents('aplikasi'));
        $this->assertStringContainsString('Pemeriksaan transaksi', $this->contents('transaksi'));
        Log::channel('transaksi')->getLogger()->close();
    }

    public function test_web_and_api_features_share_names(): void
    {
        foreach ([
            '/api/v1/auth/profile' => 'profil', '/n/receipt-id' => 'nota',
            '/api/v1/sync/push' => 'sinkronisasi', '/opening-hours' => 'jam-buka',
            '/reports/export' => 'laporan', '/api/v1/settings' => 'pengaturan-toko',
            '/admin' => 'admin-sistem', '/admin/apk' => 'versi-apk', '/admin/password' => 'autentikasi',
        ] as $uri => $feature) {
            $this->assertSame($feature, FeatureLog::resolve(Request::create($uri)));
        }
    }

    private function contents(string $feature): string
    {
        $files = glob($this->logDirectory.'/'.$feature.'-*.log');
        $this->assertCount(1, $files);
        return file_get_contents($files[0]);
    }
}
