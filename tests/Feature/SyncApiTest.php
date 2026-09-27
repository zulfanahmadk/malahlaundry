<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SyncApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_requires_an_active_authenticated_user(): void
    {
        $this->getJson('/api/v1/sync/pull')->assertUnauthorized();
        Sanctum::actingAs($this->user(['active' => false]));
        $this->getJson('/api/v1/sync/pull')->assertForbidden();
        $this->postJson('/api/v1/sync/push', [])->assertForbidden();
    }

    public function test_status_only_update_preserves_receipt_items_amounts_and_original_cashier(): void
    {
        $original = $this->user();
        $transaction = $this->transaction($original);
        $itemId = $transaction->items()->first()->id;
        $otherCashier = $this->user();
        Sanctum::actingAs($otherCashier);

        $this->postJson('/api/v1/sync/push', ['transactions' => [[
            'uuid' => $transaction->uuid,
            'user_id' => $otherCashier->id,
            'laundry_status' => 'SIAP_DIAMBIL',
        ]]])->assertOk();

        $transaction->refresh();
        $this->assertSame('SIAP_DIAMBIL', $transaction->laundry_status);
        $this->assertSame($original->id, $transaction->user_id);
        $this->assertSame('TRX-EXISTING', $transaction->transaction_number);
        $this->assertSame(17500, $transaction->total);
        $this->assertSame(17500, $transaction->subtotal);
        $this->assertNotNull($transaction->customer_uuid);
        $this->assertSame($itemId, $transaction->items()->first()->id);
    }

    public function test_batch_creation_is_repeatable_and_computes_totals_from_offline_price_snapshots(): void
    {
        $owner = $this->user(['role' => 'owner']);
        $otherUser = $this->user();
        Sanctum::actingAs($owner);
        $customerUuid = (string) Str::uuid();
        $serviceUuid = (string) Str::uuid();
        $transactionUuid = (string) Str::uuid();
        $payload = [
            'customers' => [['uuid' => $customerUuid, 'name' => 'Pelanggan', 'phone' => '081234567890']],
            'services' => [['uuid' => $serviceUuid, 'name' => 'Cuci', 'unit' => 'kg', 'price' => 9000]],
            'transactions' => [[
                'uuid' => $transactionUuid,
                'customer_uuid' => $customerUuid,
                'user_id' => $otherUser->id,
                'subtotal' => 1,
                'total' => 1,
                'items' => [['service_uuid' => $serviceUuid, 'qty' => 2.5, 'price' => 7000]],
            ]],
        ];

        $this->postJson('/api/v1/sync/push', $payload)->assertOk();
        $this->postJson('/api/v1/sync/push', $payload)->assertOk();

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('transaction_items', 1);
        $this->assertDatabaseHas('transactions', [
            'uuid' => $transactionUuid,
            'user_id' => $owner->id,
            'total' => 17500,
            'subtotal' => 17500,
            'payment_status' => 'BELUM',
            'laundry_status' => 'DITERIMA',
        ]);
        $this->assertDatabaseHas('services', ['uuid' => $serviceUuid, 'price' => 9000]);
    }

    public function test_invalid_reference_rolls_back_the_entire_batch(): void
    {
        Sanctum::actingAs($this->user(['role' => 'owner']));
        $customerUuid = (string) Str::uuid();
        $this->postJson('/api/v1/sync/push', [
            'customers' => [['uuid' => $customerUuid, 'name' => 'Batch', 'phone' => '08123']],
            'services' => [['uuid' => (string) Str::uuid(), 'name' => 'Cuci', 'price' => 7000]],
            'transactions' => [[
                'uuid' => (string) Str::uuid(),
                'customer_uuid' => $customerUuid,
                'items' => [['service_uuid' => (string) Str::uuid(), 'qty' => 1, 'price' => 7000]],
            ]],
        ])->assertUnprocessable()->assertJsonValidationErrors('transactions.0.items.0.service_uuid');

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('services', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_malformed_payloads_and_non_v4_identifiers_are_rejected(): void
    {
        Sanctum::actingAs($this->user());
        foreach ([
            ['customers' => 'not-an-array'],
            ['transactions' => [['uuid' => 'trx-456']]],
            ['transactions' => [['uuid' => '550e8400-e29b-11d4-a716-446655440000']]],
            ['transactions' => [['uuid' => (string) Str::uuid(), 'payment_status' => 'BELUM_LUNAS']]],
            ['transactions' => [['uuid' => (string) Str::uuid(), 'items' => []]]],
            ['customers' => [['uuid' => (string) Str::uuid(), 'name' => 'Missing phone']]],
            ['attendances' => [['uuid' => (string) Str::uuid(), 'check_in_photo_path' => '/spoof.jpg']]],
        ] as $payload) {
            $this->postJson('/api/v1/sync/push', $payload)->assertUnprocessable();
        }
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_duplicate_identifiers_are_rejected_before_writing(): void
    {
        Sanctum::actingAs($this->user());
        $uuid = (string) Str::uuid();
        $this->postJson('/api/v1/sync/push', ['customers' => [
            ['uuid' => $uuid, 'name' => 'A', 'phone' => '08123'],
            ['uuid' => strtoupper($uuid), 'name' => 'B', 'phone' => '08124'],
        ]])->assertUnprocessable()->assertJsonValidationErrors('customers.0.uuid');
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_attendance_checkout_keeps_checkin_device_and_photo(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'device_id' => 'kasir-1',
            'check_in_time' => '2026-09-27 08:00:00',
            'check_in_photo_path' => 'attendances/in.jpg',
        ]);
        $payload = ['attendances' => [[
            'uuid' => $attendance->uuid,
            'check_out_time' => '2026-09-27 17:00:00',
        ]]];
        $this->postJson('/api/v1/sync/push', $payload)->assertOk();
        $this->postJson('/api/v1/sync/push', $payload)->assertOk();

        $attendance->refresh();
        $this->assertSame('2026-09-27 08:00:00', $attendance->check_in_time->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-27 17:00:00', $attendance->check_out_time->format('Y-m-d H:i:s'));
        $this->assertSame('kasir-1', $attendance->device_id);
        $this->assertSame('attendances/in.jpg', $attendance->check_in_photo_path);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_attendance_cannot_impersonate_or_overwrite_another_user(): void
    {
        $otherUser = $this->user();
        $attendance = Attendance::create(['user_id' => $otherUser->id, 'check_in_time' => now()]);
        Sanctum::actingAs($this->user());
        $this->postJson('/api/v1/sync/push', ['attendances' => [[
            'uuid' => (string) Str::uuid(),
            'user_id' => $otherUser->id,
            'check_in_time' => now()->toIso8601String(),
        ]]])->assertUnprocessable()->assertJsonValidationErrors('attendances.0.user_id');
        $this->postJson('/api/v1/sync/push', ['attendances' => [[
            'uuid' => $attendance->uuid,
            'check_out_time' => now()->toIso8601String(),
        ]]])->assertForbidden();
        $this->assertNull($attendance->fresh()->check_out_time);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_checkout_cannot_precede_existing_checkin(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);
        $attendance = Attendance::create(['user_id' => $user->id, 'check_in_time' => '2026-09-27 08:00:00']);
        $this->postJson('/api/v1/sync/push', ['attendances' => [[
            'uuid' => $attendance->uuid,
            'check_out_time' => '2026-09-27 07:59:59',
        ]]])->assertUnprocessable()->assertJsonValidationErrors('attendances.0.check_out_time');
        $this->assertNull($attendance->fresh()->check_out_time);
    }

    public function test_attendance_timestamps_are_normalized_before_storage_and_comparison(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);
        Sanctum::actingAs($this->user());
        $uuid = (string) Str::uuid();
        $this->postJson('/api/v1/sync/push', ['attendances' => [[
            'uuid' => $uuid,
            'check_in_time' => '2026-09-27T08:00:00Z',
            'check_out_time' => '2026-09-27T15:00:00+07:00',
        ]]])->assertOk();
        $this->assertDatabaseHas('attendances', [
            'uuid' => $uuid,
            'check_in_time' => '2026-09-27 15:00:00',
            'check_out_time' => '2026-09-27 15:00:00',
        ]);
    }

    public function test_cashier_cannot_push_master_data(): void
    {
        Sanctum::actingAs($this->user());
        foreach ([
            ['services' => [['uuid' => (string) Str::uuid(), 'name' => 'Gratis', 'price' => 0]]],
            ['users' => [['username' => 'admin-baru', 'name' => 'Admin', 'password' => 'password', 'role' => 'owner']]],
        ] as $payload) {
            $this->postJson('/api/v1/sync/push', $payload)->assertForbidden();
        }
        $this->assertDatabaseCount('services', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_partial_master_updates_preserve_fields_and_deactivation_revokes_tokens(): void
    {
        Sanctum::actingAs($this->user(['role' => 'owner']));
        $user = $this->user();
        $originalPassword = $user->password;
        $user->createToken('Android');
        $service = Service::create(['name' => 'Cuci', 'unit' => 'pcs', 'price' => 17000, 'is_active' => true]);
        $customer = Customer::create(['name' => 'Pelanggan', 'phone' => '081234', 'address' => 'Alamat lama']);
        $this->postJson('/api/v1/sync/push', [
            'users' => [['username' => $user->username, 'active' => false]],
            'services' => [['uuid' => $service->uuid, 'is_active' => false]],
            'customers' => [['uuid' => $customer->uuid, 'address' => null]],
        ])->assertOk();

        $this->assertSame($originalPassword, $user->fresh()->password);
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertFalse($user->fresh()->active);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseHas('services', ['uuid' => $service->uuid, 'price' => 17000, 'unit' => 'pcs', 'is_active' => false]);
        $this->assertDatabaseHas('customers', ['uuid' => $customer->uuid, 'name' => 'Pelanggan', 'phone' => '081234', 'address' => null]);
    }

    public function test_owner_user_creation_hashes_password_and_self_demotion_is_rejected(): void
    {
        $owner = $this->user(['role' => 'owner']);
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/sync/push', ['users' => [[
            'username' => 'cashier-new', 'name' => 'Kasir Baru', 'password' => 'secret123',
        ]]])->assertOk();
        $this->assertTrue(Hash::check('secret123', User::where('username', 'cashier-new')->firstOrFail()->password));
        foreach ([['role' => 'cashier'], ['active' => false]] as $change) {
            $this->postJson('/api/v1/sync/push', ['users' => [[
                'username' => $owner->username, ...$change,
            ]]])->assertUnprocessable();
        }
        $this->assertSame('owner', $owner->fresh()->role);
        $this->assertTrue($owner->fresh()->active);
    }

    public function test_replayed_master_password_does_not_revoke_a_token_unless_password_changes(): void
    {
        Sanctum::actingAs($this->user(['role' => 'owner']));
        $user = $this->user();
        $password = $user->password;
        $user->createToken('Android');
        $this->postJson('/api/v1/sync/push', ['users' => [[
            'username' => $user->username, 'password' => 'password',
        ]]])->assertOk();
        $this->assertSame($password, $user->fresh()->password);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->postJson('/api/v1/sync/push', ['users' => [[
            'username' => $user->username, 'password' => 'new-password',
        ]]])->assertOk();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_paid_transactions_cannot_be_unpaid_and_pickup_requires_payment(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);
        $transaction = $this->transaction($user);
        $this->postJson('/api/v1/sync/push', ['transactions' => [[
            'uuid' => $transaction->uuid, 'laundry_status' => 'SELESAI',
        ]]])->assertUnprocessable();
        $this->postJson('/api/v1/sync/push', ['transactions' => [[
            'uuid' => $transaction->uuid, 'laundry_status' => 'SELESAI', 'payment_status' => 'LUNAS',
        ]]])->assertOk();
        $this->postJson('/api/v1/sync/push', ['transactions' => [[
            'uuid' => $transaction->uuid, 'payment_status' => 'BELUM',
        ]]])->assertUnprocessable();
        $this->assertSame('LUNAS', $transaction->fresh()->payment_status);
    }

    public function test_client_total_alone_cannot_change_a_receipt_and_bad_items_leave_existing_items_intact(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);
        $transaction = $this->transaction($user);
        $itemId = $transaction->items()->first()->id;
        $this->postJson('/api/v1/sync/push', ['transactions' => [[
            'uuid' => $transaction->uuid, 'total' => 0, 'subtotal' => 0,
        ]]])->assertOk();
        $this->postJson('/api/v1/sync/push', ['transactions' => [[
            'uuid' => $transaction->uuid,
            'items' => [['service_uuid' => (string) Str::uuid(), 'qty' => 1, 'price' => 100]],
        ]]])->assertUnprocessable();
        $this->assertSame(17500, $transaction->fresh()->total);
        $this->assertSame($itemId, $transaction->items()->first()->id);
    }

    public function test_stale_status_replay_cannot_regress_workflow_and_rolls_back_the_batch(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);
        $transaction = $this->transaction($user);
        $transaction->update(['laundry_status' => 'SIAP_DIAMBIL']);
        $this->postJson('/api/v1/sync/push', [
            'customers' => [['uuid' => $transaction->customer_uuid, 'name' => 'Stale name']],
            'transactions' => [['uuid' => $transaction->uuid, 'laundry_status' => 'DITERIMA']],
        ])->assertUnprocessable()->assertJsonValidationErrors('transactions.0.laundry_status');
        $this->assertSame('SIAP_DIAMBIL', $transaction->fresh()->laundry_status);
        $this->assertSame('Pelanggan', $transaction->customer->name);
    }

    public function test_paid_item_snapshot_is_immutable_but_identical_retries_succeed(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);
        $transaction = $this->transaction($user);
        $transaction->update(['payment_status' => 'LUNAS']);
        $item = $transaction->items()->first();
        $payload = ['transactions' => [[
            'uuid' => $transaction->uuid,
            'items' => [['service_uuid' => $item->service_uuid, 'qty' => 2.5, 'price' => 7000]],
        ]]];
        $this->postJson('/api/v1/sync/push', $payload)->assertOk();
        $payload['transactions'][0]['items'][0]['price'] = 1;
        $this->postJson('/api/v1/sync/push', $payload)->assertUnprocessable()->assertJsonValidationErrors('transactions.0.items');
        $this->assertSame(17500, $transaction->fresh()->total);
        $this->assertSame(7000, $transaction->items()->first()->price);
    }

    public function test_pcs_services_require_whole_quantities(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);
        $transaction = $this->transaction($user);
        $service = Service::create(['name' => 'Selimut', 'unit' => 'pcs', 'price' => 20000]);
        $this->postJson('/api/v1/sync/push', ['transactions' => [[
            'uuid' => $transaction->uuid,
            'items' => [['service_uuid' => $service->uuid, 'qty' => 1.5, 'price' => 20000]],
        ]]])->assertUnprocessable()->assertJsonValidationErrors('transactions.0.items.0.qty');
        $this->assertSame(17500, $transaction->fresh()->total);
    }

    public function test_late_offline_creation_preserves_sale_time_and_retry_cannot_rewrite_it(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);
        $user = $this->user();
        Sanctum::actingAs($user);
        $existing = $this->transaction($user);
        $item = $existing->items()->first();
        $uuid = (string) Str::uuid();
        $payload = ['transactions' => [[
            'uuid' => $uuid,
            'customer_uuid' => $existing->customer_uuid,
            'created_at' => '2026-09-20T23:30:00+00:00',
            'items' => [['service_uuid' => $item->service_uuid, 'qty' => 1, 'price' => 7000]],
        ]]];
        $this->postJson('/api/v1/sync/push', $payload)->assertOk();
        $this->assertDatabaseHas('transactions', ['uuid' => $uuid, 'created_at' => '2026-09-21 06:30:00']);
        $payload['transactions'][0]['created_at'] = '2026-09-27T12:00:00+07:00';
        $this->postJson('/api/v1/sync/push', $payload)->assertOk();
        $this->assertDatabaseHas('transactions', ['uuid' => $uuid, 'created_at' => '2026-09-21 06:30:00']);
    }

    public function test_upload_checks_record_ownership_type_and_size_before_storing_any_file(): void
    {
        Storage::fake('public');
        $user = $this->user();
        Sanctum::actingAs($user);
        $attendance = Attendance::create(['user_id' => $this->user()->id, 'check_in_time' => now()]);
        $payload = [
            'entity_type' => 'attendance_photo',
            'entity_uuid' => $attendance->uuid,
            'photo_type' => 'check_in',
            'file' => UploadedFile::fake()->image('selfie.jpg'),
        ];
        $this->postJson('/api/v1/sync/upload', $payload)->assertForbidden();
        $this->postJson('/api/v1/sync/upload', array_replace($payload, ['entity_uuid' => (string) Str::uuid()]))->assertNotFound();
        $this->postJson('/api/v1/sync/upload', array_replace($payload, ['entity_type' => 'customer']))->assertUnprocessable();
        $this->postJson('/api/v1/sync/upload', array_replace($payload, ['file' => UploadedFile::fake()->image('selfie.jpg')->size(501)]))->assertUnprocessable();
        $this->postJson('/api/v1/sync/upload', array_replace($payload, ['file' => UploadedFile::fake()->create('payload.svg', 10, 'image/svg+xml')]))->assertUnprocessable();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_reupload_replaces_photo_without_leaving_an_orphan(): void
    {
        Storage::fake('public');
        $user = $this->user();
        Sanctum::actingAs($user);
        $attendance = Attendance::create(['user_id' => $user->id, 'check_in_time' => now()]);
        $payload = ['entity_type' => 'attendance_photo', 'entity_uuid' => $attendance->uuid, 'photo_type' => 'check_in'];
        $first = $this->postJson('/api/v1/sync/upload', [...$payload, 'file' => UploadedFile::fake()->image('first.jpg')->size(500)])
            ->assertOk()->json('file_path');
        $second = $this->postJson('/api/v1/sync/upload', [...$payload, 'file' => UploadedFile::fake()->image('second.png')])
            ->assertOk()->json('file_path');
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
        $this->assertSame($second, $attendance->fresh()->check_in_photo_path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_pull_contains_deactivation_flags_but_no_credentials_or_private_user_fields(): void
    {
        Sanctum::actingAs($this->user());
        $inactive = $this->user(['active' => false]);
        $service = Service::create(['name' => 'Nonaktif', 'price' => 5000, 'is_active' => false]);
        $response = $this->getJson('/api/v1/sync/pull')->assertOk();
        $users = $response->json('users');
        $this->assertCount(2, $users);
        $this->assertEqualsCanonicalizing(['id', 'name', 'username', 'role', 'active'], array_keys($users[0]));
        $this->assertFalse(collect($users)->firstWhere('id', $inactive->id)['active']);
        $this->assertFalse(collect($response->json('services'))->firstWhere('uuid', $service->uuid)['is_active']);
    }

    public function test_login_rejects_inactive_accounts_and_validates_device_name(): void
    {
        $user = $this->user(['active' => false]);
        $credentials = ['username' => $user->username, 'password' => 'password'];
        $this->postJson('/api/v1/auth/login', $credentials)->assertForbidden();
        $user->update(['active' => true]);
        $this->postJson('/api/v1/auth/login', [...$credentials, 'device_name' => ['invalid']])->assertUnprocessable();
        $response = $this->postJson('/api/v1/auth/login', [...$credentials, 'device_name' => 'Kasir 1'])->assertOk();
        $this->assertNotEmpty($response->json('data.token'));
        $this->assertArrayNotHasKey('password', $response->json('data.user'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    private function user(array $attributes = []): User
    {
        return User::create(array_replace([
            'name' => 'Kasir',
            'username' => 'user-'.Str::uuid(),
            'email' => 'user-'.Str::uuid().'@example.test',
            'password' => 'password',
            'role' => 'cashier',
            'active' => true,
        ], $attributes));
    }

    private function transaction(User $user): Transaction
    {
        $customer = Customer::create(['name' => 'Pelanggan', 'phone' => '081234']);
        $service = Service::create(['name' => 'Cuci', 'unit' => 'kg', 'price' => 7000]);
        $transaction = Transaction::create([
            'customer_uuid' => $customer->uuid,
            'user_id' => $user->id,
            'transaction_number' => 'TRX-EXISTING',
            'subtotal' => 17500,
            'total' => 17500,
            'payment_status' => 'BELUM',
            'laundry_status' => 'DITERIMA',
        ]);
        $transaction->items()->create(['service_uuid' => $service->uuid, 'qty' => 2.5, 'price' => 7000]);

        return $transaction;
    }
}
