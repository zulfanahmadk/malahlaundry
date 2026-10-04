<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_counts_all_branches_without_exposing_store_details(): void
    {
        $branch = Branch::create(['name' => 'CABANG-RAHASIA', 'code' => 'PRIVATE', 'active' => false]);
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->owner()->create(['name' => 'OWNER-RAHASIA']);
        User::factory()->create(['branch_id' => $branch->id, 'name' => 'KASIR-RAHASIA', 'email' => 'private@example.test', 'active' => false]);
        Service::create(['name' => 'LAYANAN-RAHASIA', 'unit' => 'kg', 'price' => 10000, 'branch_id' => 1, 'is_active' => true]);
        Service::create(['name' => 'LAYANAN-CABANG-RAHASIA', 'unit' => 'kg', 'price' => 10000, 'branch_id' => $branch->id, 'is_active' => false]);

        $this->actingAs($admin)->withSession(['branch_id' => $branch->id])->get('/admin')
            ->assertOk()->assertViewHas('summary', [
                'users' => 2, 'active_users' => 1, 'owners' => 1, 'cashiers' => 1,
                'branches' => 2, 'active_branches' => 1, 'services' => 2,
                'active_services' => 1, 'apk_releases' => 0,
            ])->assertSee('Ringkasan sistem')->assertSee('Kelola versi APK')
            ->assertDontSee('OWNER-RAHASIA')->assertDontSee('KASIR-RAHASIA')
            ->assertDontSee('CABANG-RAHASIA')->assertDontSee('LAYANAN-RAHASIA')
            ->assertDontSee('private@example.test')->assertDontSee('branches/select')
            ->assertDontSee('href="'.url('/dashboard').'"', false);
        $this->get('/')->assertRedirect('/admin');
        $this->get('/login')->assertRedirect('/admin');
    }

    public function test_only_active_admin_can_open_system_summary(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::factory()->owner()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'active' => false]))->get('/admin')->assertForbidden();
    }

    public function test_admin_cannot_enter_owner_operations_even_with_selected_branch(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->withSession(['branch_id' => 1]);
        foreach (['/dashboard', '/branches', '/branches/1/edit', '/users', '/customers', '/transactions', '/reports', '/reports/export', '/settings', '/attendances', '/apk'] as $uri) {
            $this->get($uri)->assertForbidden();
        }
        foreach (['/branches/select', '/branches/1', '/settings', '/users'] as $uri) {
            $this->post($uri, ['branch_id' => 1])->assertForbidden();
        }
        $this->get('/admin/apk')->assertOk()->assertSee('Ringkasan')->assertDontSee('branches/select');
    }

    public function test_admin_token_cannot_read_or_modify_branch_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('legacy-token')->plainTextToken;
        $this->withToken($token)->withHeader('X-Branch-Id', '1');
        foreach (['/api/v1/auth/user', '/api/v1/branches', '/api/v1/sync/pull', '/api/v1/sync/records'] as $uri) {
            $this->getJson($uri)->assertForbidden()->assertJsonPath('message', 'Admin sistem tidak memiliki akses operasional cabang.');
        }
        foreach (['/api/v1/branches', '/api/v1/branches/1', '/api/v1/settings', '/api/v1/sync/push', '/api/v1/sync/upload', '/api/v1/sync/device'] as $uri) {
            $this->postJson($uri, [])->assertForbidden();
        }
        $this->withHeader('X-Branch-Id', 'invalid')->getJson('/api/v1/branches')->assertForbidden();
    }
}
