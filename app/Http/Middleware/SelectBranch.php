<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;

class SelectBranch
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_if($user->isAdmin(), 403, 'Admin sistem tidak memiliki akses operasional cabang.');
        $branchId = $request->is('api/*')
            ? $request->header('X-Branch-Id', $user->branch_id)
            : $request->session()->get('branch_id', $user->branch_id);
        abort_unless(ctype_digit((string) $branchId), 422, 'Cabang tidak valid.');
        abort_if(! $user->isOwner() && (int) $branchId !== (int) $user->branch_id, 403, 'Cabang tidak sesuai penempatan akun.');
        $branch = Branch::findOrFail($branchId);
        $request->attributes->set('branch_id', $branch->id);
        $request->attributes->set('branch', $branch);
        return $next($request);
    }
}
