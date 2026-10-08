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
}
