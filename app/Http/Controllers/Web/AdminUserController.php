<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\AdminUserManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:255', 'role' => 'nullable|in:admin,owner,cashier',
            'active' => 'nullable|in:0,1', 'branch_id' => 'nullable|integer|exists:branches,id',
        ]);
        $query = User::with(['branch:id,name,code,active', 'accessRole', 'latestLogin']);
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$filters['q'].'%')->orWhere('username', 'like', '%'.$filters['q'].'%'));
        }
        foreach (['role', 'active', 'branch_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $filters[$field]);
            }
        }
        if ($request->filled('branch_id')) {
            $query->where('role', '!=', 'admin');
        }
        $users = $query->orderBy('name')->orderBy('id')->paginate(30, ['id', 'branch_id', 'name', 'username', 'role', 'access_role_id', 'active', 'last_login_at'])->withQueryString();
        $counts = User::selectRaw("COUNT(*) AS total,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS admins,
            SUM(CASE WHEN role = 'owner' THEN 1 ELSE 0 END) AS owners,
            SUM(CASE WHEN role = 'cashier' THEN 1 ELSE 0 END) AS cashiers")->first();

        return view('admin.users', ['users' => $users, 'stats' => $counts->getAttributes(), 'branches' => $this->branchOptions()]);
    }

    public function create(): View
    {
        return view('admin.user-form', ['user' => new User(['role' => 'cashier', 'active' => true]), 'branches' => $this->branchOptions()]);
    }

    public function logins(User $user): View
    {
        $logins = \App\Models\UserLogin::where('user_id', $user->id)->latest('id')->paginate(30);
        return view('admin.user-logins', compact('user', 'logins'));
    }

    public function edit(User $user): View
    {
        return view('admin.user-form', ['user' => $user, 'branches' => $this->branchOptions()]);
    }

    public function store(Request $request, AdminUserManagement $management): RedirectResponse
    {
        $management->save($request);
        return redirect()->route('admin.users.index')->with('success', 'Pengguna baru berhasil dibuat.');
    }

    public function update(Request $request, User $user, AdminUserManagement $management): RedirectResponse
    {
        $management->save($request, $user);
        return redirect()->route('admin.users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function toggle(Request $request, User $user, AdminUserManagement $management): RedirectResponse
    {
        $management->toggle($request, $user);
        return back()->with('success', 'Status akun pengguna diperbarui.');
    }

    public function branches(): View
    {
        $branches = Branch::orderBy('name')->get(['id', 'name', 'code', 'active']);
        $counts = User::whereIn('role', ['owner', 'cashier'])->selectRaw("branch_id,
            SUM(CASE WHEN role = 'owner' THEN 1 ELSE 0 END) AS owners,
            SUM(CASE WHEN role = 'cashier' THEN 1 ELSE 0 END) AS cashiers")->groupBy('branch_id')->get()->keyBy('branch_id');
        return view('admin.branches', compact('branches', 'counts'));
    }

    private function branchOptions()
    {
        return Branch::orderBy('name')->get(['id', 'name', 'code', 'active']);
    }
}
