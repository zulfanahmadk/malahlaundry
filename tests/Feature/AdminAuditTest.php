<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class AdminAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_admin_can_read_audit_and_logs_have_no_edit_or_delete_routes(): void
    {
        $this->get('/admin/audit')->assertRedirect('/login');
        $this->actingAs(User::factory()->owner()->create())->get('/admin/audit')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/admin/audit')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'active' => false]))->get('/admin/audit')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/audit?feature=nota')->assertOk()
            ->assertSee('Log Audit')->assertSee('Belum ada catatan audit', false)->assertDontSee('branches/select');
        $this->post('/admin/audit')->assertStatus(405);
        $this->delete('/admin/audit/1')->assertNotFound();
    }

    public function test_login_logout_and_failed_login_are_recorded_without_credentials(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'username' => 'AUDIT-USERNAME-SECRET']);
        $this->post('/login', ['username' => $admin->username, 'password' => 'password'])->assertRedirect('/admin');
        $this->assertDatabaseHas('audit_logs', ['action' => 'login', 'actor_id' => $admin->id, 'actor_role' => 'admin', 'outcome' => 'success']);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertDatabaseHas('audit_logs', ['action' => 'logout', 'actor_id' => $admin->id, 'outcome' => 'success']);
        $this->post('/login', ['username' => $admin->username, 'password' => 'AUDIT-PASSWORD-SECRET'])->assertSessionHasErrors('username');
        $this->assertDatabaseHas('audit_logs', ['action' => 'login', 'actor_id' => null, 'outcome' => 'rejected', 'status' => 302]);
        $this->post('/login', ['username' => $admin->username, 'password' => 'password'])->assertRedirect('/admin');
        $this->assertSame('success', DB::table('audit_logs')->latest('id')->value('outcome'));
        $data = json_encode(DB::table('audit_logs')->get());
        $this->assertStringNotContainsString('AUDIT-USERNAME-SECRET', $data);
        $this->assertStringNotContainsString('AUDIT-PASSWORD-SECRET', $data);
    }

    public function test_android_login_and_changes_capture_actor_and_route_pattern_without_private_input(): void
    {
        $owner = User::factory()->owner()->create();
        $login = $this->postJson('/api/v1/auth/login', ['username' => $owner->username, 'password' => 'password'])->assertOk();
        $token = $login->json('data.token');
        $this->assertDatabaseHas('audit_logs', ['action' => 'api/v1/auth/login', 'actor_id' => $owner->id, 'channel' => 'api', 'outcome' => 'success']);
        $cashier = User::factory()->create();
        $response = $this->withToken($token)->postJson('/api/v1/users/'.$cashier->id.'?secret=QUERY-SECRET', [
            'name' => 'CUSTOMER-SECRET', 'username' => $cashier->username, 'role' => 'cashier', 'active' => true,
        ])->assertOk();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'api/v1/users/{user}', 'actor_id' => $owner->id, 'branch_id' => 1,
            'feature' => 'pengguna', 'request_id' => $response->headers->get('X-Request-ID'),
        ]);
        $this->assertStringNotContainsString('CUSTOMER-SECRET', json_encode(DB::table('audit_logs')->get()));
        $this->assertStringNotContainsString('QUERY-SECRET', json_encode(DB::table('audit_logs')->get()));
        $this->assertStringNotContainsString($token, json_encode(DB::table('audit_logs')->get()));
    }

    public function test_denied_branch_access_and_invalid_apk_are_recorded_as_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/dashboard')->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'dashboard', 'actor_id' => $admin->id, 'status' => 403, 'outcome' => 'rejected']);
        $this->from('/admin/apk')->post('/admin/apk', [])->assertSessionHasErrors('apk');
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin/apk', 'feature' => 'versi-apk', 'outcome' => 'rejected']);
    }

    public function test_audit_database_failure_does_not_fail_an_acknowledged_change(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->create();
        $token = $owner->createToken('test')->plainTextToken;
        Schema::drop('audit_logs');
        $this->withToken($token)->postJson('/api/v1/users/'.$cashier->id, [
            'name' => 'Perubahan tersimpan', 'username' => $cashier->username, 'role' => 'cashier', 'active' => true,
        ])->assertOk();
        $this->assertSame('Perubahan tersimpan', $cashier->fresh()->name);
    }

    public function test_server_error_is_audited_without_storing_exception_details(): void
    {
        Route::get('/services/audit-error', fn () => throw new RuntimeException('PRIVATE-ERROR-DETAIL'))
            ->middleware(['web', 'auth:web', 'active', 'owner', 'branch'])->name('services.audit-error');
        $owner = User::factory()->owner()->create();
        $this->actingAs($owner)->get('/services/audit-error')->assertStatus(500);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $owner->id, 'status' => 500, 'outcome' => 'error']);
        $this->assertStringNotContainsString('PRIVATE-ERROR-DETAIL', json_encode(DB::table('audit_logs')->get()));
    }

    public function test_filters_use_wib_boundaries_paginate_and_omit_owner_identity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $base = [
            'occurred_at' => '2026-10-03 17:00:00', 'actor_id' => 912,
            'actor_role' => 'owner', 'branch_id' => 1, 'feature' => 'layanan',
            'action' => 'services/{uuid}', 'method' => 'POST', 'channel' => 'web',
            'status' => 302, 'outcome' => 'success', 'request_id' => null,
        ];
        for ($i = 0; $i < 31; $i++) {
            DB::table('audit_logs')->insert($base);
        }
        DB::table('audit_logs')->insert(array_replace($base, ['occurred_at' => '2026-10-03 16:59:59', 'action' => 'outside-start']));
        DB::table('audit_logs')->insert(array_replace($base, ['occurred_at' => '2026-10-04 17:00:00', 'action' => 'outside-end']));
        DB::table('audit_logs')->insert(array_replace($base, ['outcome' => 'rejected', 'action' => 'wrong-outcome']));
        $filters = '?from=2026-10-04&to=2026-10-04&feature=layanan&outcome=success&role=owner&actor_id=912';
        $this->actingAs($admin)->get('/admin/audit'.$filters)->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 31 && $logs->count() === 30)
            ->assertSee('4 Oktober 2026, 00.00')->assertSee('ID 912')
            ->assertDontSee('outside-start')->assertDontSee('outside-end')->assertDontSee('wrong-outcome')
            ->assertSee('feature=layanan', false)->assertSee('page=2', false);
        $this->get('/admin/audit'.$filters.'&page=2')->assertOk()->assertViewHas('logs', fn ($logs) => $logs->count() === 1);
        $this->get('/admin/audit?to=2026-10-04')->assertOk();
        $this->getJson('/admin/audit?from=2026-10-05&to=2026-10-04')->assertUnprocessable();
        $this->getJson('/admin/audit?feature=../../laravel.log')->assertUnprocessable();
    }
}
