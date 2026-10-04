<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\DeviceSyncState;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_and_export_require_an_active_owner_web_session(): void
    {
        $routes = ['/dashboard', '/services', '/users', '/transactions', '/attendances', '/reports/export'];
        foreach ($routes as $route) {
            $this->get($route)->assertRedirect('/login');
        }

        $cashier = User::factory()->create();
        $this->actingAs($cashier);
        foreach ($routes as $route) {
            $this->get($route)->assertForbidden();
        }
        $this->post('/services', [])->assertForbidden();
        $this->post('/users', [])->assertForbidden();

        $owner = User::factory()->owner()->create();
        $this->actingAs($owner);
        foreach ($routes as $route) {
            $this->get($route)->assertOk();
        }
        $owner->update(['active' => false]);
        $this->get('/reports/export')->assertForbidden();
        $this->get('/dashboard')->assertForbidden();
    }

    public function test_even_an_owner_api_token_cannot_export_without_a_browser_session(): void
    {
        $owner = User::factory()->owner()->create();
        $token = $owner->createToken('android')->plainTextToken;

        $this->withToken($token)->getJson('/reports/export')->assertUnauthorized();
    }

    public function test_populated_dashboard_renders_notifications_and_keeps_other_branch_data_out(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        $owner = User::factory()->owner()->create(['last_login_at' => now(), 'notify_login' => true]);
        $cashier = User::factory()->create();
        Branch::findOrFail(1)->update(['opening_hours' => [
            ['day' => today()->dayOfWeekIso, 'open' => true, 'from' => '08:00', 'to' => '18:00'],
        ]]);
        $paid = $this->transaction($cashier, 'Pelanggan hari ini', 'DITERIMA', 'LUNAS', today()->setTime(9, 0)->toDateTimeString());
        $paid->update(['total' => 7000, 'subtotal' => 7000]);
        $overdue = $this->transaction($cashier, 'Pelanggan terlambat ambil', 'SIAP_DIAMBIL', 'BELUM', today()->subDays(5)->toDateTimeString());
        $overdue->update(['ready_at' => today()->subDays(4)]);
        Attendance::create(['user_id' => $cashier->id, 'check_in_time' => today()->setTime(9, 0)]);
        DeviceSyncState::create([
            'user_id' => $cashier->id, 'device_id' => (string) Str::uuid(), 'name' => 'Tablet perlu sinkron',
            'app_version' => '1.6.1', 'pending_count' => 2, 'failed_count' => 1, 'last_seen_at' => now(),
        ]);
        $otherBranch = Branch::create(['name' => 'Cabang lain', 'code' => 'ML-OTHER']);
        $otherCustomer = Customer::create(['branch_id' => $otherBranch->id, 'name' => 'Pelanggan cabang lain', 'phone' => '6281234567891']);
        Transaction::create([
            'branch_id' => $otherBranch->id, 'customer_uuid' => $otherCustomer->uuid,
            'total' => 999000, 'subtotal' => 999000, 'payment_status' => 'LUNAS', 'laundry_status' => 'DITERIMA',
        ]);
        DeviceSyncState::create([
            'branch_id' => $otherBranch->id, 'user_id' => $cashier->id, 'device_id' => (string) Str::uuid(),
            'name' => 'Tablet cabang lain', 'app_version' => '1.6.1', 'pending_count' => 3, 'last_seen_at' => now(),
        ]);

        $this->actingAs($owner)->get('/dashboard')->assertOk()
            ->assertViewHas('stats', fn (array $stats) => (int) $stats['today_omzet'] === 7000
                && $stats['today_transactions_count'] === 1 && $stats['active_laundry_count'] === 2)
            ->assertViewHas('overdue', 1)
            ->assertSee('Pelanggan hari ini')->assertSee('Pelanggan terlambat ambil')->assertSee('Rp7.000')
            ->assertSee('Tablet perlu sinkron')->assertSee('1 data gagal disinkronkan.')
            ->assertSee('masuk setelah jam buka cabang.')->assertSee('Login akun '.$owner->username.' berhasil.')
            ->assertDontSee('Pelanggan cabang lain')->assertDontSee('Tablet cabang lain')->assertDontSee('Rp999.000')
            ->assertSee('class="unread-dot"', false)
            ->assertDontSee('b3bda.svg', false);
        $this->post('/notifications/read')->assertRedirect();
        $this->get('/dashboard')->assertOk()->assertDontSee('class="unread-dot"', false);
        $owner->forceFill(['last_login_at' => now()->addSecond()])->save();
        $this->actingAs($owner->fresh())->get('/dashboard')->assertOk()->assertSee('class="unread-dot"', false);
        $this->travelBack();
    }

    public function test_cashier_web_login_is_rejected_but_owner_can_sign_in_and_out(): void
    {
        $cashier = User::factory()->create();
        $this->from('/login')->post('/login', [
            'username' => $cashier->username, 'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('username');
        $this->assertGuest();

        $owner = User::factory()->owner()->create();
        $this->post('/login', [
            'username' => $owner->username, 'password' => 'password',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($owner);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_owner_can_edit_service_and_validation_errors_are_visible(): void
    {
        $this->actingAs(User::factory()->owner()->create());
        $this->post('/services', ['name' => 'Cuci', 'unit' => 'kg', 'price' => 7000, 'speed' => 'REGULER', 'duration_hours' => 48])->assertSessionHasNoErrors();
        $service = Service::firstOrFail();

        $this->post('/services/'.$service->uuid, [
            'name' => 'Cuci Express', 'unit' => 'pcs', 'price' => 12000, 'is_active' => 0, 'speed' => 'EXPRESS', 'duration_hours' => 24,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('services', ['uuid' => $service->uuid, 'name' => 'Cuci Express', 'price' => 12000, 'is_active' => 0]);
        $this->get('/services')->assertOk()->assertSee('Edit layanan')->assertSee('Cuci Express');

        $this->from('/services')->post('/services', [
            'name' => 'Cuci Baru', 'unit' => 'kg', 'price' => 12.5, 'speed' => 'REGULER', 'duration_hours' => 48, '_workspace_dialog' => 'service-create',
        ])->assertRedirect('/services')->assertSessionHasErrors('price');
        $priceError = session('errors')->first('price');
        $this->get('/services')->assertSee($priceError)->assertSee('Cuci Baru')
            ->assertSee('data-reopen-dialog="service-create"', false);
        $this->assertDatabaseCount('services', 1);
    }

    public function test_disabled_owner_can_return_to_login_to_switch_accounts(): void
    {
        $owner = User::factory()->owner()->create();
        $this->actingAs($owner)->get('/dashboard')->assertOk();
        $owner->update(['active' => false]);
        $this->get('/dashboard')->assertForbidden();
        $this->get('/login')->assertOk()->assertSee('Masuk ke Malah Laundry');
        $this->assertGuest();
    }

    public function test_zero_is_a_valid_search_for_both_history_and_export(): void
    {
        $owner = User::factory()->owner()->create();
        $included = $this->transaction($owner, 'Nama A', 'DITERIMA', 'BELUM', '2026-09-15');
        $included->update(['transaction_number' => 'TRX-A']);
        $included->customer->update(['phone' => '022']);
        $excluded = $this->transaction($owner, 'Nama B', 'DITERIMA', 'BELUM', '2026-09-15');
        $excluded->update(['transaction_number' => 'TRX-B']);
        $excluded->customer->update(['phone' => '111']);
        $this->actingAs($owner)->get('/transactions?q=0')->assertOk()->assertSee('TRX-A')->assertDontSee('TRX-B');
        $csv = $this->worksheet($this->get('/reports/export?q=0')->assertOk());
        $this->assertStringContainsString('TRX-A', $csv);
        $this->assertStringNotContainsString('TRX-B', $csv);
    }

    public function test_deactivating_a_user_revokes_tokens_and_owner_cannot_disable_self(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->create();
        $cashier->createToken('android');
        $this->actingAs($owner)->post('/users/'.$cashier->id.'/toggle')->assertSessionHas('success');
        $this->assertFalse($cashier->fresh()->active);
        $this->assertSame(0, $cashier->tokens()->count());
        $this->post('/users/'.$owner->id.'/toggle')->assertSessionHas('error');
        $this->assertTrue($owner->fresh()->active);
    }

    public function test_export_applies_same_filters_as_history_and_escapes_spreadsheet_formulas(): void
    {
        $owner = User::factory()->owner()->create();
        $included = $this->transaction($owner, '=1+1', 'SELESAI', 'LUNAS', '2026-09-15 10:00:00');
        $excluded = $this->transaction($owner, 'Pelanggan Lain', 'DITERIMA', 'BELUM', '2026-09-14 10:00:00');
        $query = http_build_query(['status' => 'SELESAI', 'payment' => 'LUNAS', 'from' => '2026-09-15', 'to' => '2026-09-15']);

        $this->actingAs($owner)->get('/transactions?'.$query)
            ->assertOk()->assertSee($included->transaction_number)->assertDontSee($excluded->transaction_number);
        $response = $this->get('/reports/export?'.$query)->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $csv = $this->worksheet($response);
        $this->assertStringContainsString($included->transaction_number, $csv);
        $this->assertStringNotContainsString($excluded->transaction_number, $csv);
        $this->assertStringContainsString('>=1+1</t>', $csv);
        $this->assertStringNotContainsString('<f>', $csv);
        $this->assertStringContainsString($included->public_receipt_url, $csv);
    }

    public function test_search_and_filter_pagination_preserve_query_parameters(): void
    {
        $owner = User::factory()->owner()->create();
        for ($index = 0; $index < 16; $index++) {
            $this->transaction($owner, 'Pencarian '.$index, 'DITERIMA', 'BELUM', '2026-09-15 10:00:00');
        }
        $this->actingAs($owner)->get('/transactions?q=Pencarian&payment=BELUM')
            ->assertOk()->assertSee('Halaman 1 dari 2')
            ->assertSee('q=Pencarian&amp;payment=BELUM&amp;page=2', false);
        $this->get('/transactions?q=Pencarian&payment=BELUM&page=2')
            ->assertViewHas('transactions', fn ($transactions) => $transactions->count() === 1);
    }

    public function test_invalid_date_range_is_rejected_for_history_and_export(): void
    {
        $this->actingAs(User::factory()->owner()->create());
        foreach (['/transactions', '/reports/export'] as $route) {
            $this->getJson($route.'?from=2026-09-20&to=2026-09-01')->assertUnprocessable()->assertJsonValidationErrors('to');
            $this->getJson($route.'?status=UNKNOWN')->assertUnprocessable()->assertJsonValidationErrors('status');
        }
    }

    public function test_demo_seed_can_be_repeated_without_changing_existing_ids_or_passwords(): void
    {
        $this->seed();
        $service = Service::where('name', 'Cuci Komplit Reguler')->firstOrFail();
        $service->update(['price' => 9000]);
        User::where('username', 'owner')->firstOrFail()->update(['password' => 'new-password']);

        $this->seed();

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertDatabaseCount('services', 4);
        $this->assertDatabaseHas('services', ['uuid' => $service->uuid, 'price' => 9000]);
        $this->assertTrue(Hash::check('new-password', User::where('username', 'owner')->firstOrFail()->password));
    }

    private function transaction(User $user, string $name, string $status, string $payment, string $date): Transaction
    {
        $customer = Customer::create(['name' => $name, 'phone' => '081234567890']);
        $transaction = new Transaction([
            'customer_uuid' => $customer->uuid, 'user_id' => $user->id,
            'total' => 21000, 'subtotal' => 21000, 'laundry_status' => $status, 'payment_status' => $payment,
        ]);
        $transaction->created_at = $date;
        $transaction->save();

        return $transaction;
    }

    private function worksheet($response): string
    {
        $file = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($file));
        try {
            foreach (['[Content_Types].xml', 'xl/workbook.xml', 'xl/styles.xml', 'xl/worksheets/sheet1.xml'] as $name) {
                $document = new \DOMDocument();
                $this->assertTrue($document->loadXML($zip->getFromName($name)));
            }
            return $zip->getFromName('xl/worksheets/sheet1.xml');
        } finally {
            $zip->close();
            unlink($file);
        }
    }
}
