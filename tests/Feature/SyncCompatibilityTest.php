<?php

namespace Tests\Feature;

use App\Http\Middleware\SelectBranch;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeviceSyncState;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SyncCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_attendance_with_matching_branch_is_saved_and_acknowledged_on_every_retry(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $uuid = (string) Str::uuid();
        $payload = ['attendances' => [[
            'uuid' => strtoupper($uuid), 'branch_id' => $user->branch_id,
            'user_id' => $user->id, 'device_id' => 'tablet-kasir',
            'check_in_time' => '2026-10-03T01:00:00Z',
        ]]];

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->withHeader('X-Branch-Id', '1')->postJson('/api/v1/sync/push', $payload)
                ->assertOk()->assertJsonPath('acknowledged.attendances', [$uuid]);
        }
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', ['uuid' => $uuid, 'branch_id' => 1, 'user_id' => $user->id]);
    }

    public function test_different_record_branch_is_rejected_without_silently_moving_the_attendance(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $response = $this->postJson('/api/v1/sync/push', ['attendances' => [[
            'uuid' => (string) Str::uuid(), 'branch_id' => 2, 'check_in_time' => now()->toIso8601String(),
        ]]])->assertUnprocessable()->assertJsonValidationErrors('attendances.0.branch_id');
        $this->assertSame('Cabang presensi berbeda dari cabang aktif (1). Data tetap tersimpan di perangkat.', $response->json('errors')['attendances.0.branch_id'][0]);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_missing_branch_middleware_returns_a_clear_validation_error_without_writing(): void
    {
        $this->withoutMiddleware(SelectBranch::class);
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/sync/push', ['attendances' => [[
            'uuid' => (string) Str::uuid(), 'branch_id' => 1, 'check_in_time' => now()->toIso8601String(),
        ]]])->assertUnprocessable()->assertJsonValidationErrors('server');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_cashier_may_resend_archival_metadata_without_changing_archive_state(): void
    {
        $customer = Customer::create([
            'name' => 'Pelanggan arsip', 'phone' => '6281234567890', 'archived_at' => '2026-09-30 12:00:00',
        ]);
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/sync/push', ['customers' => [[
            'uuid' => $customer->uuid, 'branch_id' => 1, 'address' => 'Alamat baru',
            'archived_at' => $customer->archived_at->toIso8601String(),
            'created_at' => now()->toIso8601String(), 'updated_at' => now()->toIso8601String(),
            'transactions_count' => 10,
        ]]])->assertOk()->assertJsonPath('acknowledged.customers', [$customer->uuid]);
        $this->assertSame('Alamat baru', $customer->fresh()->address);
        $this->assertTrue($customer->archived_at->equalTo($customer->fresh()->archived_at));
        $this->postJson('/api/v1/sync/push', ['customers' => [[
            'uuid' => $customer->uuid, 'name' => 'Perubahan dilarang', 'archived_at' => null,
        ]]])->assertForbidden();
        $this->assertSame('Pelanggan arsip', $customer->fresh()->name);
        $this->assertNotNull($customer->fresh()->archived_at);
    }

    public function test_readonly_cached_photo_metadata_cannot_write_a_server_photo_path(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id, 'check_in_time' => '2026-10-03 08:00:00',
            'check_in_photo_path' => 'attendances/real-photo.jpg',
        ]);
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/sync/push', ['attendances' => [[
            'uuid' => $attendance->uuid, 'check_out_time' => '2026-10-03T17:00:00+07:00',
            'check_in_photo_path' => 'attendances/spoof.jpg', 'id' => 999, 'user' => ['role' => 'owner'],
        ]]])->assertOk();
        $this->assertSame('attendances/real-photo.jpg', $attendance->fresh()->check_in_photo_path);
        $this->assertSame($user->id, $attendance->fresh()->user_id);
    }

    public function test_records_have_iso_dates_that_android_can_parse_and_replays_keep_transaction_version(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Pelanggan', 'phone' => '6281234567890', 'archived_at' => '2026-09-30 12:00:00',
        ]);
        $service = Service::create(['name' => 'Cuci', 'unit' => 'kg', 'price' => 7000]);
        $order = Transaction::create([
            'customer_uuid' => $customer->uuid, 'user_id' => $user->id, 'total' => 7000, 'subtotal' => 7000,
            'laundry_status' => 'DITERIMA', 'payment_status' => 'BELUM',
        ]);
        $order->forceFill(['created_at' => '2026-09-29 08:00:00', 'estimated_at' => '2026-10-01 08:00:00'])->save();
        $order->items()->create(['service_uuid' => $service->uuid, 'qty' => 1, 'price' => 7000]);
        Sanctum::actingAs($user);
        $payload = ['transactions' => [[
            'uuid' => $order->uuid, 'laundry_status' => 'SELESAI', 'payment_status' => 'LUNAS',
            'payment_method' => 'TUNAI', 'expected_total' => 7000,
            'ready_at' => '2026-10-01T08:00:00+07:00', 'paid_at' => '2026-10-02T03:00:00Z',
            'picked_up_at' => '2026-10-02T10:00:00+07:00',
        ]]];
        $this->postJson('/api/v1/sync/push', $payload)->assertOk();
        $this->assertSame(2, $order->fresh()->version);
        $this->postJson('/api/v1/sync/push', $payload)->assertOk()->assertJsonPath('acknowledged.transactions', [$order->uuid]);
        $this->assertSame(2, $order->fresh()->version);
        $record = $this->getJson('/api/v1/sync/records?type=transactions&q='.$order->uuid)->assertOk()->json('data.0');
        foreach (['created_at', 'estimated_at', 'ready_at', 'paid_at', 'picked_up_at'] as $field) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/', $record[$field]);
        }
        $this->getJson('/api/v1/sync/records?type=customers')->assertOk()
            ->assertJsonPath('data.0.archived_at', '2026-09-30T12:00:00+07:00');
    }

    public function test_unknown_customer_is_a_retryable_validation_error_and_rolls_back_the_batch(): void
    {
        Sanctum::actingAs(User::factory()->owner()->create());
        $this->postJson('/api/v1/sync/push', [
            'services' => [['uuid' => (string) Str::uuid(), 'name' => 'Layanan', 'price' => 7000]],
            'transactions' => [[
                'uuid' => (string) Str::uuid(), 'customer_uuid' => (string) Str::uuid(),
                'items' => [['service_uuid' => (string) Str::uuid(), 'qty' => 1, 'price' => 7000]],
            ]],
        ])->assertUnprocessable()->assertJsonValidationErrors('transactions.0.customer_uuid');
        $this->assertDatabaseCount('services', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_customer_reference_uuid_is_case_insensitive_when_creating_an_offline_order(): void
    {
        $customer = Customer::create(['name' => 'Pelanggan', 'phone' => '6281234567890']);
        $service = Service::create(['name' => 'Cuci', 'unit' => 'kg', 'price' => 7000]);
        Sanctum::actingAs(User::factory()->create());
        $uuid = (string) Str::uuid();
        $this->postJson('/api/v1/sync/push', ['transactions' => [[
            'uuid' => strtoupper($uuid), 'customer_uuid' => strtoupper($customer->uuid),
            'items' => [['service_uuid' => strtoupper($service->uuid), 'qty' => 1, 'price' => 7000]],
        ]]])->assertOk();
        $this->assertDatabaseHas('transactions', ['uuid' => $uuid, 'customer_uuid' => $customer->uuid]);
    }

    public function test_device_is_only_reported_as_synced_after_a_completed_empty_queue(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $uuid = (string) Str::uuid();
        $payload = ['device_id' => $uuid, 'name' => 'Tablet', 'app_version' => '1.0', 'pending_count' => 1, 'failed_count' => 0, 'completed' => true];
        $this->postJson('/api/v1/sync/device', $payload)->assertOk();
        $state = DeviceSyncState::where('device_id', $uuid)->firstOrFail();
        $this->assertNull($state->last_synced_at);
        $this->postJson('/api/v1/sync/device', [...$payload, 'pending_count' => 0, 'completed' => false])->assertOk();
        $this->assertNull($state->fresh()->last_synced_at);
        $this->postJson('/api/v1/sync/device', [...$payload, 'pending_count' => 0])->assertOk();
        $this->assertNotNull($state->fresh()->last_synced_at);
        $this->assertDatabaseCount('device_sync_states', 1);
    }

    public function test_cashier_cannot_select_another_branch_to_upload_or_read_records(): void
    {
        $branch = Branch::create(['name' => 'Cabang kedua', 'code' => 'ML-002']);
        Sanctum::actingAs(User::factory()->create());
        $this->withHeader('X-Branch-Id', (string) $branch->id)->postJson('/api/v1/sync/push', [])->assertForbidden();
        $this->getJson('/api/v1/sync/records?type=customers')->assertForbidden();
    }

    public function test_offline_order_recovery_requires_existing_branch_services_and_preserves_original_prices(): void
    {
        $customer = Customer::create(['name' => 'Pelanggan offline', 'phone' => '6281234567890']);
        $pieceService = Service::create(['name' => 'Layanan satuan baru', 'unit' => 'pcs', 'price' => 45000]);
        $weightService = Service::create(['name' => 'Layanan kg nonaktif', 'unit' => 'kg', 'price' => 7000, 'is_active' => false]);
        $otherBranch = Branch::create(['name' => 'Cabang lain', 'code' => 'ML-OTHER']);
        $foreignService = Service::create(['branch_id' => $otherBranch->id, 'name' => 'Layanan cabang lain', 'unit' => 'pcs', 'price' => 25000]);
        Sanctum::actingAs(User::factory()->create());
        $uuid = (string) Str::uuid();
        $payload = ['transactions' => [[
            'uuid' => $uuid, 'customer_uuid' => $customer->uuid, 'branch_id' => 1,
            'laundry_status' => 'DITERIMA', 'payment_status' => 'BELUM', 'expected_total' => 155000,
            'items' => [
                ['service_uuid' => (string) Str::uuid(), 'service_name' => 'Nama satuan saat diterima', 'unit' => 'pcs', 'qty' => 5, 'price' => 25000],
                ['service_uuid' => (string) Str::uuid(), 'service_name' => 'Nama kg saat diterima', 'unit' => 'kg', 'qty' => 6, 'price' => 5000],
            ],
        ]]];

        $this->postJson('/api/v1/sync/push', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors(['transactions.0.items.0.service_uuid', 'transactions.0.items.1.service_uuid']);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);

        $payload['transactions'][0]['items'][0]['service_uuid'] = $foreignService->uuid;
        $payload['transactions'][0]['items'][1]['service_uuid'] = $weightService->uuid;
        $this->postJson('/api/v1/sync/push', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors('transactions.0.items.0.service_uuid');
        $this->assertDatabaseCount('transactions', 0);

        $payload['transactions'][0]['items'][0]['service_uuid'] = $pieceService->uuid;
        $this->postJson('/api/v1/sync/push', $payload)->assertOk()->assertJsonPath('acknowledged.transactions', [$uuid]);
        $this->postJson('/api/v1/sync/push', $payload)->assertOk();
        $this->assertDatabaseHas('transactions', ['uuid' => $uuid, 'branch_id' => 1, 'total' => 155000, 'subtotal' => 155000]);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('transaction_items', 2);
        $this->assertDatabaseHas('transaction_items', [
            'transaction_uuid' => $uuid, 'service_uuid' => $pieceService->uuid,
            'service_name' => 'Nama satuan saat diterima', 'unit' => 'pcs', 'qty' => 5, 'price' => 25000,
        ]);
        $this->assertDatabaseHas('transaction_items', [
            'transaction_uuid' => $uuid, 'service_uuid' => $weightService->uuid,
            'service_name' => 'Nama kg saat diterima', 'unit' => 'kg', 'qty' => 6, 'price' => 5000,
        ]);
    }
}
