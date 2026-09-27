<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        $this->post('/services', ['name' => 'Cuci', 'unit' => 'kg', 'price' => 7000])->assertSessionHasNoErrors();
        $service = Service::firstOrFail();

        $this->post('/services/'.$service->uuid, [
            'name' => 'Cuci Express', 'unit' => 'pcs', 'price' => 12000, 'is_active' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('services', ['uuid' => $service->uuid, 'name' => 'Cuci Express', 'price' => 12000, 'is_active' => 0]);
        $this->get('/services')->assertOk()->assertSee('Edit Layanan')->assertSee('Cuci Express');

        $this->from('/services')->post('/services', [
            'name' => 'Cuci Baru', 'unit' => 'kg', 'price' => 12.5,
        ])->assertRedirect('/services')->assertSessionHasErrors('price');
        $this->get('/services')->assertSee('Periksa kembali data yang diisi.')->assertSee('Cuci Baru');
        $this->assertDatabaseCount('services', 1);
    }

    public function test_disabled_owner_can_return_to_login_to_switch_accounts(): void
    {
        $owner = User::factory()->owner()->create();
        $this->actingAs($owner)->get('/dashboard')->assertOk();
        $owner->update(['active' => false]);
        $this->get('/dashboard')->assertForbidden();
        $this->get('/login')->assertOk()->assertSee('Masuk ke Web Dashboard Owner');
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
        $csv = $this->get('/reports/export?q=0')->assertOk()->streamedContent();
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
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString($included->transaction_number, $csv);
        $this->assertStringNotContainsString($excluded->transaction_number, $csv);
        $this->assertStringContainsString("'=1+1", $csv);
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

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('transaction_items', 1);
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
}
