<?php

namespace App\Http\Middleware;

use App\Support\Access;
use Closure;
use Illuminate\Http\Request;

class CheckAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user) {
            if ($scope = $user->accessRole?->branch_id) {
                $branch = $request->route('branch');
                $target = $request->route('user');
                abort_if($branch instanceof \App\Models\Branch && (int) $branch->id !== (int) $scope, 403, 'Cabang di luar akses role.');
                abort_if($target instanceof \App\Models\User && (int) $target->branch_id !== (int) $scope, 403, 'Pengguna di luar akses cabang role.');
            }
            $feature = Access::routeFeature($request->route()?->getName());
            if ($request->is('api/*')) {
                $feature = match (true) {
                    $request->is('api/v1/settings') => 'settings',
                    $request->is('api/v1/users/*') => 'users',
                    $request->is('api/v1/branches*') => 'branches',
                    $request->is('api/v1/auth/profile') => 'profile',
                    $request->is('api/v1/sync/records') => $request->input('type') === 'customers' ? 'customers' : 'transactions',
                    default => null,
                };
                if ($request->is('api/v1/branches*') && ! $request->isMethodSafe() && $request->route('branch')) {
                    $feature = Access::branchFeature(array_keys($request->all()));
                }
            }
            $action = $request->isMethodSafe() ? (str_contains($request->route()?->getName() ?? '', 'export') ? 'export' : 'view') : 'write';
            if ($request->route()?->getName() === 'branches.select') {
                $action = 'view';
            }
            if ($feature) {
                abort_unless(Access::allowed($user, $feature, $action), 403, 'Role Anda tidak memiliki akses fitur ini.');
            }
        }
        return $next($request);
    }
}
