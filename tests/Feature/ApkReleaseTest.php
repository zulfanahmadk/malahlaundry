<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ApkReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seeder_is_repeatable_and_owner_cannot_manage_admin_or_releases(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = User::where('role', 'admin')->firstOrFail();
        $hash = $admin->password;
        $this->seed(AdminSeeder::class);
        $this->assertSame($hash, $admin->fresh()->password);
        $owner = User::factory()->owner()->create();
        $this->actingAs($owner)->get('/admin/apk')->assertForbidden();
        $this->post('/admin/apk', [])->assertForbidden();
        $this->post('/users/'.$admin->id.'/toggle')->assertForbidden();
        $this->post('/users/'.$admin->id, [])->assertForbidden();
        $this->get('/users/'.$admin->id.'/edit')->assertForbidden();
        $this->actingAs($admin)->get('/dashboard')->assertForbidden();
        $this->get('/admin/apk')->assertOk()->assertSee('Upload APK baru');
    }

    public function test_admin_login_redirects_to_apk_page_and_is_rejected_by_android_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->post('/login', ['username' => $admin->username, 'password' => 'password'])->assertRedirect('/admin/apk');
        $this->post('/logout')->assertRedirect('/login');
        $this->postJson('/api/v1/auth/login', ['username' => $admin->username, 'password' => 'password'])->assertForbidden();
        $this->assertSame(0, $admin->tokens()->count());
    }

    public function test_upload_reads_actual_apk_identity_publishes_metadata_and_serves_identical_file(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $apk = $this->apk(10, '1.7.0');
        $hash = hash_file('sha256', $apk->getRealPath());
        $this->actingAs($admin)->from('/admin/apk')->post('/admin/apk', ['apk' => $apk, 'notes' => 'Perbaikan kasir'])->assertRedirect('/admin/apk')->assertSessionHasNoErrors();
        $release = DB::table('apk_releases')->first();
        $this->assertSame($hash, $release->sha256);
        $this->getJson('/api/v1/app-release?package=com.malahlaundry.app')->assertOk()
            ->assertJsonPath('release.version_code', 10)->assertJsonPath('release.version_name', '1.7.0')
            ->assertJsonPath('release.sha256', $hash)->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/api/v1/app-release?package=com.malahlaundry.app.qa')->assertOk()->assertJsonPath('release', null);
        $this->get('/api/v1/app-releases/'.$release->id.'/download')->assertOk()->assertDownload('MalahLaundry-10.apk');
        $this->post('/admin/apk', ['apk' => $this->apk(9, '1.6.2')])->assertSessionHasErrors('apk');
        $this->assertDatabaseCount('apk_releases', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles('apk-releases'));
    }

    public function test_invalid_apk_and_wrong_package_are_rejected_without_publishing(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/apk', ['apk' => UploadedFile::fake()->createWithContent('bad.apk', 'not an apk')])->assertSessionHasErrors('apk');
        $this->post('/admin/apk', ['apk' => $this->apk(10, '1.7.0', 'org.other.app')])->assertSessionHasErrors('apk');
        $this->assertDatabaseCount('apk_releases', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles('apk-releases'));
    }

    public function test_owner_can_check_latest_main_apk_and_download_but_cannot_upload_or_download_qa_from_owner_page(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->owner()->create();
        $this->actingAs($admin)->post('/admin/apk', ['apk' => $this->apk(10, '1.7.0')])->assertSessionHasNoErrors();
        $old = DB::table('apk_releases')->where('package_name', 'com.malahlaundry.app')->first();
        $this->post('/admin/apk', ['apk' => $this->apk(11, '1.7.1'), 'notes' => 'Perbaikan transaksi offline'])->assertSessionHasNoErrors();
        $this->post('/admin/apk', ['apk' => $this->apk(99, '9.9.9-qa', 'com.malahlaundry.app.qa')])->assertSessionHasNoErrors();
        $main = DB::table('apk_releases')->where('package_name', 'com.malahlaundry.app')->orderByDesc('version_code')->first();
        $qa = DB::table('apk_releases')->where('package_name', 'com.malahlaundry.app.qa')->first();
        $this->actingAs($owner)->get('/apk')->assertOk()->assertSee('APK Android')->assertSee('Cek versi terbaru')
            ->assertSee('Unduh APK 1.7.1')->assertSee('Perbaikan transaksi offline')
            ->assertDontSee('Unduh APK 1.7.0')->assertDontSee('9.9.9-qa')->assertDontSee('Upload APK baru');
        $this->get('/apk/'.$main->id.'/download')->assertOk()->assertDownload('MalahLaundry-11.apk');
        $this->get('/apk/'.$qa->id.'/download')->assertNotFound();
        $this->post('/admin/apk', [])->assertForbidden();
        Storage::disk('local')->delete($main->path);
        $this->get('/apk')->assertOk()->assertSee('File APK belum tersedia.')->assertDontSee('Unduh APK 1.7.1');
        $this->get('/apk/'.$main->id.'/download')->assertNotFound();
        $this->assertNotNull($old);
    }

    public function test_owner_apk_page_handles_empty_releases_and_requires_owner_session(): void
    {
        $this->get('/apk')->assertRedirect('/login');
        $this->get('/apk/1/download')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/apk')->assertForbidden();
        $this->get('/apk/1/download')->assertForbidden();
        $this->actingAs(User::factory()->owner()->create())->get('/apk')->assertOk()
            ->assertSee('Admin belum menerbitkan APK untuk aplikasi toko.')->assertDontSee('Unduh APK');
    }

    private function apk(int $code, string $version, string $package = 'com.malahlaundry.app'): UploadedFile
    {
        $strings = ['manifest', 'package', 'versionCode', 'versionName', $package, $version];
        $offsets = '';
        $data = '';
        foreach ($strings as $string) {
            $offsets .= pack('V', strlen($data));
            $data .= chr(strlen($string)).chr(strlen($string)).$string."\0";
        }
        $pool = pack('vvVVVVVV', 1, 28, 28 + strlen($offsets) + strlen($data), count($strings), 0, 0x100, 28 + strlen($offsets), 0).$offsets.$data;
        $attributes = '';
        foreach ([[1, 3, 4], [2, 0x10, $code], [3, 3, 5]] as [$name, $type, $value]) {
            $attributes .= pack('VVVvCCV', 0xffffffff, $name, 0xffffffff, 8, 0, $type, $value);
        }
        $element = pack('vvVVVVVvvvvvv', 0x102, 16, 36 + strlen($attributes), 1, 0xffffffff, 0xffffffff, 0, 20, 20, 3, 0, 0, 0).$attributes;
        $xml = pack('vvV', 3, 8, 8 + strlen($pool) + strlen($element)).$pool.$element;
        $path = tempnam(sys_get_temp_dir(), 'apk-test-');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('AndroidManifest.xml', $xml);
        $zip->addFromString('classes.dex', 'test dex');
        $zip->close();
        return new UploadedFile($path, 'MalahLaundry.apk', 'application/vnd.android.package-archive', null, true);
    }
}
