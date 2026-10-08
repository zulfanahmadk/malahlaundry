<?php

namespace Tests\Feature;

use App\Models\AccessRole;
use App\Models\Branch;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Laravel\Sanctum\Sanctum;

class AccessRolesTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        $app->instance('env', 'testing');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('app.env', 'testing');
        return $app;
    }

    private function account(string $base = 'owner', ?AccessRole $role = null): User
    {
        $branch = Branch::first() ?? Branch::create(['name' => 'Test', 'code' => 'TEST', 'active' => true]);
        return User::factory()->create(['role' => $base, 'branch_id' => $branch->id, 'access_role_id' => $role?->id]);
    }

    public function test_read_only_role_hides_menus_and_blocks_writes_and_export(): void
    {
        $actor = $this->account();
        $role = AccessRole::create(['name' => 'Viewer', 'base_role' => 'owner', 'permissions' => ['dashboard.view', 'profile.view', 'users.view']]);
        $actor->update(['access_role_id' => $role->id]);
        $this->actingAs($actor)->get('/users')->assertOk()->assertDontSee('href="http://localhost/roles"', false);
        $this->get('/dashboard')->assertOk()->assertDontSee('Omzet hari ini');
        $this->get('/reports')->assertForbidden();
        $this->post('/users', [])->assertForbidden();
        $this->get('/attendances/export')->assertForbidden();
        $this->get('/roles')->assertForbidden();
    }

    public function test_admin_can_create_owner_role_and_assign_it(): void
    {
        $admin = $this->account('admin');
        $this->actingAs($admin)->get('/admin/roles')->assertOk();
        $this->actingAs($admin)->post('/admin/roles', [
            'name' => 'Supervisor', 'base_role' => 'owner',
            'permissions' => ['dashboard.view', 'profile.view', 'reports.view'],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $role = AccessRole::where('name', 'Supervisor')->firstOrFail();
        $this->get('/admin/users/create')->assertOk()->assertSee('Supervisor');
        $this->post('/admin/users', [
            'name' => 'Supervisor', 'username' => 'supervisor', 'role' => 'owner',
            'branch_id' => $admin->branch_id, 'active' => 1, 'password' => 'test-password', 'access_role_id' => $role->id,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['username' => 'supervisor', 'access_role_id' => $role->id]);
    }

    public function test_owner_cannot_edit_global_role_or_create_admin_role(): void
    {
        $owner = $this->account();
        $global = AccessRole::create(['name' => 'Global', 'base_role' => 'owner', 'permissions' => ['dashboard.view', 'profile.view']]);
        $this->actingAs($owner)->post('/roles/'.$global->id, ['name' => 'Hijacked'])->assertForbidden();
        $this->post('/roles', ['name' => 'Admin', 'base_role' => 'admin'])->assertSessionHasErrors('base_role');
    }

    public function test_role_in_use_cannot_be_deleted_or_changed_by_its_own_user(): void
    {
        $admin = $this->account('admin');
        $role = AccessRole::create(['name' => 'Owner', 'base_role' => 'owner', 'permissions' => ['dashboard.view', 'profile.view']]);
        $this->account('owner', $role);
        $this->actingAs($admin)->post('/admin/roles/'.$role->id.'/delete')->assertStatus(422);
        $this->assertDatabaseHas('access_roles', ['id' => $role->id]);
    }

    public function test_restricted_owner_cannot_grant_unrestricted_role_or_push_forbidden_changes(): void
    {
        $owner = $this->account();
        $role = AccessRole::create(['name' => 'Limited', 'base_role' => 'owner', 'permissions' => ['dashboard.view', 'profile.view', 'users.view', 'users.write']]);
        $owner->update(['access_role_id' => $role->id]);
        $this->actingAs($owner)->post('/users', [
            'name' => 'Escalation', 'username' => 'escalation', 'role' => 'owner',
            'branch_id' => $owner->branch_id, 'password' => 'test-password',
        ])->assertSessionHasErrors('access_role_id');
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/sync/push', ['customers' => [['uuid' => '4599d1ed-0758-44f7-b49b-e5f51cbf939e', 'name' => 'Denied']]])->assertForbidden();
        $this->getJson('/api/v1/sync/records?type=transactions')->assertForbidden();
    }

    public function test_login_records_metadata_without_password_and_locations_are_owned(): void
    {
        $user = $this->account('cashier');
        $login = $this->postJson('/api/v1/auth/login', ['username' => $user->username, 'password' => 'password', 'device_name' => 'Test Phone'])
            ->assertOk()->assertJsonMissingPath('data.user.password');
        $record = UserLogin::firstOrFail();
        $this->assertSame('Test Phone', $record->device);
        $this->assertNotEmpty($record->ip_address);
        $other = $this->account();
        Sanctum::actingAs($other);
        $this->postJson('/api/v1/auth/location', ['login_id' => $record->id, 'latitude' => -6.2, 'longitude' => 106.8])->assertNotFound();
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/auth/location', ['login_id' => $record->id, 'latitude' => -6.2, 'longitude' => 106.8])->assertOk();
        $this->assertEquals(-6.2, (float) $record->fresh()->latitude);
        $admin = $this->account('admin');
        $this->actingAs($admin, 'web')->get('/admin/users')->assertOk()->assertSee('Riwayat login');
        $this->get('/admin/users/'.$user->id.'/logins')->assertOk()->assertSee('Test Phone');
    }

    public function test_branch_scoped_role_cannot_access_another_branch(): void
    {
        $owner = $this->account();
        $role = AccessRole::create(['name' => 'Local', 'branch_id' => $owner->branch_id, 'base_role' => 'owner', 'permissions' => ['dashboard.view', 'profile.view', 'branches.view']]);
        $owner->update(['access_role_id' => $role->id]);
        $other = Branch::create(['name' => 'Other', 'code' => 'OTHER', 'active' => true]);
        Sanctum::actingAs($owner);
        $this->withHeader('X-Branch-Id', $other->id)->getJson('/api/v1/branches')->assertForbidden();
        $this->withHeader('X-Branch-Id', $owner->branch_id)->postJson('/api/v1/branches/'.$other->id, ['name' => 'Denied'])->assertForbidden();
    }

    public function test_restricted_admin_cannot_bypass_profile_write_permission(): void
    {
        $admin = $this->account('admin');
        $role = AccessRole::create(['name' => 'Admin viewer', 'base_role' => 'admin', 'permissions' => ['dashboard.view', 'profile.view']]);
        $admin->update(['access_role_id' => $role->id]);
        $this->actingAs($admin)->post('/admin/password', [])->assertForbidden();
    }

    public function test_editing_user_without_role_field_preserves_assigned_role(): void
    {
        $admin = $this->account('admin');
        $role = AccessRole::create(['name' => 'Viewer', 'base_role' => 'owner', 'permissions' => ['dashboard.view', 'profile.view']]);
        $user = $this->account('owner', $role);
        $this->actingAs($admin)->post('/admin/users/'.$user->id, [
            'name' => 'Updated', 'username' => $user->username, 'role' => 'owner', 'active' => 1, 'branch_id' => $user->branch_id,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($role->id, $user->fresh()->access_role_id);
    }

    public function test_hours_access_cannot_be_bypassed_through_branch_endpoint(): void
    {
        $owner = $this->account();
        $role = AccessRole::create(['name' => 'Branch manager', 'base_role' => 'owner', 'permissions' => ['dashboard.view', 'profile.view', 'branches.view', 'branches.write']]);
        $owner->update(['access_role_id' => $role->id]);
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/branches/'.$owner->branch_id, ['opening_hours' => null])->assertForbidden();
        $this->postJson('/api/v1/branches/'.$owner->branch_id, ['name' => 'Test', 'code' => 'TEST', 'templates' => ['WA_DITERIMA' => 'Denied']])->assertForbidden();
    }

    public function test_admin_can_set_menu_access_on_user_without_changing_role_peers(): void
    {
        $admin = $this->account('admin');
        $role = AccessRole::create(['name' => 'Supervisor', 'base_role' => 'owner', 'permissions' => ['dashboard.view', 'profile.view', 'customers.view', 'customers.export']]);
        $user = $this->account('owner', $role);
        $peer = $this->account('owner', $role);
        $user->createToken('old-device');
        $this->actingAs($admin)->get('/admin/users/'.$user->id.'/edit')->assertOk()->assertSee('Atur khusus untuk pengguna ini');
        $this->post('/admin/users/'.$user->id, [
            'name' => $user->name, 'username' => $user->username, 'role' => 'owner', 'active' => 1, 'branch_id' => $user->branch_id,
            'access_role_id' => $role->id, 'access_mode' => 'custom', 'menu_permissions' => ['dashboard.view', 'profile.view', 'customers.view'],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->canAccess('customers', 'export'));
        $this->assertTrue($peer->fresh()->canAccess('customers', 'export'));
        $this->assertSame(0, $user->tokens()->count());
        $this->actingAs($user->fresh())->get('/customers')->assertOk();
        $this->get('/customers/export')->assertForbidden();
        $this->get('/reports')->assertForbidden();
        Sanctum::actingAs($user->fresh());
        $this->getJson('/api/v1/auth/user')->assertOk()->assertJsonMissing(['reports.view']);
        $this->postJson('/api/v1/sync/push', ['customers' => [['uuid' => '4599d1ed-0758-44f7-b49b-e5f51cbf939e', 'name' => 'Denied']]])->assertForbidden();
    }

    public function test_only_existing_actions_are_offered_and_accepted(): void
    {
        $admin = $this->account('admin');
        $this->actingAs($admin)->get('/admin/roles')->assertOk()
            ->assertSee('Unduh laporan Excel')->assertDontSee('value="dashboard.export"', false)
            ->assertDontSee('value="reports.write"', false)->assertDontSee('value="sync.write"', false);
        $this->post('/admin/roles', ['name' => 'Invalid', 'base_role' => 'owner', 'permissions' => ['dashboard.view', 'profile.view', 'reports.write']])
            ->assertSessionHasErrors('permissions.2');
    }

    public function test_owner_can_create_user_with_custom_access_and_cannot_exceed_own_permissions(): void
    {
        $owner = $this->account();
        $owner->update(['menu_permissions' => ['dashboard.view', 'profile.view', 'users.view', 'users.write']]);
        $payload = ['name' => 'Limited cashier', 'username' => 'limited-cashier', 'role' => 'cashier', 'branch_id' => $owner->branch_id,
            'password' => 'test-password', 'access_mode' => 'custom', 'menu_permissions' => ['dashboard.view', 'profile.view']];
        $this->actingAs($owner)->post('/users', $payload)->assertRedirect()->assertSessionHasNoErrors();
        $cashier = User::where('username', 'limited-cashier')->firstOrFail();
        $this->assertSame(['dashboard.view', 'profile.view'], $cashier->menu_permissions);
        $payload['username'] = 'escalated-cashier';
        $payload['menu_permissions'][] = 'transactions.view';
        $this->post('/users', $payload)->assertSessionHasErrors('access_role_id');
        $this->assertDatabaseMissing('users', ['username' => 'escalated-cashier']);
    }

    public function test_user_permissions_survive_old_client_edit_and_can_be_reset_to_role(): void
    {
        $admin = $this->account('admin');
        $user = $this->account();
        $user->update(['menu_permissions' => ['dashboard.view', 'profile.view']]);
        $payload = ['name' => $user->name, 'username' => $user->username, 'role' => 'owner', 'active' => 1, 'branch_id' => $user->branch_id];
        $this->actingAs($admin)->post('/admin/users/'.$user->id, $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(['dashboard.view', 'profile.view'], $user->fresh()->menu_permissions);
        $this->post('/admin/users/'.$user->id, $payload + ['access_mode' => 'role'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->menu_permissions);
        $this->assertTrue($user->fresh()->canAccess('reports'));
    }

    public function test_custom_access_cannot_exceed_role_or_remove_required_menus(): void
    {
        $admin = $this->account('admin');
        $role = AccessRole::create(['name' => 'Viewer', 'base_role' => 'owner', 'permissions' => ['dashboard.view', 'profile.view']]);
        $user = $this->account('owner', $role);
        $payload = ['name' => $user->name, 'username' => $user->username, 'role' => 'owner', 'active' => 1, 'branch_id' => $user->branch_id, 'access_mode' => 'custom'];
        $this->actingAs($admin)->post('/admin/users/'.$user->id, $payload + ['menu_permissions' => ['dashboard.view', 'profile.view', 'reports.view']])
            ->assertSessionHasErrors('menu_permissions');
        $this->post('/admin/users/'.$user->id, $payload + ['menu_permissions' => ['dashboard.view']])->assertSessionHasErrors('menu_permissions');
        $this->assertNull($user->fresh()->menu_permissions);
    }

    public function test_custom_admin_cannot_grant_broader_roles_or_change_own_access(): void
    {
        $admin = $this->account('admin');
        $admin->update(['menu_permissions' => ['dashboard.view', 'profile.view', 'roles.view', 'roles.write', 'users.view', 'users.write']]);
        $this->actingAs($admin)->post('/admin/roles', ['name' => 'Escalation', 'base_role' => 'admin', 'permissions' => ['dashboard.view', 'profile.view', 'apk.view']])->assertForbidden();
        $this->post('/admin/users/'.$admin->id, ['name' => $admin->name, 'username' => $admin->username, 'role' => 'admin', 'active' => 1, 'access_mode' => 'role'])
            ->assertSessionHasErrors('menu_permissions');
    }
}
