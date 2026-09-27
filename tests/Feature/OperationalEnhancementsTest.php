<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OperationalEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_active_account_can_download_seeded_history_and_customers(): void
    {
        $this->seed();
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/sync/records?type=transactions')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.customer_name', 'Budi Santoso')
            ->assertJsonPath('data.0.items.0.service_name', 'Cuci Komplit Reguler');
        $this->getJson('/api/v1/sync/records?type=customers')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/sync/records?type=users')->assertUnprocessable();
    }

    public function test_records_use_stable_bounded_pages_and_inactive_accounts_cannot_pull(): void
    {
        for ($i = 0; $i < 201; $i++) {
            Customer::create(['name' => 'Customer '.$i, 'phone' => '081234567890']);
        }
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $first = $this->getJson('/api/v1/sync/records?type=customers')->assertOk()->assertJsonCount(200, 'data');
        $cursor = $first->json('next');
        $this->assertNotNull($cursor);
        $second = $this->getJson('/api/v1/sync/records?type=customers&after='.$cursor)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('next', null);
        $this->assertNotContains($second->json('data.0.uuid'), array_column($first->json('data'), 'uuid'));
        $user->update(['active' => false]);
        $this->getJson('/api/v1/sync/records?type=transactions')->assertForbidden();
    }

    public function test_three_stage_pickup_records_original_offline_time_and_is_idempotent(): void
    {
        $this->seed();
        $transaction = Transaction::firstOrFail();
        $transaction->forceFill(['created_at' => now()->subDays(2)])->save();
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/sync/push', ['transactions' => [['uuid' => $transaction->uuid, 'laundry_status' => 'DIPROSES']]])->assertUnprocessable();
        $pickup = now()->subHour()->startOfSecond();
        $data = ['transactions' => [['uuid' => $transaction->uuid, 'laundry_status' => 'SELESAI', 'payment_status' => 'LUNAS', 'picked_up_at' => $pickup->toIso8601String()]]];
        $this->postJson('/api/v1/sync/push', $data)->assertOk();
        $this->postJson('/api/v1/sync/push', $data)->assertOk();
        $this->assertTrue($transaction->fresh()->picked_up_at->equalTo($pickup));
        $this->assertDatabaseCount('transactions', 1);
        $this->get('/n/'.$transaction->uuid)->assertOk()->assertSee('Tanggal Pengambilan');
    }

    public function test_attendance_filters_and_owner_only_photos_work_without_storage_symlink(): void
    {
        Storage::fake('public');
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->create(['name' => 'Nina Kasir']);
        $path = UploadedFile::fake()->image('selfie.jpg')->store('attendances', 'public');
        $attendance = Attendance::create(['user_id' => $cashier->id, 'check_in_time' => '2026-09-27 08:00:00', 'check_in_photo_path' => $path]);
        Attendance::create(['user_id' => $owner->id, 'check_in_time' => '2026-09-26 08:00:00', 'check_out_time' => '2026-09-26 17:00:00']);
        $this->actingAs($owner)->get('/attendances?q=Nina&status=active&from=2026-09-27&to=2026-09-27')
            ->assertOk()->assertViewHas('attendances', fn ($records) => $records->count() === 1 && $records->first()->id === $attendance->id)
            ->assertSee(route('attendances.photo', [$attendance->uuid, 'check_in']));
        $photo = $this->get(route('attendances.photo', [$attendance->uuid, 'check_in']))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame(Storage::disk('public')->get($path), $photo->streamedContent());
        $this->getJson('/attendances?from=2026-09-27&to=2026-09-26')->assertUnprocessable();
        $this->actingAs($cashier)->get(route('attendances.photo', [$attendance->uuid, 'check_in']))->assertForbidden();
    }

    public function test_owner_can_edit_users_on_web_and_api_without_disabling_self(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->create();
        $cashier->createToken('old');
        $this->actingAs($owner)->get('/users/'.$cashier->id.'/edit')->assertOk()->assertSee('Simpan perubahan');
        $data = ['name' => 'Kasir Baru', 'username' => 'kasirbaru', 'role' => 'cashier', 'active' => true, 'password' => 'newpassword123'];
        $this->post('/users/'.$cashier->id, $data)->assertRedirect('/users')->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('newpassword123', $cashier->fresh()->password));
        $this->assertSame(0, $cashier->tokens()->count());
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/users/'.$cashier->id, array_replace($data, ['name' => 'Nama Dari Android']))->assertOk();
        $this->postJson('/api/v1/users/'.$owner->id, array_replace($data, ['username' => $owner->username]))->assertUnprocessable();
        Sanctum::actingAs($cashier);
        $this->postJson('/api/v1/users/'.$owner->id, $data)->assertForbidden();
    }

    public function test_account_can_update_own_profile_and_password_without_losing_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('current')->plainTextToken;
        $other = $user->createToken('other')->plainTextToken;
        $data = ['name' => 'Nama Baru', 'username' => 'profilbaru', 'current_password' => 'wrong', 'password' => 'newpassword123', 'password_confirmation' => 'newpassword123', 'role' => 'owner'];
        $this->withToken($token)->postJson('/api/v1/auth/profile', $data)->assertUnprocessable();
        $this->withToken($token)->postJson('/api/v1/auth/profile', array_replace($data, ['current_password' => 'password']))->assertOk()->assertJsonPath('data.user.username', 'profilbaru');
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
        $this->assertSame('cashier', $user->fresh()->role);
        $this->assertNotNull(PersonalAccessToken::findToken($token));
        $this->assertNull(PersonalAccessToken::findToken($other));
    }
}
