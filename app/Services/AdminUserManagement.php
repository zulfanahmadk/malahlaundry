<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminUserManagement
{
    public function save(Request $request, ?User $user = null): User
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $data = $request->validate([
            'access_role_id' => 'nullable|integer|exists:access_roles,id',
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user?->id)],
            'role' => ['required', Rule::in(['admin', 'owner', 'cashier'])],
            'active' => 'required|boolean',
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:72'],
            'branch_id' => [Rule::requiredIf($request->input('role') !== 'admin'), 'nullable', 'integer', 'exists:branches,id'],
        ]);

        \App\Support\Access::validateAssignment($request, $data, $user);
        return DB::transaction(function () use ($request, $user, $data) {
            $this->lockActor($request);
            $account = $user ? User::whereKey($user->id)->lockForUpdate()->firstOrFail() : new User();
            $originalUsername = $account->username;
            if ($account->id === $request->user()->id && ($data['role'] !== 'admin' || ! $data['active'])) {
                throw ValidationException::withMessages(['role' => 'Admin tidak dapat menonaktifkan atau menurunkan peran akun sendiri.']);
            }
            if ($data['role'] === 'admin') {
                // Admin has no operational branch access; retain the required internal FK.
                $data['branch_id'] = $account->branch_id ?? Branch::orderBy('id')->value('id');
            }
            \App\Support\Access::validateAssignment($request, $data, $user);
            if (empty($data['password'])) {
                unset($data['password']);
            }
            $account->fill($data);
            $revoke = $account->exists && (
                $account->isDirty(['branch_id', 'role', 'access_role_id', 'password', 'username'])
                || ($account->isDirty('active') && ! $account->active)
            );
            $forgetCredentials = $account->isDirty(['username', 'password', 'role']);
            if ($revoke) {
                $account->remember_token = Str::random(60);
            }
            $account->save();
            if ($revoke) {
                $this->revokeAccess($request, $account);
            }
            if ($forgetCredentials && $originalUsername && Storage::disk('local')->exists('admin-initial-password.txt')) {
                $lines = preg_split('/\r?\n/', Storage::disk('local')->get('admin-initial-password.txt'));
                if (($lines[0] ?? '') === 'Username: '.$originalUsername) {
                    Storage::disk('local')->delete('admin-initial-password.txt');
                }
            }

            return $account;
        });
    }

    public function toggle(Request $request, User $user): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
        DB::transaction(function () use ($request, $user) {
            $this->lockActor($request);
            $account = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($account->id === $request->user()->id) {
                throw ValidationException::withMessages(['active' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
            }
            $account->active = ! $account->active;
            if (! $account->active) {
                $account->remember_token = Str::random(60);
            }
            $account->save();
            if (! $account->active) {
                $this->revokeAccess($request, $account);
            }
        });
    }

    private function lockActor(Request $request): void
    {
        $admins = User::where('role', 'admin')->where('active', true)->orderBy('id')->lockForUpdate()->get(['id']);
        abort_unless($admins->contains('id', $request->user()->id), 403, 'Akun admin sudah tidak aktif.');
    }

    private function revokeAccess(Request $request, User $account): void
    {
        $account->tokens()->delete();
        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $account->id)
                ->when($account->id === $request->user()->id, fn ($query) => $query->where('id', '!=', $request->session()->getId()))
                ->delete();
        }
        if ($account->id === $request->user()->id) {
            $request->session()->regenerate();
        }
    }
}
